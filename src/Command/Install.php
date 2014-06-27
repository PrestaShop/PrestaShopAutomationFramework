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
		->addArgument('language', InputArgument::OPTIONAL, 'Which install language do you want to use?');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

	}
}
