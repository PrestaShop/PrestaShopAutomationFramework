<?php

namespace PrestaShop\FunctionalTest;

class CarrierManagementTest extends \PrestaShop\TestCase\LazyTestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        static::getShop()->getBackOfficeNavigator()->login();
    }

    public function testCreateCarrier()
    {
        $shop = static::getShop();
        $data = $shop->getCarrierManager()->createCarrier(array(
            'name' => 'Turtle',
            'delay' => '28 days later',
            'handling' => 0,
            'free' => false,
            'tax_rules_group' => 9,
            'based_on' => 'price',
            'ranges' => [
				1000 => 5
			],
            'oorb' => 'disable'
        ));
    }

}
