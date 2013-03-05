<?php
namespace Yak\Command\Migration;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Yak\SqlString;

class Up extends MigrationAbstract
{
    protected function configure()
    {
        $this->setName('up')
             ->setDescription('upgrades your database schema to the latest version available')
             ->addArgument('version', InputArgument::OPTIONAL, 'set a specific version number to upgrade to')
             ->addOption('continue', null, InputOption::VALUE_NONE, 'Continue executing queries even if one fails');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createVersionTable();
        $migrations = $this->getMigrations();
        if (!$migrations) {
            $output->writeln('No migration files found.');
            return;
        }

        $currentVersion = $this->getCurrentVersion();
        $output->writeln("<info>Current version is: $currentVersion</info>");

        $versionNumbers = array_keys($migrations);
        $specifiedVersion = $input->getArgument('version');
        if ($specifiedVersion) {
            $output->writeln("<info>Upgrading to: $specifiedVersion</info>");
            $upgradeVersion = $specifiedVersion;
        } else {
            $maxVersion = max($versionNumbers);
            $output->writeln("<info>Upgrading to: latest ($maxVersion)</info>");
            $upgradeVersion = $maxVersion;
        }



        if ($upgradeVersion == $currentVersion) {
            $output->writeln("<info>Nothing to do.</info>");
        } else {
            $pdo = $this->getConnection();
            for ($c = $currentVersion + 1; $c <= $upgradeVersion; $c++) {

                $data = $migrations[$c];
                $checksum = $data['checksum'];
                $description = $data['description'];
                $output->writeln("<info>Applying $c: $description...</info>");

                $sql = new SqlString($data['up']);
                $queries = $sql->getQueries();

                foreach ($queries as $query) {
                    try {
                        $stmt = $pdo->query($query);
                        $stmt->closeCursor();
                        unset($stmt);
                    } catch (\PDOException $e) {
                        if (!$input->getOption('continue')) {
                            throw $e;
                        }
                    }

                }

                $date = date("YmdHis");
                $sql = "INSERT INTO yak_version
                        VALUES ('$c', '$description', '$checksum', '$date')";
                $stmt = $pdo->query($sql);
                if ($stmt) {
                    $stmt->closeCursor();
                }
            }
        }
    }
}
