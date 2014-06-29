<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command
{
	protected function configure()
	{
		$this->setName('install')
		->setDescription('Install PrestaShop');

		$optionDescriptions = \PrestaShop\Action\OptionProvider::getDescriptions('ShopInstallation');

		foreach ($optionDescriptions as $name => $data)
		{
			$this->addOption($name, $data['short'], $data['type'], $data['description'], $data['default']);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$conf = \PrestaShop\ConfigurationFile::getFromCWD();
		$shop = new \PrestaShop\Shop(getcwd(), $conf->get('shop'));

		$shop->install(\PrestaShop\Action\OptionProvider::fromInput('ShopInstallation', $input));
	}
}
