<?php

namespace PrestaShop\PSTAF\TestCase;

use PrestaShop\PSTAF\Shop;
use PrestaShop\PSTAF\SeleniumManager;

class OnDemandTestCase extends TestCase
{
    public function setUp()
    {
        $this->shop = static::getShop();
        $this->browser = static::getBrowser();
    }

    public function tearDown()
    {
        // Do nothing
    }

    public static function setUpBeforeClass()
    {
        SeleniumManager::ensureSeleniumIsRunning();
    }

    public static function tearDownAfterClass()
    {
       
    }
}
