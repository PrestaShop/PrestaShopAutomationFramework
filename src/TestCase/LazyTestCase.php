<?php

namespace PrestaShop\TestCase;

use \PrestaShop\Shop;

/**
* A Lazy TestCase differs from a TestCase in that
* the state of the shop is not saved and restored after each test.
* This is acceptable for simple tests or for tests that depend on the result of a previous test.
*/

class LazyTestCase extends TestCase
{
	private static $shops = [];

	public function setUp()
	{
		if (!$this->shop)
		{
			$this->shop = Shop::getFromCWD();
			self::$shops[get_called_class()] = $this->shop;
		}
	}

	public function tearDown()
	{
		// Do nothing
	}

	public static function tearDownAfterClass()
	{
		if (isset(self::$shops[get_called_class()]))
			self::$shops[get_called_class()]->getBrowser()->quit();
	}
}
