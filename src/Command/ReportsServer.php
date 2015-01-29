<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use PrestaShop\PSTAF\Helper\FileSystem as FS;

class ReportsServer extends Command
{
    protected function configure()
    {
        $this
        ->setName('reports:server')
        ->setDescription('Start a reporting server in the current directory.')
        ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Which port should the server run on?', 3000)
        ;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reporterRoot = FS::join(__DIR__, '..', '..', 'vendor', 'djfm', 'ftr', 'reporting');

        $output->writeln('<info>Updating reporting tool if needed... (npm install)</info>');
        $dir = getcwd();
        chdir($reporterRoot);
        passthru('npm install');
        chdir($dir);

        $output->writeln("<info>And start!</info>\n");

        $reporterPath = realpath(FS::join($reporterRoot, 'server', 'index.js'));

        if (!$reporterPath) {
            $output->writeLn('<error>Report server not found.</error>');
            return;
        }

        $command = implode(' ', array_map('escapeshellarg', ['node', $reporterPath, '--port=' . $input->getOption('port')]));

        passthru($command);
    }
}
