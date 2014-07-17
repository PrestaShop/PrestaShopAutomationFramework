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
		$this->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Parallelize tests');
		$this->addOption('paratest', null, InputOption::VALUE_NONE, 'Use paratest for parallelization');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// TODO: windows?
		$test_name = $input->getArgument('test_name');
		$parallel = $input->getOption('parallel');

		if (!$test_name)
		{
			$tests = ['all'];
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

		if (!$test_name)
			$test_name = 'all';

		$phpunit_xml_path = realpath(__DIR__.'/../../tests-available/phpunit.xml');
		$phpunit_path = realpath(__DIR__.'/../../vendor/bin/phpunit');

		if ($test_name !== 'all' && !$parallel)
		{
			$class_path = realpath(__DIR__.'/../../tests-available/'.$test_name.'Test.php');

			if ($class_path && $phpunit_path && $phpunit_xml_path)
				pcntl_exec($phpunit_path, ['-c', $phpunit_xml_path, $class_path]);
			else
				$output->writeln('<error>Could not find either test case, phphunit or phpunit.xml.</error>');
		}
		elseif (!$parallel)
		{
			$tests_path = realpath(__DIR__.'/../../tests-available/');

			if ($tests_path && $phpunit_path && $phpunit_xml_path)
				pcntl_exec($phpunit_path, ['-c', $phpunit_xml_path, $tests_path]);
			else
				$output->writeln('<error>Could not find either test cases directory, phphunit or phpunit.xml.</error>');
		}
		elseif ($input->getOption('paratest'))
		{
			$tests_path = realpath(__DIR__.'/../../tests-available/');
			$paratest_path = realpath(__DIR__.'/../../vendor/bin/paratest');

			if ($tests_path && $paratest_path && $phpunit_xml_path)
				pcntl_exec($paratest_path, ['-c', $phpunit_xml_path, '-p', $parallel, $tests_path]);
			else
				$output->writeln('<error>Could not find either test cases directory, paratest or phpunit.xml.</error>');
		}
		else
		{
			$tests_path = realpath(__DIR__.'/../../tests-available/');
			$ptest_path = realpath(__DIR__.'/../../vendor/bin/ptest');

			if ($test_name !== 'all')
				$tests_path = realpath("$tests_path/{$test_name}Test.php");

			$output->writeln(sprintf('Running tests using ptest with %d processes', $parallel));

			if ($ptest_path && $tests_path)
				pcntl_exec($ptest_path, ['run', $tests_path, '-p', $parallel]);
			else
				$output->writeln('<error>Could not find either test cases directory or the ptest runner.</error>');
		}
	}
}
