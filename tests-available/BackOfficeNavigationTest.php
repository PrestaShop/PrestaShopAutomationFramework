<?php

require_once __DIR__.'/../vendor/autoload.php';

class BackOfficeNavigationTest extends \PrestaShop\TestCase\LazyTestCase
{
	public function testLogin()
	{
		$this->shop->getBackOfficeNavigator()->login();
	}

	public function testMenuLinks()
	{
		$links = $this->shop->getBackOfficeNavigator()->getMenuLinks();
		print_r($links);
	}
}
