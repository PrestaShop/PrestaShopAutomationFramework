<?php

namespace PrestaShop\PSTAF\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use PrestaShop\SeleniumManager as Selenium;

class RestartSelenium extends Command
{
    protected function configure()
    {
        $this->setName('selenium:restart')
        ->setDescription('Restarts the selenium server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (Selenium::startedInCWD()) {
                Selenium::stop(false);
                Selenium::start();
            } elseif (($data = Selenium::startedInHigherDirectory())){
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Selenium was started in a higher directory, do you really want to restart it? (y/N) ', false);
                
                if ($helper->ask($input, $output, $question)) {
                    Selenium::stop(true);
                    Selenium::start($data['pidDirectory']);
                }
        } else {
            $output->writeln("<comment>Selenium doesn't seem to be running.</comment>");
        }
    }
}
