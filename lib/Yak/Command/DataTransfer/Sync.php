<?php
namespace Yak\Command\DataTransfer;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Sync extends AbstractDataTransfer
{
    protected function configure()
    {
        $this->setName('sync')
             ->setDescription('transfer data from one database to another')
             ->addArgument('transfer_config', InputArgument::REQUIRED, 'config file that describes how to transfer the data')
             ->addArgument('source_connection', InputArgument::REQUIRED, 'connection to use for data source')
             ->addArgument('target_connection', InputArgument::REQUIRED, 'connection to write the data to');
    }
}