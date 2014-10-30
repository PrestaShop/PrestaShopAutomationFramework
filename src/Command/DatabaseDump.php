<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use PrestaShop\PSTAF\OptionProvider;
use PrestaShop\PSTAF\ShopManager;
use PrestaShop\PSTAF\SeleniumManager;

class DatabaseDump extends Command
{
    protected function configure()
    {
        $this
        ->setName('db:dump')
        ->addArgument('fileName', InputArgument::OPTIONAL, 'Optional filename for the SQL dump.', 'pstaf.database.sql')
        ->setDescription('Dumps the database of this shop.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shop = ShopManager::getInstance()->getShop([
            'temporary' => false,
            'use_cache' => false,
            'overwrite' => false
        ]);

        $dumpFileName = $input->getArgument('fileName');

        $shop->getDatabaseManager()->dumpTo($dumpFileName);
    }
}
