<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use PrestaShop\Helper\FileSystem as FSHelper;

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
		// TODO: windows?
		$test_name = $input->getArgument('test_name');

		if (!$test_name)
		{
			$tests = [];
			foreach (scandir(__DIR__.'/../../tests-available/') as $entry)
			{
				$m = [];
				if (preg_match('/^(\w+?)Test\.php$/', $entry, $m))
					$tests[] = $m[1];
			}

			$question = new Question('Which test do you want to run? ');
			$question->setAutocompleterValues($tests);
			$helper = $this->getHelperSet()->get('question');
			$test_name = $helper->ask($input, $output, $question);
		}

		$class_path = realpath(__DIR__.'/../../tests-available/'.$test_name.'Test.php');
		$phpunit_xml_path = realpath(__DIR__.'/../../tests-available/phpunit.xml');
		$phpunit_path = realpath(__DIR__.'/../../vendor/bin/phpunit');

		if ($class_path && $phpunit_path && $phpunit_xml_path)
			pcntl_exec($phpunit_path, ['-c', $phpunit_xml_path, $class_path]);
		else
			$output->writeln('<error>Could not find either test case or phphunit itself.</error>');
	}
}
