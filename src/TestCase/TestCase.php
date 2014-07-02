<?php

namespace PrestaShop\TestCase;

use \PrestaShop\Shop;

class TestCase extends \PHPUnit_Framework_TestCase
{
	private static $shops = [];

	protected $shop;

	public static function setupBeforeClass()
	{
		\PrestaShop\SeleniumManager::ensureSeleniumIsRunning();
		if (!isset(self::$shops[get_called_class()]))
			self::$shops[get_called_class()] = Shop::getFromCWD();
	}

	public static function tearDownAfterClass()
	{
		// Close the opened window
		if (isset(self::$shops[get_called_class()]))
			self::$shops[get_called_class()]->getBrowser()->quit();
	}

	public static function getShop()
	{
		return self::$shops[get_called_class()];
	}

	public function setUp()
	{
		$this->shop = self::getShop();
		// TODO: Save state of shop
	}

	public function tearDown()
	{
		// TODO: restore state of shop
	}
}
