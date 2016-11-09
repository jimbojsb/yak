<?php
namespace Yak\Command;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    protected $config;
    protected $connections = array();
    protected $input;

    public function __construct()
    {
        parent::__construct();
        $this->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'config file, defaults to ./yak_config.php');
        $this->addOption('target', 't', InputOption::VALUE_OPTIONAL, 'target connection from config file');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->config = $this->getConfig();
    }

    protected function getConfig()
    {
        $configOptions = array();

        $configFile = $this->input->getOption('config') ?: 'yak_config.php';
        if (file_exists($configFile)) {
            $configOptions = include($configFile);
        }
        return $configOptions;
    }

    /**
     * @return \PDO
     */
    protected function getConnection($config = null)
    {
        if (!$config) {
            $config = $this->getTarget();
        }

        $target = false;
        if (is_string($config)) {
            $target = $config;
            $config = $this->config[$target];
        }

        if ($target) {
            if ($this->connections[$target]) {
                return $this->connections[$target];
            }
        }

        $pdoDSN = "mysql:dbname=" . $config["dbname"] . ";host=" . $config["host"] . ";charset=utf8";
        $pdo = new \PDO($pdoDSN, $config["username"], $config["password"]);
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 1);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if ($target) {
            $this->connections[$target] = $pdo;
        }

        return $pdo;
    }




    public function getTarget()
    {
        $target = $this->input->getOption('target') ?: getenv('YAK_TARGET') ?: getenv('APPLICATION_ENV') ?: $this->getDefaultTarget();
        if (!$target) {
            throw new \Exception("Attempted to get a connection target but couldn't find one");
        }
        return $target;
    }

    public function getDefaultTarget()
    {
        if ($this->config) {
            foreach ($this->config as $target => $options) {
                if ($target["default"] === true) {
                    return $target;
                }
            }
        }
        return false;
    }
}
