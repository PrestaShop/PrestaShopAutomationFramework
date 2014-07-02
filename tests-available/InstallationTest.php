<?php

require_once __DIR__.'/../vendor/autoload.php';

class InstallationTest extends \PrestaShop\TestCase
{
	public function seed()
	{
		return [
			['fr', 'fr'],
			['fr', 'de']
		];
	}

	public function execute($language, $country)
	{
		$this->shop->getInstaller()->install(['language' => $language, 'country' => $country]);
	}
}
