<?php

namespace PrestaShop\TestCase;

use \PrestaShop\ShopManager;
use \PrestaShop\Shop;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
	private static $shops = [];

	public static function setUpBeforeClass()
	{
		\PrestaShop\SeleniumManager::ensureSeleniumIsRunning();
		$class = get_called_class();
		self::$shops[$class] = ShopManager::getShop();
		static::beforeAll();
	}

	public static function beforeAll()
	{

	}

	public static function tearDownAfterClass()
	{
		$shop = static::getShop();
		$shop->getBrowser()->quit();
	}

	public static function getShop()
	{
		$class = get_called_class();
		return self::$shops[$class];
	}

	public function setUp()
	{
		static::getShop()->getBrowser()->clearCookies();
		// TODO: Save state of shop
	}

	public function tearDown()
	{
		// TODO: restore state of shop
	}
}
