<?php
namespace Yak\Command;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class UpdateYak extends Command
{
    protected function configure()
    {
        $this->setName('update-yak')
             ->setDescription('attempt to download the latest copy of yak and upgrade-in-place');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $downloads = json_decode(
            file_get_contents(
                'https://api.github.com/repos/jimbojsb/yak/downloads'
            ),
            true
        );
        $currentVersion = $this->getApplication()->getVersion();

        $versions = array();
        foreach ($downloads as $download) {
            preg_match("/\d\.\d\.?\d?/", $download["name"], $matches);
            if ($matches[0]) {
                $versions[$matches[0]] = $download["html_url"];
            }
        }
        $versionNumbers = array_keys($versions);
        sort($versionNumbers);
        $maxAvailableVersion = array_pop($versionNumbers);
        if ($maxAvailableVersion > $currentVersion) {
            $output->writeln("<info>Upgrading from $currentVersion to $maxAvailableVersion</info>");
            copy($versions[$maxAvailableVersion], dirname($_SERVER['SCRIPT_NAME']) . DIRECTORY_SEPARATOR . 'yak');
            chmod(dirname($_SERVER['SCRIPT_NAME']) . DIRECTORY_SEPARATOR . 'yak', 0755);
        }
    }
}
