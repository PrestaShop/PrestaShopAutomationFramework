<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use Exception;

use PrestaShop\PSTAF\Exception\Unexpected301Exception;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\Helper\Spinner;

class DomainsAssociationTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
	public function getSecretsName()
	{
		return 'EndToEndTest';
	}

	private function getStatusAndLocation($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		$headers = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$m = array();
		$location = null;

		if (preg_match('/^location:\s+(.*?)\s*$/im', $headers, $m)) {
			$location = $m[1];
		}

		return [$status, $location];
	}

	private function checkDomainPointsToShopWithoutRedirection($domain, $shopName, $timeout = 300)
	{
		$spinner = new Spinner(null, $timeout, 5000);

		$url = $this->getBrowser()->getCurrentURL();

		$spinner->addPassthroughExceptionClass('PrestaShop\PSTAF\Exception\Unexpected301Exception');

		$spinner->assertNoException(function () use ($domain, $shopName) {

			if (!preg_match('#^\w+://#', $domain)) {
				$url = "http://$domain";
			} else {
				$url = $domain;
			}

			list($status, $location) = $this->getStatusAndLocation($url);

			if ($status === 301) {
				throw new Unexpected301Exception('Redirected to `' . $location . '`. Expected status code 200 on `' . $url . '`, but got 301. This is very bad because 301 is permanent.');
			} elseif ($status !== 200) {
				throw new Exception('Expected status code 200 on `' . $url . '`, but got ' . $status . '.');
			}

			$this->getBrowser()->visit($url);

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

	/**
	 * @depends testSubdomains2and3AreUnassignedFromShopA
	 */
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

	/**
	 * @depends testCreateShopB
	 */
	public function testICanReAssignSd2ToBandSd3toA()
	{
		$domainsPage = $this->homePage->visit()->gotoMyStores()->gotoDomains();

		$spinner = new Spinner('Cannot assign domains that are not green.', 1800, 1000);

		$spinner->assertBecomesTrue(function () use ($domainsPage) {
			$this->getBrowser()->reload();
			return $domainsPage->isGreen(self::getValue('sd2')) && $domainsPage->isGreen(self::getValue('sd3'));
		});

		$domainsPage->assignDomainToShop(self::getValue('sd2'), self::getValue('uidB'));
		$domainsPage->assignDomainToShop(self::getValue('sd3'), self::getValue('uidA'));

		$spinner = new Spinner('Domains weren\'t bound in 30 minutes.', 1800, 1000);

		$spinner->assertBecomesTrue(function () use ($domainsPage) {
			$this->getBrowser()->reload();
			$sd2 = $domainsPage->isDomainActive(self::getValue('sd2'));
			$sd3 = $domainsPage->isDomainActive(self::getValue('sd3'));
			return $sd2 && $sd3;
		});

		return $domainsPage;
	}

	/**
	 * @depends testICanReAssignSd2ToBandSd3toA
	 */
	public function testICanSetSd2andSd3asPrimary($domainsPage)
	{
		$domainsPage->setPrimary(self::getValue('sd2'));
		$domainsPage->setPrimary(self::getValue('sd3'));

		$this->checkDomainPointsToShopWithoutRedirection(self::getValue('sd2'), self::getValue('uidB'));
		$this->checkDomainPointsToShopWithoutRedirection(self::getValue('sd3'), self::getValue('uidA'));
	}

	/**
	 * @depends testCreateShopB
	 */
	public function testSubdomainsFromDeletedShopRemainAvailableToCustomer()
	{
		$domainsPage = $this->homePage->visit()->gotoMyStores()
		->gotoDetails(self::getValue('uidA'))
		->deleteStore()
		->gotoDomains();

		$spinner = new Spinner('Domains from deleted shop did not become available.', 1800, 1000);

		$spinner->assertBecomesTrue(function () use ($domainsPage) {
			$this->getBrowser()->reload();
			return $domainsPage->isGreen(self::getValue('sd1')) && $domainsPage->isGreen(self::getValue('sd3'));
		});

		return $domainsPage;
	}

	/**
	 * @depends testSubdomainsFromDeletedShopRemainAvailableToCustomer
	 */
	public function testDomainsFromDeletedShopCanBeReAssigned($domainsPage)
	{
		$domainsPage->assignDomainToShop(self::getValue('sd1'), self::getValue('uidB'));
		$domainsPage->assignDomainToShop(self::getValue('sd3'), self::getValue('uidB'));

		$spinner = new Spinner('Domains from deleted shop could not be re-assigned to surviving shop.', 1800, 1000);

		$spinner->assertBecomesTrue(function () use ($domainsPage) {
			$this->getBrowser()->reload();
			return $domainsPage->isGreen(self::getValue('sd1')) && $domainsPage->isGreen(self::getValue('sd3'));
		});
	}
}
