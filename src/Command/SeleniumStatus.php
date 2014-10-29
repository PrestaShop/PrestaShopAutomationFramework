<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PrestaShop\PSTAF\SeleniumManager as Selenium;

class SeleniumStatus extends Command
{
    protected function configure()
    {
        $this->setName('selenium:status')
        ->setDescription('Is selenium started?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($data = Selenium::started(true))) {
        	$output->writeln(
        		'<fg=green>Selenium is running (pidFile: '.$data['pidFile'].')</fg=green>'
        	);
        	if (realpath($data['pidDirectory']) !== realpath('.')) {
        		$output->writeln('<comment>Please note that the pid file is located in a higher directory, not your CWD.</comment>');
        	}
        } else {
        	$output->writeln('<fg=red>Selenium is not running!</fg=red>');
        }
    }
}
