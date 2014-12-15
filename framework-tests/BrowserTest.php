<?php

use PrestaShop\PSTAF\Browser\Browser;

function wait()
{
	fgets(fopen("php://stdin","r"));
}

class BrowserTest extends PHPUnit_Framework_TestCase
{
	private static $staticBrowser;

	public static function setupBeforeClass()
	{
		self::$staticBrowser = new Browser(['host' => getenv('SELENIUM_HOST')]);
	}

	public static function teardownAfterClass()
	{
		self::$staticBrowser->quit();
	}

	public function setup()
	{
		$this->browser = self::$staticBrowser;
		$this->browser->setDefaultRetryTimeout(0);
		$this->browser->visit('file://' . __DIR__ . '/html/index.html');
	}

	public function testVisit()
	{
		$this->browser->visit('file://' . __DIR__ . '/html/index.html');
	}

	/**
	 * @expectedException PrestaShop\PSTAF\Exception\ElementNotFoundException
	 */
	public function testFindUnExistingSingle()
	{
		$this->browser->find('#notThere', ['unique' => true]);
	}

	/**
	 * @expectedException PrestaShop\PSTAF\Exception\ElementNotFoundException
	 */
	public function testFindUnExistingMultiple()
	{
		$this->browser->find('#notThere', ['unique' => false]);
	}

	/**
	 * @expectedException PrestaShop\PSTAF\Exception\TooManyElementsFoundException
	 */
	public function testFindTooMany()
	{
		$this->browser->find('.many', ['unique' => true]);
	}

	public function testAllNothing()
	{
		$this->assertEquals(0, count($this->browser->all('.nablanablanabla')));
	}

	public function testAllSomething()
	{
		$this->assertEquals(3, count($this->browser->all('.three')));
	}

	public function testFindExistingSingle()
	{
		$this->browser->find('#ohai', ['unique' => true]);
	}

	public function testWaitForExistingImmediatelyVisible()
	{
		$this->browser->waitFor('#ohai');
	}

	/**
	 * @expectedException PrestaShop\PSTAF\Exception\ElementNotFoundException
	 */
	public function testWaitForExistingNeverVisible()
	{
		try {
			$this->browser->find('#invisible');
		} catch (\Exception $e) {
			throw new \Exception("Did not find element, should have been found.", 1, $e);
		}

		$this->browser->waitFor('#invisible');
	}

	public function testWaitForEventuallyVisible()
	{
		$this->browser->waitFor('#eventually-visible');
	}

	public function testHasVisible()
	{
		$this->assertEquals(true, $this->browser->hasVisible('#ohai'));
	}

	public function testHasVisibleDoesntWait()
	{
		$this->browser->setDefaultRetryTimeout(15); // should not matter
		$this->assertEquals(false, $this->browser->hasVisible('#eventually-visible'));
	}

	public function testFindExistingMultiple()
	{
		$elements = $this->browser->find('.three', ['unique' => false]);
		$this->assertEquals(3, count($elements));
	}

	public function testGetAttribute()
	{
		$attr = $this->browser->getAttribute('[data-hello]', 'data-hello');
		$this->assertEquals('world', $attr);
	}

	/**
	 * @expectedException PrestaShop\PSTAF\Exception\ElementNotFoundException
	 */
	public function testGetAttributeNotFound()
	{
		$this->browser->getAttribute('[data-nonExisting]', 'data-hello');
	}

	/**
	 * @expectedException PrestaShop\PSTAF\Exception\TooManyElementsFoundException
	 */
	public function testGetAttributeTooMany()
	{
		$this->browser->getAttribute('.many', 'data-hello');
	}

	public function testSelectedValue()
	{
		$this->assertEquals("3", $this->browser->getSelectedValue('#select'));
	}

	public function testSelect()
	{
		$this->assertEquals("3", $this->browser->getSelectedValue('#select'));
		$this->browser->select('#select', "4");
		$this->assertEquals("4", $this->browser->getSelectedValue('#select'));
	}

	public function testGetSelectOptions()
	{
		$this->assertEquals([
			"1" => "",
			"2" => "",
			"3" => "three",
			"4" => ""
		], $this->browser->getSelectOptions('#select'));
	}

	public function testSelectedText()
	{
		$this->assertEquals("three", $this->browser->getSelectedText('#select'));
	}

	public function testSelectedValues()
	{
		$this->assertEquals(["1", "3"], $this->browser->getSelectedValues('#multiple'));
	}

	public function testMultiSelect()
	{
		$this->assertEquals(["1", "3"], $this->browser->getSelectedValues('#multiple'));
		$this->browser->multiSelect('#multiple', ["1", "2"]);
		$this->assertEquals(["1", "2"], $this->browser->getSelectedValues('#multiple'));
	}

	public function testClick()
	{
		$this->assertEquals('not clicked', $this->browser->getText('#click-me'));
		$this->browser->click('#click-me');
		$this->assertEquals('clicked', $this->browser->getText('#click-me'));
	}

	public function testHover()
	{
		$this->assertEquals('', $this->browser->getText('#hover'));
		$this->browser->hover('#hover-container');
		$this->assertEquals('hovered!', $this->browser->getText('#hover'));
	}

	public function testFillInAndGetValue()
	{
		$this->assertEquals('', $this->browser->getValue('#input'));
		$this->browser->fillIn('#input', 'a value');
		$this->assertEquals('a value', $this->browser->getValue('#input'));
	}

	public function testSendKeys()
	{
		$this->assertEquals('', $this->browser->getValue('#input'));
		$this->browser->click('#input')->sendKeys('yo');
		$this->assertEquals('yo', $this->browser->getValue('#input'));
	}

	public function testClickFirstVisible()
	{
		$this->assertEquals('not clicked yet', $this->browser->getText('#first-visible'));
		$this->browser->clickFirstVisible('.first-visible');
		$this->assertEquals('clicked', $this->browser->getText('#first-visible'));
	}

	public function testClickButtonNamed()
	{
		$this->browser->setDefaultRetryTimeout(5);
		$this->assertEquals('', $this->browser->getText('#clickButtonNamed'));
		$this->browser->clickButtonNamed('button');
		$this->assertEquals('clicked', $this->browser->getText('#clickButtonNamed'));
	}
}