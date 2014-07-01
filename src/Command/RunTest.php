<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use PrestaShop\FSHelper;

class RunTest extends Command
{
	protected function configure()
	{
		$this->setName('test:run')
		->setDescription('Runs a test');

		$this->addArgument('test_name', InputArgument::OPTIONAL, 'Which test do you want to run?');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// TODO: windows
		$test_name = $input->getArgument('test_name');
		$class_path = realpath(__DIR__.'/../../tests-available/'.$test_name.'Test.php');

		$phpunit_path = realpath(__DIR__.'/../../vendor/bin/phpunit');

		pcntl_exec($phpunit_path, [$class_path]);
	}
}
