<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PrestaShop\OptionProvider;

class Install extends Command
{
	protected function configure()
	{
		$this->setName('shop:install')
		->setDescription('Install PrestaShop');

		$optionDescriptions = OptionProvider::getDescriptions('ShopInstallation');

		foreach ($optionDescriptions as $name => $data)
		{
			$this->addOption($name, $data['short'], $data['type'], $data['description'], $data['default']);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		\PrestaShop\SeleniumManager::ensureSeleniumIsRunning();

		$shop = \PrestaShop\ShopManager::getShop();

		$shop->getInstaller()->install(OptionProvider::fromInput('ShopInstallation', $input));
		$shop->getBrowser()->quit();
	}
}
