<?php
namespace Yak\Application;
use \Symfony\Component\Console\Application,
    \Symfony\Component\Console\Input\InputInterface,
    \Symfony\Component\Console\Output\OutputInterface;
class Yak extends Application
{
    protected $config;
    protected $input;

    public function __construct()
    {
        parent::__construct('Yak Database Migrations', '0.4.9');
        $this->addCommands(
            array(
                new \Yak\Command\Migration\Up(),
                new \Yak\Command\Migration\Down(),
                new \Yak\Command\Utility\Clear(),
                new \Yak\Command\Utility\Execute(),
                new \Yak\Command\DataTransfer\Transfer(),
                new \Yak\Command\UpdateYak()
            )
        );
    }
}



