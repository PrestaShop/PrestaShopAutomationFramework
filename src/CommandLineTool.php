<?php

namespace PrestaShop;

use Symfony\Component\Console\Application;

class CommandLineTool extends Application
{
	public function __construct()
	{
		parent::__construct();

		$this->add(new Command\InitShop());
		$this->add(new Command\Install());
		$this->add(new Command\StartSelenium());
		$this->add(new Command\StopSelenium());
		$this->add(new Command\RestartSelenium());
		$this->add(new Command\RunTest());
		$this->add(new Command\ShopAddOrUpdateLanguage());
	}
}
