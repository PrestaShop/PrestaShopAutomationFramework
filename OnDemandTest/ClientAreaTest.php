<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use PrestaShop\PSTAF\OnDemand\AccountCreation;

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
	 * @maxattempts 1
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

		/*
		$myStoresPage = $this->homePage->visit()->login(
			'prestashop.john.doe+cYbTndwbLenbzbqdpcleHdwcRegbMeo@gmail.com',
			'123456789'
		);*/

		$domains = $myStoresPage->gotoDomains();

		while(!$domains->checkIfDomainIsAvailable(md5(microtime()).'.com'));

		$addressForm = $domains->orderDomain();

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
}