<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PrestaShop\PSTAF\OptionProvider;

class Install extends Command
{
    protected function configure()
    {
        $this->setName('shop:install')
        ->setDescription('Install PrestaShop');

        $optionDescriptions = OptionProvider::getDescriptions('ShopInstallation');

        foreach ($optionDescriptions as $name => $data) {
            $this->addOption($name, $data['short'], $data['type'], $data['description'], $data['default']);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \PrestaShop\SeleniumManager::ensureSeleniumIsRunning();

        $shop = \PrestaShop\ShopManager::getInstance()->getShop([
            'temporary' => false,
            'use_cache' => false,
            'overwrite' => true
        ]);

        $shop->getInstaller()->install(OptionProvider::fromInput('ShopInstallation', $input));
        $shop->getBrowser()->quit();
    }
}
