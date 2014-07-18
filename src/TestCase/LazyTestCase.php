<?php

namespace PrestaShop\TestCase;

use \PrestaShop\Shop;

/**
* A Lazy TestCase differs from a TestCase in that
* the state of the shop is not saved and restored after each test.
* This is acceptable for simple tests or for tests that depend on the result of a previous test.
*/

class LazyTestCase extends TestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
		// Do nothing
	}
}
