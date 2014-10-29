<?php

namespace PrestaShop\PSTAF\TestCase;

use \PrestaShop\PSTAF\Shop;

/**
* A Lazy TestCase differs from a TestCase in that
* you don't get a new shop after each test.
*/

class LazyTestCase extends TestCase
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
}
