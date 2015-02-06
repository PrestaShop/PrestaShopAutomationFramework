<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use Exception;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\Helper\Spinner;

class DomainsAssociationTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
	public function getSecretsName()
	{
		return 'EndToEndTest';
	}

	private function checkDomainPointsToShopWithoutRedirection($domain, $shopName)
	{
		$spinner = new Spinner(null, 300, 1000);

		$url = $this->getBrowser()->getCurrentURL();

		$spinner->assertNoException(function () use ($domain, $shopName) {
			$this->getBrowser()->visit($domain);

			$currentURL = $this->getBrowser()->getCurrentURL();

			if (strpos($currentURL, $domain) === false) {
				throw new Exception('Not a primary domain, redirected to `' . $currentURL . '` but expected `' . $domain . '`.');
			}

			$alt = $this->getBrowser()->getAttribute('#header_logo img', 'alt');

			if (strtolower($alt) !== strtolower($shopName)) {
				throw new Exception(
					'This is not the shop you\'re looking for. Got `' . $alt . '` instead of `' . $shopName . '`.'
				);
			}
		});

		$this->getBrowser()->visit($url);

		return $this;
	}

	public function testCreateAccountAndShop()
	{
		$accountCreation = new AccountCreation($this->homePage);

		$uidA = self::newUID();
		self::setValue('uidA', $uidA);

		$secrets = $this->getSecrets();

		$email =  implode("+$uidA@", explode('@', $secrets['customer']['email']));
		self::setValue('emailA', $email);

		$accountAndShop = $accountCreation->createAccountAndShop([
			'email' 	=> $email,
			'password'	=> $secrets['customer']['password'],
			'shop_name' => $uidA,
			'language'	=> 'en',
			'country'	=> 'United States',
			'waitForSubdomain' => false
		]);

		self::setValue('shopA', $accountAndShop['shop']);
		self::setValue('sd1', rtrim(preg_replace('/^\w+:\/\//', '', $accountAndShop['shop']->getFrontOfficeURL()), '/'));

		return $accountAndShop['myStoresPage']->gotoDetails();
	}

	/**
	 * @depends testCreateAccountAndShop
	 */
	public function testICanBind2SubdomainsToShopA($storeDetailsPage)
	{
		$sd2 = md5(microtime() . 'A') . $this->getSecrets()['subdomain'];
		$sd3 = md5(microtime() . 'B') . $this->getSecrets()['subdomain'];

		self::setValue('sd2', $sd2);
		self::setValue('sd3', $sd3);

		$storeDetailsPage->bindDomain($sd2);
		$storeDetailsPage->bindDomain($sd3);
	}

	/**
	 * @depends testICanBind2SubdomainsToShopA
	 */
	public function testMy3DomainsAssociateCorrectlyToShopA()
	{
		$domainsPage = $this->homePage->visit()->gotoMyStores()->gotoDomains();

		$spinner = new Spinner('Domains weren\'t bound in 30 minutes.', 1800, 1000);

		$spinner->assertBecomesTrue(function () use ($domainsPage) {
			$this->getBrowser()->reload();
			$sd1 = $domainsPage->isDomainActive(self::getValue('sd1'));
			$sd2 = $domainsPage->isDomainActive(self::getValue('sd2'));
			$sd3 = $domainsPage->isDomainActive(self::getValue('sd3'));
			return $sd1 && $sd2 && $sd3;
		});

		return $domainsPage;
	}


	/**
	 * @depends testMy3DomainsAssociateCorrectlyToShopA
	 */
	public function testSubdomain2CanBeSetToPrimaryForShopA($domainsPage)
	{
		$domainsPage->setPrimary(self::getValue('sd2'));
		$this->checkDomainPointsToShopWithoutRedirection(self::getValue('sd2'), self::getValue('uidA'));

		return $domainsPage;
	}

	/**
	* @depends testSubdomain2CanBeSetToPrimaryForShopA
	*/
	public function testSubdomain1CanBeSetToPrimaryForShopA($domainsPage)
	{
		$domainsPage->setPrimary(self::getValue('sd1'));
		$this->checkDomainPointsToShopWithoutRedirection(self::getValue('sd1'), self::getValue('uidA'));
		return $domainsPage;
	}

	/**
	 * @depends testSubdomain1CanBeSetToPrimaryForShopA
	 */
	public function testSubdomains2and3AreUnassignedFromShopA($domainsPage)
	{
		$domainsPage->unAssign(self::getValue('sd2'));
		$domainsPage->unAssign(self::getValue('sd3'));
	}

	public function testCreateShopB()
	{
		$uidB = self::newUID();
		self::setValue('uidB', $uidB);
		$accountCreation = new AccountCreation($this->homePage);

		$secrets = $this->getSecrets();
		$email =  implode("+$uidB@", explode('@', $secrets['customer']['email']));
		self::setValue('emailB', $email);

		$accountCreation->createAccountAndShop([
			'password'	=> $this->getSecrets()['customer']['password'],
			'shop_name' => $uidB,
			'language'	=> 'en',
			'country'	=> 'United States',
			'email' => $email,
			'waitForSubdomain' => false
		], true);
	}
}
