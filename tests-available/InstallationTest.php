<?php

require_once __DIR__.'/../vendor/autoload.php';

class InstallationTest extends \PrestaShop\TestCase\TestCase
{
	public function languageAndCountryPairs()
	{
		return [
			['fr', 'fr'],
			['fr', 'de']
		];
	}

	/**
	* @dataProvider languageAndCountryPairs
	*/
	public function testInstallationForLanguageAndCountry($language, $country)
	{
		$this->shop->getInstaller()->install(['language' => $language, 'country' => $country]);
	}
}
