<?php
namespace Yak;
use \Symfony\Component\Console\Application,
    \Symfony\Component\Console\Input\InputInterface,
    \Symfony\Component\Console\Output\OutputInterface;
class Yak extends Application
{
    protected $config;
    protected $input;

    public function __construct()
    {
        parent::__construct('Yak Database Migrations', '0.4.5');

        $this->addCommands(
            array(
                new Command\Migration\Up(),
                new Command\Migration\Down(),
                new Command\Utility\Clear(),
                new Command\Utility\Execute(),
                new Command\DataTransfer\Sync(),
                new Command\DataTransfer\Transfer(),
                new Command\UpdateYak()
            )
        );
    }
}



