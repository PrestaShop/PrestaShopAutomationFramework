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
		->setDescription('Install PrestaShop')
		->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Installation language (e.g. en)');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$conf = \PrestaShop\ConfigurationFile::getFromCWD();
		$shop = new \PrestaShop\Shop(getcwd(), $conf->get('shop'));

		$shop->install();
	}
}
