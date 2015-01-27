<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use PrestaShop\PSTAF\OptionProvider;
use PrestaShop\PSTAF\ShopManager;
use PrestaShop\PSTAF\SeleniumManager;

class Install extends Command
{
    protected function configure()
    {
        $this->setName('shop:install')
        ->setDescription('Install PrestaShop');

        try {
            $options = ShopManager::getInstance()
            ->getOptionProvider()
            ->getDefaults('ShopInstallation');
        } catch (\Exception $e) {
            // We probably did not have a configuration file.
            $options = (new OptionProvider())->getDefaults('ShopInstallation');
        }

        foreach ($options as $name => $data) {
            $this->addOption(
                $name,
                $data['short'],
                $data['type'],
                $data['description'],
                $data['type'] !== InputOption::VALUE_NONE ? $data['default'] : null
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!SeleniumManager::isSeleniumStarted()) {
            SeleniumManager::spawnSelenium();
        }

        SeleniumManager::ensureSeleniumIsRunning();

        $shop = ShopManager::getInstance()->getShop([
            'temporary' => false,
            'use_cache' => false,
            'overwrite' => true
        ]);

        $shop->getInstaller()->install(
            $shop->getOptionProvider()->getValues('ShopInstallation', $input)
        );
        $shop->getBrowser()->quit();
    }
}
