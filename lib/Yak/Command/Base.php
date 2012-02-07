<?php
namespace Yak\Command;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
class Base extends Command
{
    protected $pdo;
    protected $config;
    protected $input;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->addOption('host', 'l', InputOption::VALUE_NONE, 'hostname of mysql server');
        $this->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'mysql username');
        $this->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'mysql password');
        $this->addOption('database', 'd', InputOption::VALUE_OPTIONAL, 'database name');
        $this->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'database name');
    }

    protected function setIntput(InputInterface $input)
    {
        $this->input = $input;
    }

    protected function getConfig()
    {
        if ($this->config) {
            return $this->config;
        }

        $configOptions = array();

        $configFile = $this->input->getOption('config') ?: 'yak_config.php';
        if (file_exists($configFile)) {
            $configFileOptions = include($configFile);
            $configOptions = $configFileOptions[$this->getEnvironment()];
        }

        $configOptions["host"] = $this->input->getOption('host') ?: $configOptions["host"];
        $configOptions["username"] = $this->input->getOption('username') ?: $configOptions["username"];
        $configOptions["password"] = $this->input->getOption('password') ?: $configOptions["password"];
        $configOptions["dbname"] = $this->input->getOption('database') ?: $configOptions["dbname"];

        if (!(isset($configOptions["host"]) && isset($configOptions["username"]) && isset($configOptions["password"]) && isset($configOptions["dbname"]))) {
            throw new \InvalidArgumentException("Missing config params. Either specify them as options or specify a yak_config.php");
        }

        $this->config = $configOptions;
        return $this->config;
    }

    /**
     * @return PDO
     */
    protected function getPdo()
    {
        if ($this->pdo) {
            return $this->pdo;
        }
        $config = $this->getConfig();
        $pdoDSN = "mysql:dbname=" . $config["dbname"] . ";host=" . $config["host"];
        $pdo = new \PDO($pdoDSN, $config["username"], $config["password"]);
        $this->pdo = $pdo;
        return $this->pdo;
    }

    protected function getMigrations()
    {
        $migrations = array();
        $dir = "./migrations";
        try {
            $d = new \DirectoryIterator($dir);
        } catch (\Exception $e) {
            return $migrations;
        }
        
        foreach ($d as $file) {
            if ($file->isFile()) {
                $fileName = $file->getPathname();
                $nameParts = explode('.', $file->getFilename());
                $version = $nameParts[0];
                $description = $nameParts[1];
                $contents = file_get_contents($fileName);

                $migrationParts = preg_split('/--(up|down)/s', $contents);
                if (count($migrationParts) == 2) {
                    $up = $migrationParts[1];
                    $down = null;
                } else {
                    $up = $migrationParts[1];
                    $down = $migrationParts[2];
                }

                $migrations[$version] = array("description" => $description,
                                              "up"          => $up,
                                              "down"        => $down,
                                              "checksum"    => sha1($contents));

            }
        }
        return $migrations;
    }

    protected function createVersionTable()
    {
        $pdo = $this->getPdo();
        $sql = "CREATE TABLE IF NOT EXISTS `yak_version` (
                    `version` int(11) NOT NULL DEFAULT '0',
                    `description` varchar(64) NOT NULL,
                    `checksum` char(40) NOT NULL,
                    `date_applied` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`version`)
                )";
        $stmt = $pdo->query($sql);
        if ($stmt) {
            $stmt->closeCursor();
        }
    }

    protected function getCurrentVersion()
    {
        $this->createVersionTable();
        $pdo = $this->getPdo();
        $sql = "SELECT MAX(version) AS version FROM yak_version";
        $stmt = $pdo->query($sql);
        $version = $stmt->fetchColumn();
        return $version ?: 0;
    }

    public function getEnvironment()
    {
        return getenv('APPLICATION_ENV') ?: 'development';
    }
}
