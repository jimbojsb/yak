<?php
namespace Yak\Command\Migration;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Yak\SqlString;

class Down extends MigrationAbstract
{
    protected function configure()
    {
        $this->setName('down')
             ->setDescription('downgrades your database 1 version number')
             ->addArgument('version', InputArgument::OPTIONAL, 'set a specific version number to downgrade to')
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

        $specifiedVersion = $input->getArgument('version');
        if ($specifiedVersion) {
            $output->writeln("<info>Downgrading to: $specifiedVersion</info>");
            $downgradeVersion = $specifiedVersion;
        } else {
            $downgradeVersion = $currentVersion - 1;
            $output->writeln("<info>Downgrading to: previous ($downgradeVersion)</info>");
        }



        if ($downgradeVersion == $currentVersion) {
            $output->writeln("<info>Nothing to do.</info>");
        } else {
            $pdo = $this->getConnection();
            for ($c = $currentVersion; $c > $downgradeVersion; $c--) {
                $data = $migrations[$c];

                $sql = new SqlString($data['down']);
                $queries = $sql->getQueries();

                if ($queries) {
                    $pdo->query("SET FOREIGN_KEY_CHECKS=0");
                } else {
                    continue;
                }

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

                $sql = "DELETE FROM yak_version
                            WHERE version='$c'";
                $stmt = $pdo->query($sql);
                if ($stmt) {
                    $stmt->closeCursor();
                }

                $pdo->query("SET FOREIGN_KEY_CHECKS=1");

            }
        }
    }
}
