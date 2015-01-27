<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use PrestaShop\PSTAF\Helper\FileSystem as FS;
use PrestaShop\PSTAF\SeleniumManager;

class RunTest extends Command
{
    protected function configure()
    {
        $this->setName('test:run')
        ->setDescription('Runs a test');
        $this->addArgument('test_name', InputArgument::OPTIONAL, 'Which test do you want to run?');
        $this->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'Parallelize tests: max number of parallel processes.');
        $this->addOption('runner', 'r', InputOption::VALUE_REQUIRED, 'Test runner to use: phpunit, paratest, ptest or ftr.', 'ftr');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Run all available tests.');
        $this->addOption('domain', 'd', InputOption::VALUE_REQUIRED, 'Specify test domain.', 'FunctionalTest');
        $this->addOption('info', 'i', InputOption::VALUE_NONE, 'Make a dry run: display information but do not perform tests.');
        $this->addOption('no-screenshots', 'x', InputOption::VALUE_NONE, 'Disable screenshots (if the runner supports them).');
        $this->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter tests.');
        $this->addOption('data-provider-filter', 'z', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter datasets returned by the dataProviders');
        $this->addOption('inplace', 'I', InputOption::VALUE_NONE, 'Run tests against the current shop, when its source dir and target dir are the same.');
        $this->addOption('headless', 'H', InputOption::VALUE_NONE, 'Run tests headlessly (needs Xvfb).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!SeleniumManager::isSeleniumStarted()) {
            SeleniumManager::spawnSelenium($input->getOption('headless'));
        }

        $runners = ['phpunit', 'paratest', 'ptest', 'ftr'];

        $parallel = max(1, (int) $input->getOption('parallel'));

        $runner = $input->getOption('runner');

        if (!in_array($runner, $runners)) {
            $output->writeln('<error>Unsupported runner! Available runners are: phpunit, paratest and ptsest.</error>');

            return;
        }

        if ($parallel > 1 && $runner === 'phpunit') {
            $output->writeln('<error>The PHPUnit runner can\'t run tests in parallel.</error>');

            return;
        }

        $info = $input->getOption('info');
        if ($info && $runner !== 'ptest' && $runner !== 'ftr') {
            $output->writeln('<error>The info option is only supported by the ptest runner.</error>');

            return;
        }

        $runner_path = realpath(FS::join(__DIR__, '/../../vendor/bin/', $runner));

        if (!$runner_path) {
            $output->writeln(sprintf('<error>Could not find runner executable (%s). Did you run composer install?</error>', $runner));

            return;
        }

        if ($input->getArgument('test_name') && $input->getOption('all')) {
            $output->writeln(sprintf('<error>Cannot specify both a test name / folder and the "all" option.</error>'));

            return;
        }

        $domain = $input->getOption('domain');

        $tests_directory = realpath(FS::join(__DIR__, '..', '..', $domain));
        if (!$tests_directory) {
            $output->writeln('<error>Couldn\'t find directory containing tests.</error>');

            return;
        }

        if ($input->getOption('all')) {
            $tests_path = $tests_directory;
        } elseif (($test_name = $input->getArgument('test_name')) && !preg_match('/\.php$/', $test_name)) {
            $tests_path = realpath(FS::join($tests_directory, $test_name.'Test.php'));
        } elseif ($test_name) {
            $tests_path = realpath($test_name);
        } else {
            $rdi = new \RecursiveDirectoryIterator(
                $tests_directory,
                \RecursiveDirectoryIterator::SKIP_DOTS
            );
            $rii = new \RecursiveIteratorIterator($rdi);

            $tests = [];

            foreach ($rii as $path => $info) {
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

        if (!$tests_path) {
            $output->writeln('<error>Could not find requested test.</error>');
        }

        $arguments = [];
        $options = [];

        if ($runner === 'ptest' || $runner === 'ftr')
            $arguments[] = 'run';

        if ($info)
            $options['-i'] = '';

        $arguments[] = $tests_path;

        if ($runner === 'ptest' || $runner === 'paratest' || $runner === 'ftr')
            $options['-p'] = $parallel;

        $filter = $input->getOption('filter');
        if ($filter)
            $options['--filter'] = $filter;

        $z = $input->getOption('data-provider-filter');
        if (!empty($z)) {
            foreach ($z as $opt) {
                $options['--data-provider-filter'] = $opt;
                if ($runner === 'ftr') {
                    break; // this one supports only one filter
                }
            }
        }

        $process = new \djfm\Process\Process(
            $runner_path,
            $arguments,
            $options,
            ['wait' => true]
        );

        $process->setEnv('SELENIUM_HOST', SeleniumManager::getHost());

        if ($input->getOption('inplace')) {
            echo "-----------------------------------------------------\n";
            echo "-    Warning, running test inplace (as asked).      -\n";
            echo "- Initial states will not be changed, nor restored. -\n";
            echo "-----------------------------------------------------\n";
            echo "\n";
            $process->setEnv('PSTAF_INPLACE', 1);
        }

        if ($input->getOption('no-screenshots')) {
            $process->setEnv('NO_SCREENSHOTS', 1);
        }

        $process->run(STDIN, STDOUT, STDERR);
    }
}
