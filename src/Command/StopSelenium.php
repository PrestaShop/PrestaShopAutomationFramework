<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PrestaShop\FSHelper;

class StopSelenium extends Command
{
	protected function configure()
	{
		$this->setName('selenium:stop')
		->setDescription('Stops the selenium server');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		\PrestaShop\SeleniumManager::stopSelenium();
	}
}
