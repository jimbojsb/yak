<?php
namespace Yak\Command;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
class Execute extends Base
{
    protected function configure()
    {
        $this->setName('execute')
             ->setDescription('executes a single SQL script or a folder full of scripts')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to SQL script or directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        if (!$path) {
            $path = realpath(getcwd());
        }

        $files = array();
        if (is_dir($path)) {
            $di = new \DirectoryIterator($path);
            foreach ($di as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        } else {
            $files[] = $path;
        }

        $output->writeln('<info>Found ' . count($files) . ' files to execute:</info>');
        $pdo = $this->getPdo();
        foreach ($files as $file) {
            $result = $pdo->query(file_get_contents($file));
            if (!$result) {
                $output->writeln("<error>Encountered an error running $file</error>");
                $errorMessages = $pdo->errorInfo();
                foreach ($errorMessages as $message) {
                    $output->writeln("<error>\t$message</error>");
                }

            } else {
                $result->closeCursor();
                $output->writeln('<info>Executed ' . $file . '</info>');
            }

        }
    }
}
