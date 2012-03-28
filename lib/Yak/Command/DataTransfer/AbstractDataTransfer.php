<?php
namespace Yak\Command\DataTransfer;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    \Yak\Command\AbstractCommand;

class AbstractDataTransfer extends AbstractCommand
{
    protected function validateTargets()
    {
        $destination = $this->input->getArgument('destination_connection');
        $destinationConfig = $this->config[$destination];
        if ($destinationConfig['readonly'] == true) {
            throw new \Exception("Cannot use a read-only target as a destination connection");
        }
    }
}