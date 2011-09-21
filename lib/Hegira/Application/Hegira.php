<?php
namespace Hegira\Application;
use \Symfony\Component\Console\Application;
class Hegira extends Application
{
    protected $config;
    public function __construct()
    {
        parent::__construct('Hegira Database Migrations', '1.0');
        $this->addCommands(array(new \Hegira\Command\Up(),
                                 new \Hegira\Command\Clear()));
    }
}



