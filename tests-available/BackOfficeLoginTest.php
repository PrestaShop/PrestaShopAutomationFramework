<?php

require_once __DIR__.'/../vendor/autoload.php';

class BackOfficeLoginTest extends \PrestaShop\TestCase
{
	public function execute()
	{
		$browser = $this->shop->getBrowser();
		$browser->visit($this->shop->getBackOfficeURL());
		sleep(10);
	}
}
