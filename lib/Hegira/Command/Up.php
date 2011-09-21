<?php
namespace Hegira\Command;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
class Up extends Base
{
    protected function configure()
    {
        $this->setName('up')
             ->setDescription('upgrades your database schema to the latest version available');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createVersionTable();
        $migrations = $this->getMigrations();

        $currentVersion = $this->getCurrentVersion();
        $output->writeln("<info>Current version is: $currentVersion</info>");

        $versionNumbers = array_keys($migrations);
        $maxVersion = max($versionNumbers);
        $output->writeln("<info>Max version is: $maxVersion</info>");

        if ($maxVersion == $currentVersion) {
            $output->writeln("<info>Nothing to do.</info>");
        } else {
            $pdo = $this->getPdo();
            for ($c = $currentVersion + 1; $c <= $maxVersion; $c++) {
                $data = $migrations[$c];
                $stmt = $pdo->query($data['up']);
                if ($stmt) {
                    $stmt->closeCursor();
                    unset($stmt);
                    $checksum = $data['checksum'];
                    $description = $data['description'];
                    $output->writeln("<info>Applying $c: $description...</info>");
                    $date = date("YmdHis");
                    $sql = "INSERT INTO hegira_version
                            VALUES ('$c', '$description', '$checksum', '$date')";
                    $stmt = $pdo->query($sql);
                    if ($stmt) {
                        $stmt->closeCursor();
                    }
                }
            }
        }
    }
}
