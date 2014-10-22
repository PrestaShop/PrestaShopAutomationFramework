<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShopAddOrUpdateLanguage extends Command
{
    protected function configure()
    {
        $this->setName('shop:language:add')
        ->setDescription('Add a language')
        ->addArgument('TwoLettersLanguageCode', InputArgument::REQUIRED, 'Which language do you want to add or update?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \PrestaShop\SeleniumManager::ensureSeleniumIsRunning();

        $shop = \PrestaShop\ShopManager::getInstance()->getShop(null, false);

        $lc = $input->getArgument('TwoLettersLanguageCode');

        $shop->getBackOfficeNavigator()->login();

        $translations = $shop->getPageObject('AdminTranslations');
        $translations->visit();
        $translations->addOrUpdateLanguage($lc);
    }
}
