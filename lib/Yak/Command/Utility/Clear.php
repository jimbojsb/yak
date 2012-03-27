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
             ->setDescription('completely removes all tables in your database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Clearing your database...</info>');
        $pdo = $this->getConnection();
        $pdo->query("SET FOREIGN_KEY_CHECKS=0");
        $stmt = $pdo->query("SHOW TABLES;");
        while ($table = $stmt->fetchColumn()) {
            $output->writeln("<info>Dropping table $table</info>");
            $pdo->query("DROP TABLE $table");
        }
        $pdo->query("SET FOREIGN_KEY_CHECKS=1");
    }
}
