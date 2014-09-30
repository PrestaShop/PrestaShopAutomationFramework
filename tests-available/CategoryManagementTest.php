<?php

namespace PrestaShop\FunctionalTest;

class CategoryManagementTest extends \PrestaShop\TestCase\LazyTestCase
{
	public static function setupBeforeClass()
	{
		parent::setupBeforeClass();

		self::getShop()->getBackOfficeNavigator()->login();
	}

	public function testSimpleCreation()
	{
		$shop = self::getShop();
		$shop->getCategoryManager()->createCategory(['name' => 'Hello']);
	}

	public function testCreateTree()
	{
		$shop = self::getShop();
		$cm = $shop->getCategoryManager();

		$tree = [];
		$tree[] = $cm->createCategory(['name' => 'Selenium Tree Root', 'parent' => 2]);
		$tree[] = $cm->createCategory(['name' => 'Selenium Child 1', 'parent' => end($tree)]);
		$tree[] = $cm->createCategory(['name' => 'Selenium Child 2', 'parent' => end($tree)]);
	}
}
