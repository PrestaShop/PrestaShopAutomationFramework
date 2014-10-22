<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartSelenium extends Command
{
    protected function configure()
    {
        $this->setName('selenium:restart')
        ->setDescription('Restarts the selenium server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \PrestaShop\SeleniumManager::stopSelenium();
        \PrestaShop\SeleniumManager::startSelenium();
    }
}
