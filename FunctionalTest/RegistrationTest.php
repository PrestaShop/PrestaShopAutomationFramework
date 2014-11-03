<?php

namespace PrestaShop\PSTAF\FunctionalTest;
use PrestaShop\PSTAF\TestCase\LazyTestCase;


class RegistrationTest extends LazyTestCase
{
	public function testCustomersCanRegister()
	{
		$this->shop->getRegistrationManager()->registerCustomer();
	}
}