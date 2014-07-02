<?php

namespace PrestaShop\TestCase;

use \PrestaShop\Shop;

class TestCase extends \PHPUnit_Framework_TestCase
{
	protected $shop;

	public static function setupBeforeClass()
	{
		\PrestaShop\SeleniumManager::ensureSeleniumIsRunning();
	}

	public static function tearDownAfterClass()
	{

	}

	public function setUp()
	{
		$this->shop = Shop::getFromCWD();
		// TODO: Save state of shop
	}

	public function tearDown()
	{
		$this->shop->getBrowser()->quit();
		// TODO: restore state of shop
	}
}
