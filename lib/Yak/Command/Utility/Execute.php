<?php
namespace Yak\Command\Utility;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Execute extends UtilityAbstract
{
    protected function configure()
    {
        $this->setName('execute')
             ->setDescription('executes a single SQL script or a folder full of scripts')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to SQL script or directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateTargets();
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

        // detect if the files are numbered
        $numbered = true;
        $numberedFiles = array();
        array_walk($files, function($item) use (&$numbered, &$numberedFiles) {
            $hasLeadingNumber = preg_match('`.*?([0-9]+)\.?(.*?)\.sql`', $item, $matches);
            if ($hasLeadingNumber) {
                $numberedFiles[$matches[1]] = $item;
            } else {
                $numbered = false;
            }
        });

        $output->writeln('<info>Found ' . count($files) . ' files to execute:</info>');
        if ($numbered) {
            $output->writeln("<info>These files look numbered, attempting to run them in order...</info>");
            ksort($numberedFiles);
            $files = array_values($numberedFiles);
        } else {
            sort($files);
        }



        $pdo = $this->getConnection();
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
