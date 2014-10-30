<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use PrestaShop\PSTAF\OptionProvider;
use PrestaShop\PSTAF\ShopManager;
use PrestaShop\PSTAF\SeleniumManager;

class DatabaseLoad extends Command
{
    protected function configure()
    {
        $this
        ->setName('db:load')
        ->addArgument('fileName', InputArgument::OPTIONAL, 'Optional filename for the SQL dump.', 'pstaf.database.sql')
        ->setDescription('Loads a database dump into this shop.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shop = ShopManager::getInstance()->getShop([
            'temporary' => false,
            'use_cache' => false,
            'overwrite' => false
        ]);

        $dumpFileName = $input->getArgument('fileName');

        if (!file_exists($dumpFileName)) {
            $output->writeln('<error>File '.$dumpFileName.' not found!</error>');
            return;
        }

        $shop->getDatabaseManager()->loadDump($dumpFileName);
    }
}
