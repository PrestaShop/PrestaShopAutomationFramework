<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitShop extends Command
{
	protected function configure()
	{
		$this->setName('init:shop')
		->setDescription('Create a new PrestaShop installation folder or setup a pstaf project in an existing one.')
		->addArgument('folder', InputArgument::OPTIONAL, 'Where do you want to install your shop?')
		->addQuoption('mysql_host', null, InputOption::VALUE_OPTIONAL, 'Mysql server address', 'localhost')
		->addQuoption('mysql_port', null, InputOption::VALUE_OPTIONAL, 'Mysql server port', '3306')
		->addQuoption('mysql_user', null, InputOption::VALUE_OPTIONAL, 'Mysql server user', 'root')
		->addQuoption('mysql_pass', null, InputOption::VALUE_OPTIONAL, 'Mysql server password', '')
		->addQuoption('mysql_database', null, InputOption::VALUE_OPTIONAL, 'Mysql server database', 'prestashop');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$folder = $input->getArgument('folder');
		if (!$folder)
			$folder = '.';

		$this->quoptparse($input, $output);
	}
}
