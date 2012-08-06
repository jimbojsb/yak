<?php
namespace Yak\Command\Utility;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Yak\Command\AbstractCommand;

abstract class UtilityAbstract extends AbstractCommand
{
    protected function validateTargets()
    {
        $destinationConfig = $this->config[$this->getTarget()];
        if ($destinationConfig['readonly'] == true) {
            throw new \Exception("Cannot use a read-only target");
        }
    }
}