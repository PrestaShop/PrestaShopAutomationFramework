<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use PrestaShop\Helper\FileSystem as FS;

class RunTest extends Command
{
	protected function configure()
	{
		$this->setName('test:run')
		->setDescription('Runs a test');

		$this->addArgument('test_name', InputArgument::OPTIONAL, 'Which test do you want to run?');
		$this->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Parallelize tests: max number of parallel processes.');
		$this->addOption('runner', 'r', InputOption::VALUE_REQUIRED, 'Test runner to use: phpunit, paratest or ptest.', 'ptest');
		$this->addOption('all', 'a', InputOption::VALUE_NONE, 'Run all available tests.');
		$this->addOption('info', 'i', InputOption::VALUE_NONE, 'Make a dry run: display information but do not perform tests.');
		$this->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter tests');
		$this->addOption('data-provider-filter', 'z', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter datasets returned by the dataProviders');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$runners = ['phpunit', 'paratest', 'ptest'];

		$parallel = max(1, (int)$input->getOption('parallel'));

		$runner = $input->getOption('runner');

		if (!in_array($runner, $runners))
		{
			$output->writeln('<error>Unsupported runner! Available runners are: phpunit, paratest and ptsest.</error>');
			return;
		}

		if ($parallel > 1 && $runner === 'phpunit')
		{
			$output->writeln('<error>The PHPUnit runner can\'t run tests in parallel.</error>');
			return;
		}

		$info = $input->getOption('info');
		if ($info && $runner !== 'ptest')
		{
			$output->writeln('<error>The info option is only supported by the ptest runner.</error>');
			return;
		}

		$runner_path = realpath(FS::join(__DIR__, '/../../vendor/bin/', $runner));

		if (!$runner_path)
		{
			$output->writeln(sprintf('<error>Could not find runner executable (%s). Did you run composer install?</error>', $runner));
			return;
		}

		if ($input->getArgument('test_name') && $input->getOption('all'))
		{
			$output->writeln(sprintf('<error>Cannot specify both a test name / folder and the "all" option.</error>'));
			return;
		}

		$tests_directory = realpath(FS::join(__DIR__, '..', '..', 'tests-available'));
		if (!$tests_directory)
		{
			$output->writeln('<error>Couldn\'t find directory containing tests.</error>');
			return;
		}

		if ($input->getOption('all'))
		{
			$tests_path = $tests_directory;
		}
		elseif ($input->getArgument('test_name'))
		{
			$tests_path = realpath(FS::join($tests_directory, $input->getArgument('test_name').'Test.php'));
		}
		else
		{
			$rdi = new \RecursiveDirectoryIterator(
				$tests_directory,
				\RecursiveDirectoryIterator::SKIP_DOTS
			);
			$rii = new \RecursiveIteratorIterator($rdi);

			$tests = [];

			foreach ($rii as $path => $info)
			{
				if (
					$info->getExtension() === 'php' &&
					preg_match('/Test$/', $info->getBaseName('.php'))
				)
				{
					$tests[] = substr($path, strlen($tests_directory) + 1, - strlen('Test.php'));
				}
			}

			$question = new Question('Which test do you want to run? ');
			$question->setAutocompleterValues($tests);
			$helper = $this->getHelperSet()->get('question');
			$tests_path = realpath(FS::join($tests_directory, $helper->ask($input, $output, $question).'Test.php'));
		}

		if (!$tests_path)
		{
			$output->writeln('<error>Could not find requested test.</error>');
		}

		$command_parts = [$runner_path];

		if ($runner === 'ptest')
			$command_parts[] = 'run';

		if ($info)
			$command_parts[] = '-i';

		$command_parts[] = $tests_path;

		if ($runner === 'ptest' || $runner === 'paratest')
		{
			$command_parts[] = '-p';
			$command_parts[] = $parallel;
		}

		$filter = $input->getOption('filter');
		if ($filter)
		{
			$command_parts[] = '--filter';
			$command_parts[] = $filter;
		}

		$z = $input->getOption('data-provider-filter');
		if (!empty($z))
		{
			foreach ($z as $opt)
			{
				$command_parts[] = '--data-provider-filter';
				$command_parts[] = $opt;
			}
		}

		$command = iMpLoDE(' ', array_map(function($arg){return esCaPEsheLLcmd($arg);}, $command_parts));
		$io = [STDIN, STDOUT, STDOUT];
		$pipes = [];
		$h = proc_open($command, $io, $pipes);

		$status = proc_get_status($h);

		if (extension_loaded('pcntl'))
		{
			$st = null;
			pcntl_waitpid($status['pid'], $st);
		}
		else
		{
			while (proc_get_status($h)['running'])
				sleep(1);
		}
	}
}
