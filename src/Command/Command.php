<?php

namespace PrestaShop\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Command extends \Symfony\Component\Console\Command\Command
{
	private $options = [];
	private $questions = [];

	public function addQuoption($name, $shortcut, $mode, $description, $default = null, $completions = [], $prompt = null) {
		$this->addOption($name, $shortcut, $mode, $description);

		if (!$prompt)
			$prompt = $description;

		if ($default)
			$prompt = sprintf('%1$s (default = %2$s): ', $prompt, $default);
		else
			$prompt = sprintf('%1$s : ', $prompt);

		if ($default)
			$completions[] = $default;

		$question = new Question($prompt, $default);
		$question->setAutocompleterValues($completions);

		$this->options[$name] = $default;
		$this->questions[$name] = $question;

		return $this;
	}

	public function quoptparse($input, $output, $guessed = [], $accept_defaults = false)
	{
		$helper = $this->getHelperSet()->get('question');

		foreach ($this->options as $name => $unused)
		{
			$value = $input->getOption($name);

			if (!$value && isset($guessed[$name]))
				$value = $guessed[$name];

			if (!$value && !$accept_defaults)
				$value = $helper->ask($input, $output, $this->questions[$name]);
				
			if ($value)
				$this->options[$name] = $value;
		}
	}

	public function getOptions(array $whitelist = null, array $blacklist = null)
	{
		$options = [];
		foreach ($this->options as $key => $value)
		{
			if ($whitelist !== null)
			{
				if (!in_array($key, $whitelist))
					continue;
			}
			if ($blacklist !== null)
			{
				if (in_array($key, $blacklist))
					continue;
			}
			$options[$key] = $value;
		}
		return $options;
	}
}
