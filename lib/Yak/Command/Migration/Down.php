<?php
namespace Yak\Command\Migration;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Down extends MigrationAbstract
{
    protected function configure()
    {
        $this->setName('down')
             ->setDescription('downgrades your database 1 version number')
             ->addArgument('version', InputArgument::OPTIONAL, 'set a specific version number to downgrade to');
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
                $pdo->query("SET FOREIGN_KEY_CHECKS=0");
                $stmt = $pdo->query($data['down']);
                if ($stmt) {
                    unset($stmt);
                    $sql = "DELETE FROM yak_version
                            WHERE version='$c'";
                    $stmt = $pdo->query($sql);
                    if ($stmt) {
                        $stmt->closeCursor();
                    }
                } else {
                    $output->writeln('<error>Tried to run your down command, but nothing happened...</error>');
                }
                $pdo->query("SET FOREIGN_KEY_CHECKS=1");

            }
        }
    }
}
