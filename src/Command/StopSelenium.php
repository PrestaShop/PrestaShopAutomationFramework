<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PrestaShop\SeleniumManager as Selenium;

class StopSelenium extends Command
{
    protected function configure()
    {
        $this->setName('selenium:stop')
        ->setDescription('Stops the selenium server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // \PrestaShop\SeleniumManager::stopSelenium();

        if (Selenium::startedInCWD()) {
        	Selenium::stop(false);
        } elseif (Selenium::startedInHigherDirectory()){
        	$helper = $this->getHelper('question');
        	$question = new ConfirmationQuestion('Selenium was started in a higher directory, do you really want to stop it? (y/N) ', false);
        	if ($helper->ask($input, $output, $question)) {
        		Selenium::stop(true);
        	}
        } else {
        	$output->writeln("<comment>Selenium doesn't seem to be running.</comment>");
        }
    }
}
