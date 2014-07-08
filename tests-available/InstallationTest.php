<?php

class InstallationTest extends \PrestaShop\TestCase\TestCase
{
	public function languageAndCountryPairs()
	{
		return [
			['ca', 'es'], ['ca', 'fr'],
			['de', 'de'], ['de', 'fr'],
			['en', 'us'], ['en', 'de'],
			['es', 'es'], ['es', 'it'],
			['fr', 'fr'], ['fr', 'fr'],
			['id', 'id'], ['id', 'de'],
			['it', 'it'], ['it', 'cz'],
			['hu', 'hu'], ['hu', 'us'],
			['nl', 'nl'], ['nl', 'fr'],
			['no', 'no'], ['no', 'rs'],
			['pl', 'pl'], ['pl', 'pt'],
			['br', 'br'], ['br', 'sk'],
			['ro', 'ro'], ['ro', 'it'],
			['sr', 'rs'], ['sr', 'fr'],
			['tr', 'tr'], ['tr', 'gr'],
			['cs', 'cz'], ['cs', 'gr'],
			['ru', 'ru'], ['ru', 'nl'],
			['mk', 'mk'], ['mk', 'de'],
			['fa', 'ir'], ['fa', 'fr'],
			['bn', 'bd'], ['bn', 'id'],
			['tw', 'tw'], ['tw', 'us'],
			['zh', 'cn'], ['zh', 'us']
		];
	}

	/**
	* @dataProvider languageAndCountryPairs
	*/
	public function testInstallationForLanguageAndCountry($language, $country)
	{
		static::getShop()->getInstaller()->install(['language' => $language, 'country' => $country]);
		static::getShop()->getBackOfficeNavigator()->login();
	}
}
