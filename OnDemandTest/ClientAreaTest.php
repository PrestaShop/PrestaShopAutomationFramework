<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\Helper\Spinner;

class ClientAreaTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
	public function getSecretsName()
	{
		// Share the secrets
		return 'EndToEndTest';
	}

	public function languageAndCountryPairs()
	{
		return [
			['en', 'United States'],
			['fr', 'France'],
			['es', 'Spain'],
			['it', 'Italy'],
			['nl', 'Netherlands'],
			['pt', 'Brazil']
		];
	}

	/**
	 * @parallelize
	 * @dataProvider languageAndCountryPairs
	 */
	public function testSubdomainsCanBeBought($language, $country)
	{
		self::setValue('language', $language);
		self::setValue('country', $country);

		$accountCreation = new AccountCreation($this->homePage);

		$uid = md5(microtime().getmypid());

		$secrets = $this->getSecrets();

		$email =  implode("+$uid@", explode('@', $secrets['customer']['email']));

		$data = $accountCreation->createAccountAndShop([
			'email' 	=> $email,
			'password'	=> $secrets['customer']['password'],
			'shop_name' => $uid,
			'language'	=> $language,
			'country'	=> $country,
			'waitForSubdomain' => false
		]);

		$myStoresPage = $data['myStoresPage'];

		/*$myStoresPage = $this->homePage->visit()->login(
			'prestabot+dBchbXeQdYcqcuwddorfGbadfbK@gmail.com',
			'123456789'
		);*/

		$storeDetailsPage = $myStoresPage->gotoDetails();

		$spinner = new Spinner('Could not find an available domain to order.', 60, 1000);
		$spinner->assertBecomesTrue(function () use ($storeDetailsPage) {
			return $storeDetailsPage->checkIfDomainIsAvailable(md5(microtime()).'.com');
		});

		$addressForm = $storeDetailsPage->orderDomain();

		$addressForm
		->setAddress('55, Main Street')
		->setPostCode($this->extraLocalizationData('addressData.postCode', '12345'))
		->setCity('Nöwhär')
		->setCountryId($this->extraLocalizationData('addressData.countryId', '1'));

		if (($stateId = $this->extraLocalizationData('addressData.stateId'))) {
			sleep(5);
			$addressForm->setStateId($stateId);
		}

		$addressForm
		->setPhone('0658795126')
		->setAlias('Yiha My Address');

		$addressForm->save();


		$this->browser->click('a.be2bill_link')->clickFirstVisible('[name="submitBe2billForm"]');

		try {
			$this->browser->ensureElementShowsUpOnPage('#be2bill_iframe');
			$this->browser->switchToIFrame('be2bill_iframe');
			$this->browser->ensureElementShowsUpOnPage('#b2b-submit');
		} catch (\Exception $e) {
			throw new \Exception('It seems the be2bill iframe did not show up.');
		}
	}

	public function testSubdomainsCanBeBound()
	{
		$storeDetailsPage = $this->homePage->visit()->gotoMyStores()->gotoDetails();

		$domain = md5(microtime()) . $this->getSecrets()['subdomain'];

		$storeDetailsPage->bindDomain($domain);

		$detailsPage = $this->homePage->visit()->gotoMyStores()->gotoDetails();

		$spinner = new Spinner('Domain `' . $domain . '` wasn\'t bound in 30 minutes.', 1800, 1000);

		$spinner->assertBecomesTrue(function () use ($detailsPage, $domain) {
			$this->getBrowser()->reload();
			return $detailsPage->isDomainActive($domain);
		});
	}
}
