<?php

namespace PrestaShop\FunctionalTest;

class UploadTest extends \PrestaShop\TestCase\LazyTestCase
{
	public function tesUpload()
	{
		$shop = self::getShop();
		$browser = $shop->getBrowser();

		$shop
		->getBackOfficeNavigator()
		->login()
		->visit('AdminCategories', 'edit', 3);

		$browser->setFile('#image', '/home/fram/Documents/Pictures/logo.jpg');

		$browser->waitForUserInput();
	}

	public function testUpload()
	{
		$shop = self::getShop();
		$browser = $shop->getBrowser();

		$shop
		->getBackOfficeNavigator()
		->login()
		->visit('AdminProducts', 'edit', 3)
		->click('#link-Images')
		->setFile('#file', '/home/fram/Documents/Pictures/logo.jpg')
		->setFile('#file', '/home/fram/Documents/Pictures/pleo.jpg');

		$browser->waitForUserInput();
	}
}