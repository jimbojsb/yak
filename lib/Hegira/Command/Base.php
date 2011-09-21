<?php
namespace Hegira\Command;
use Symfony\Component\Console\Command\Command;
class Base extends Command
{
    protected $pdo;
    protected $config;

    protected function getConfig()
    {
        if ($this->config) {
            return $this->config;
        }
        if (file_exists('hegira_config.php')) {
            $config = include('hegira_config.php');
            $this->config = $config[$this->getEnvironment()];;
            return $this->config;
        } else {
            throw new \Exception('hegira_config.php not found in current path');
        }
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
        $d = new \DirectoryIterator($dir);
        foreach ($d as $file) {
            if (!$d->isDir() && !$d->isDot()) {
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
                return $migrations;
            }
        }
    }

    protected function createVersionTable()
    {
        $pdo = $this->getPdo();
        $sql = "CREATE TABLE `hegira_version` (
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
        $sql = "SELECT MAX(version) AS version FROM hegira_version";
        $stmt = $pdo->query($sql);
        $version = $stmt->fetchColumn();
        return $version ?: 0;
    }

    public function getEnvironment()
    {
        return getenv('APPLICATION_ENV') ?: 'development';
    }
}
