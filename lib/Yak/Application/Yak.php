<?php
namespace Yak\Application;
use \Symfony\Component\Console\Application;
class Yak extends Application
{
    protected $config;
    public function __construct()
    {
        parent::__construct('Yak Database Migrations', '0.2');
        $this->addCommands(
            array(
                new \Yak\Command\Up(),
                new \Yak\Command\Clear(),
                new \Yak\Command\Transfer(),
                new \Yak\Command\Execute(),
                new \Yak\Command\Down()
            )
        );
    }
}



