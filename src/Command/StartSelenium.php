<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PrestaShop\PSTAF\SeleniumManager as Selenium;

class StartSelenium extends Command
{
    protected function configure()
    {
        $this->setName('selenium:start')
        ->setDescription('Starts the selenium server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (Selenium::startedInCWD()) {
        	$output->writeln("<comment>Selenium seems to be already running!</comment>");
        } elseif (Selenium::startedInHigherDirectory()){
        	$helper = $this->getHelper('question');
        	$question = new ConfirmationQuestion('Selenium was started in a higher directory, do you want to spawn another instance here? (y/N) ', false);
        	if ($helper->ask($input, $output, $question)) {
        		Selenium::start();
        	}
        } else {
        	Selenium::start();
        }
    }
}
