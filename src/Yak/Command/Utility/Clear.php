<?php
namespace Yak\Command\Utility;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Clear extends UtilityAbstract
{
    protected function configure()
    {
        $this->setName('clear')
             ->setDescription('completely removes all tables, functions, procedures, and views from your database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateTargets();
        $output->writeln('<info>Clearing your database...</info>');
        $pdo = $this->getConnection();
        $pdo->query("SET FOREIGN_KEY_CHECKS=0");
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'");
        while ($table = $stmt->fetchColumn()) {
            $output->writeln("<info>Dropping table $table</info>");
            $pdo->query("DROP TABLE $table");
        }

        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type='VIEW'");
        while ($view = $stmt->fetchColumn()) {
            $output->writeln("<info>Dropping view $view</info>");
            $pdo->query("DROP VIEW $view");
        }

        $stmt = $pdo->query("SELECT SPECIFIC_NAME FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'FUNCTION' AND ROUTINE_SCHEMA = DATABASE()");
        while ($function = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $output->writeln("<info>Dropping function " . $function["SPECIFIC_NAME"] . "</info>");
            $pdo->query("DROP FUNCTION " . $function["SPECIFIC_NAME"]);
        }

        $stmt = $pdo->query("SELECT SPECIFIC_NAME FROM INFORMATION_SCHEMA.ROUTINES WHERE ROUTINE_TYPE = 'PROCEDURE' AND ROUTINE_SCHEMA = DATABASE()");
        while ($function = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $output->writeln("<info>Dropping procedure " . $function["SPECIFIC_NAME"] . "</info>");
            $pdo->query("DROP PROCEDURE " . $function["SPECIFIC_NAME"]);
        }

        $pdo->query("SET FOREIGN_KEY_CHECKS=1");
    }
}
