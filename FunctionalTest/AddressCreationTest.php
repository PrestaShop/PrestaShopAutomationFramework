<?php

namespace PrestaShop\PSTAF\FunctionalTest;
use PrestaShop\PSTAF\TestCase\LazyTestCase;


class AddressCreationTest extends LazyTestCase
{
	/**
	 * @maxattempts 1
	 */
	public function testCustomersCanCreateAddresses()
	{
		$this->shop->getRegistrationManager()->registerCustomer();

		$this->shop
		->getPageObject('MyAccount')->visit()
		->goToMyAddresses()
		->goToNewAddress()
		->setFirstName('Carrie')
		->setLastName('Murray')
		->setAddress('5, main street')
		->setCity('Neverland')
		->setStateId(1)
		->setPostCode(12345)
		->setPhone('12345655')
		->setAdditionalInformation('bob')
		->setAlias('My Cool Address')
		->save();

		$ok = $this->shop
		->getPageObject('MyAccount')->visit()
		->goToMyAddresses()
		->hasAddressWithAlias('My Cool Address');

		if (!$ok) {
			throw new \Exception('Did not find address with required alias.');
		}
	}

	/**
	 * @maxattempts 1
	 */
	public function testAddressCanBeEdited()
	{
		$ok = $this->shop
		->getPageObject('MyAccount')->visit()
		->goToMyAddresses()
		->editAddress('My Cool Address')
		->setAlias('This Alias is new')
		->save();

		$ok = $this->shop
		->getPageObject('MyAccount')->visit()
		->goToMyAddresses()
		->hasAddressWithAlias('This Alias is new');

		if (!$ok) {
			throw new \Exception('Did not find address with required alias.');
		}
	}

	/**
	 * @maxattempts 1
	 */
	public function testAddressCanBeDeleted()
	{
		$ok = $this->shop
		->getPageObject('MyAccount')->visit()
		->goToMyAddresses()
		->deleteAddress('This Alias is new');
	}

	/**
	 * @maxattempts 1
	 * @expectedException PrestaShop\PSTAF\Exception\StandardErrorMessageDisplayedException
	 */
	public function testInvalidAddressIsNotSaved()
	{
		$this->shop
		->getPageObject('MyAccount')->visit()
		->goToMyAddresses()
		->goToNewAddress()
		->setFirstName('Carrie')
		->setLastName('Murray')
		// ->setAddress('5, main street') // omitted on purpose
		->setCity('Neverland')
		->setStateId(1)
		->setPostCode(12345)
		->setPhone('12345655')
		->setAdditionalInformation('bob')
		->setAlias('My Cool Address')
		->save();
	}
}