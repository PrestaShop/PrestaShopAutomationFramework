<?php

namespace PrestaShop;

class TestCase extends \PHPUnit_Framework_TestCase
{
	public static function setupBeforeClass()
	{
		SeleniumManager::ensureSeleniumIsRunning();
	}

	public function setUp()
	{
		$this->shop = Shop::getFromCWD();
	}

	public function tearDown()
	{
	}

	public function seed()
	{
		return [[null]];
	}

	/**
	* @dataProvider seed
	*/
	public function test()
	{
		call_user_func_array([$this, 'execute'], func_get_args());
	}
}
