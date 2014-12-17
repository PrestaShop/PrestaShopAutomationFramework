<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\TestCase;

class InstallationTest extends TestCase
{
    public static function initialState()
    {
        return null;
    }

    public function languageAndCountryPairs()
    {
        return [
            ['ca', 'es'], ['ca', 'fr'],
            ['de', 'de'], ['de', 'fr'],
            ['en', 'us'], ['en', 'de'],
            ['es', 'es'], ['es', 'it'],
            ['fr', 'fr'], ['fr', 'de'],
            ['id', 'id'], ['id', 'de'],
            ['it', 'it'], ['it', 'cz'],
            ['hu', 'hu'], ['hu', 'us'],
            ['nl', 'nl'], ['nl', 'fr'],
            ['no', 'no'], ['no', 'rs'],
            ['pl', 'pl'], ['pl', 'pt'],
            ['br', 'br'], ['br', 'sk'],
            ['pt', 'pt'], ['pt', 'fr'],
            ['ro', 'ro'], ['ro', 'it'],
            ['sr', 'rs'], ['sr', 'fr'],
            ['tr', 'tr'], ['tr', 'gr'],
            ['cs', 'cz'], ['cs', 'gr'],
            ['ru', 'ru'], ['ru', 'nl'],
            ['mk', 'mk'], ['mk', 'de'],
            ['fa', 'ir'], ['fa', 'fr'],
            ['bn', 'bd'], ['bn', 'id'],
            ['tw', 'tw'], ['tw', 'us'],
            ['zh', 'cn'], ['zh', 'us'],
            ['he', 'il'], ['he', 'fr']
        ];
    }

    /**
	* @dataProvider languageAndCountryPairs
	* @parallelize 3
	*/
    public function testInstallationForLanguageAndCountry($language, $country)
    {
        $this->shop->getInstaller()->install(['language' => $language, 'country' => $country]);
        $this->shop->getBackOfficeNavigator()->login();
        $this->shop->getOrderManager()->visit(5)->validate();
    }
}
