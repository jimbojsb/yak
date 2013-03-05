<?php
namespace Yak\Command\Migration;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Yak\Command\AbstractCommand;

abstract class MigrationAbstract extends AbstractCommand
{
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

                $migrationParts = preg_split('/-- (up|down)/s', $contents);
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
        $pdo = $this->getConnection();
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
        $pdo = $this->getConnection();
        $sql = "SELECT MAX(version) AS version FROM yak_version";
        $stmt = $pdo->query($sql);
        $version = $stmt->fetchColumn();
        return $version ?: 0;
    }
}