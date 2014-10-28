<?php

namespace PrestaShop\FunctionalTest;

/**
 * This test does the following:
 *
 * - Check it can access AdminTaxes
 * - Check taxes can be disabled / enabled
 * - Check displaying taxes in the shopping cart can be enabled / disabled
 * - Check ecotax can be enabled / disabled
 * - Check ecotax can be set to use a specific tax group
 * - Check that the tax can be set to be based on delivery / invoice address
 *
 * - Create and enable 2 tax rules, OldFrenchVat and NewFrenchVat
 *
 * - Create a tax rules group with one tax, one country with zipcode range
 * - Create a tax rules group with one tax, applied to 2 USA states
 * - Create a tax rules group with one tax, applied to 1 USA state
 * - Create a tax rules group with one tax, applied to 2 countries
 * - Create a tax rules group with one tax, applied to 1 country
 * - Create a tax rules group with two taxes, applied combined to 1 country
 * - Create a tax rules group with one tax, applied to all countries
 * - Create a tax rules group with two taxes, applied to all countries in combine mode
 *
 * - Delete created tax rule groups
 * - Delete created taxes
 *
 * THIS DOES NOT TEST THAT TAXES ARE ACTUALLY APPLIED CORRECTLY
 * IT JUST TESTS THAT THE BACK-OFFICE MENUS WORK AS EXPECTED
 *
 * Other tests will check correct application of taxes.
 *
 *
 */

class GeneralPreferencesManagementTest extends \PrestaShop\TestCase\LazyTestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        static::getShop()->getBackOfficeNavigator()->login();
    }

    public function testNumberOfDecimalsCanBeSet()
    {
        $this->shop->getPreferencesManager()->setRoundingDecimals(4);
    }
}
