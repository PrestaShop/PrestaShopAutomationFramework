<?php

namespace PrestaShop\TestCase;

use \PrestaShop\ShopManager;
use \PrestaShop\Shop;

abstract class TestCase extends \PHPUnit_Framework_TestCase implements \PrestaShop\Ptest\TestClass\Basic
{
	private static $shops = [];
	private static $shop_managers = [];
	private static $test_numbers = [];

	protected static $cache_initial_state = true;

	private static function newShop()
	{
		$class = get_called_class();
		self::$shops[$class] = self::getShopManager()->getShop([
			'initial_state' => static::initialState(),
			'temporary' => true,
			'use_cache' => true
		]);

		self::$shops[$class]->getBrowser()->clearCookies();
	}

	public static function setUpBeforeClass()
	{
		\PrestaShop\SeleniumManager::ensureSeleniumIsRunning();
		$class = get_called_class();
		$manager = ShopManager::getInstance();
		self::$shop_managers[$class] = $manager;
		self::newShop();
		register_shutdown_function([$class, 'tearDownAfterClass']);
	}

	public static function initialState()
	{
		return [
			'ShopInstallation' => [
				'language' => 'en',
				'country' => 'us'
			]
		];
	}

	public static function beforeAll()
	{

	}

	public static function tearDownAfterClass()
	{
		$class = get_called_class();
		if (isset(self::$shops[$class]))
		{
			static::getShopManager()->cleanUp(static::getShop());
			unset(self::$shops[$class]);
		}
	}

	public static function getShop()
	{
		$class = get_called_class();
		return self::$shops[$class];
	}

	private static function getShopManager()
	{
		$class = get_called_class();
		return self::$shop_managers[$class];
	}

	public function setUp()
	{
		$class = get_called_class();

		if (!isset(self::$test_numbers[$class]))
			self::$test_numbers[$class] = 0;
		else
			self::$test_numbers[$class]++;
		
		
		if (self::$test_numbers[$class] > 0)
		{
			// clean current shop
			static::getShopManager()->cleanUp(static::getShop());
			// get a new one
			self::newShop();
		}

		$this->shop = static::getShop();
	}

	public function tearDown()
	{
		// TODO: restore state of shop
	}

	public function onException($e, $files_prefix)
	{
		static::getShop()->getBrowser()->takeScreenshot($files_prefix.'.png');
	}
}
