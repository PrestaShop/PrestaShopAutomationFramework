<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PrestaShop\PSTAF\ShopManager;

class ProjectClean extends Command
{
    protected function configure()
    {
        $this->setName('project:clean')
        ->setDescription('Clears the project\'s cache and cleans up stuff.')
        ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update the repository files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = ShopManager::getInstance();
        $manager->cleanDirectory();
        if ($input->getOption('update'))
            $manager->updateRepo();
    }
}
