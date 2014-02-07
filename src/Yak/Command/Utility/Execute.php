<?php
namespace Yak\Command\Utility;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

use Yak\SqlString;

class Execute extends UtilityAbstract
{
    protected function configure()
    {
        $this->setName('execute')
             ->setDescription('executes a single SQL script or a folder full of scripts')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to SQL script or directory')
             ->addOption('continue', null, InputOption::VALUE_NONE, 'Continue executing queries even if one fails')
             ->addOption('raw', null, InputOption::VALUE_NONE, "Don't try and parse the contents of the file into multiple queries");
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
            if ($input->getOption('raw')) {
                $queries = array(file_get_contents($file));
            } else {
                $sql = SqlString::fromFile($file);
                $queries = $sql->getQueries();
            }

            $output->write("<info>Executing $file</info>");
            foreach ($queries as $query) {
                try {
                    $result = $pdo->query($query);
                    $result->closeCursor();
                    $output->write("<info>.</info>");
                } catch (\PDOException $e) {
                    $output->writeln("");
                    $output->writeln("<error>Encountered an error running $file</error>");
                    $output->writeln("<error>$query</error>");
                    $output->writeln("<error>" . $e->getMessage() . "</error>");
                    if (!$input->getOption('continue')) {
                        throw new \Exception("Halting due to SQL errors. To ignore, re-run with --continue flag");
                    }
                }
            }
            $output->writeln('<info>  done.</info>');
        }
    }
}
