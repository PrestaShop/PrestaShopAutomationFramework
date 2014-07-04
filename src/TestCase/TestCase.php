<?php

namespace PrestaShop\TestCase;

use \PrestaShop\Shop;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
	private static $shops = [];

	protected $shop;

	public static function setUpBeforeClass()
	{
		static::beforeAll();
	}

	public static function beforeAll()
	{

	}

	public static function tearDownAfterClass()
	{
		// Close the opened window
		if (isset(self::$shops[get_called_class()]))
			self::$shops[get_called_class()]->getBrowser()->quit();
	}

	public static function getShop()
	{
		\PrestaShop\SeleniumManager::ensureSeleniumIsRunning();
		if (!isset(self::$shops[get_called_class()]))
			self::$shops[get_called_class()] = Shop::getFromCWD();
		return self::$shops[get_called_class()];
	}

	public function setUp()
	{
		// TODO: Save state of shop
	}

	public function tearDown()
	{
		// TODO: restore state of shop
	}
}
