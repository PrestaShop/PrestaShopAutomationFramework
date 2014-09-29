<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PrestaShop\Helper\FileSystem as FSHelper;

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
		$manager = \PrestaShop\ShopManager::getInstance();
		$manager->cleanDirectory();
		if ($input->getOption('update'))
			$manager->updateRepo();
	}
}
