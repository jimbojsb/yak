<?php
namespace Yak;

use Symfony\Component\Console\Application as ConsoleApplication;

class Application extends ConsoleApplication
{
    protected $config;
    protected $input;

    public function __construct()
    {
        parent::__construct('Yak Database Migrations', '0.5.3');
        $this->addCommands(
            array(
                new \Yak\Command\Migration\Up(),
                new \Yak\Command\Migration\Down(),
                new \Yak\Command\Utility\Clear(),
                new \Yak\Command\Utility\Execute(),
                new \Yak\Command\DataTransfer\Transfer()
            )
        );
    }
}



