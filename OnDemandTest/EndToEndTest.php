<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\FunctionalTest\InvoiceTest;
use PrestaShop\PSTAF\Helper\Spinner;
use PrestaShop\PSTAF\Helper\HumanHash;
use PrestaShop\PSTAF\Exception\FailedTestException;

class EndToEndTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
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
	 * @dataProvider languageAndCountryPairs
	 * @parallelize
	 */
	public function testFullProcess($language, $country)
	{
		self::setValue('language', $language);
		self::setValue('country', $country);

		$this->browser->clearCookies();
		$this->homePage->getBrowser()->clearCookies();
		$accountCreation = new AccountCreation($this->homePage);

		$secrets = $this->getSecrets();

		$uid = HumanHash::humanMd5(self::newUid().$language.$country);

		$this->writeMetaData('uid', $uid);

		self::setValue('uid', $uid);

		$email =  implode("+$uid@", explode('@', $secrets['customer']['email']));
		$shop_name = $uid;

		$this->writeMetaData('email', $email);
		$this->writeMetaData('password', $secrets['customer']['password']);

		$data = $accountCreation->createAccountAndShop([
			'email' 	=> $email,
			'password'	=> $secrets['customer']['password'],
			'shop_name' => $shop_name,
			'country'	=> $country,
			'language'	=> $language
		]);

		$this->shop = $data['shop'];
		self::setShop($this->shop);

		$this->writeMetaData('backOfficeURL', $this->shop->getBackOfficeURL());

		$this->shop->getBackOfficeNavigator()->login();

		if (!empty($secrets['smtp'])) {
			$smtp = $secrets['smtp'];
			$this->shop->getPageObject('AdminStores')->visit()->setShopEmail($smtp['sender']);
			$this->shop->getPageObject('AdminEmails')->visit()->setSMTP($smtp);
		}

		$loc = $this->shop->getPageObject('AdminLocalization')->visit();

		$actualLanguage = $loc->getDefaultLanguageName();
		$actualCountry = $loc->getDefaultCountryName();

		// Check that the shop is setup with the same country as defined during onboarding
		$expectedCountry = $this->extraLocalizationData('AdminLocalizationExpectedCountry');
		$this->assertEquals(
			$expectedCountry,
			$actualCountry,
			"Shop doesn't have the expected default country, expected `$expectedCountry` but got `$actualCountry`."
		);

		$expectedLanguage = $this->extraLocalizationData('AdminLocalizationExpectedLanguage');
		$this->assertEquals(
			$expectedLanguage,
			$actualLanguage,
			"Shop doesn't have the expected default language, expected `$expectedLanguage` but got `$actualLanguage`."
		);

		$this->customersCanRegister();
		$this->emailsAreSent();
		$this->basicSellingFeatures();
	}

	public function getEmailTestAddress()
	{
		$uid = self::getValue('uid');
		return implode("+{$uid}_emailTest@", explode('@', $this->getSecrets()['customer']['email']));
	}

	public function getRegistrationAddress()
	{
		$uid = self::getValue('uid');
		return implode("+{$uid}_registration@", explode('@', $this->getSecrets()['customer']['email']));
	}

	public function customersCanRegister()
	{
		$this->browser->clearCookies();

		$registrationAddress =  $this->getRegistrationAddress();
		$this->shop->getRegistrationManager()->registerCustomer([
			'customer_email' => $registrationAddress,
			'customer_password' => '123456789'
		]);

		$addressData = $this->extraLocalizationData('addressData');

		// Create an address
		$addressForm = $this->shop
							->getPageObject('MyAccount')
							->goToMyAddresses()
							->goToNewAddress();

		$addressForm->setFirstName('Carrie')
					->setLastName('Murray')
					->setAddress('5, main street')
					->setCity('Neverland')
					->setCountryId($addressData['countryId']);

		if (isset($addressData['stateId'])) {
			sleep(5);
			$addressForm->setStateId($addressData['stateId']);
		}

		if (isset($addressData['dni'])) {
			$addressForm->setDni($addressData['dni']);
		}

		$addressForm->setPostCode($addressData['postCode'])
					->setPhone('12345655')
					->setAlias('My Cool Selenium Address');

		$addressForm->save();

		$this->shop->getOptionProvider()->setDefaultValues([
			'FrontOfficeLogin' => [
				'customer_email' => $registrationAddress,
				'customer_password' => '123456789'
			]
		]);
	}

	public function emailsAreSent()
	{
		$this->browser->clearCookies();
		$this->shop->getBackOfficeNavigator()->login();
		$emails = $this->shop->getPageObject('AdminEmails')->visit();

		$emailTestAddress = $this->getEmailTestAddress();

		$spinner = new Spinner('Could not send an email (after 10 minutes).', 600, 5000);

		$spinner->assertNoException(function () use ($emails, $emailTestAddress) {
			$emails->sendTestEmailTo($emailTestAddress);
		});

		$this->getEmailReader()->ensureAnEmailIsSentTo($emailTestAddress);
	}


	public function basicSellingFeatures()
	{
		$this->browser->clearCookies();

		$scenario = $this->getJSONExample('invoice/simple-order.json');
        $output = InvoiceTest::runScenario($this->shop, $scenario);
        InvoiceTest::checkInvoiceJson($scenario['expect']['invoice'], $output['json']);

        $reference = $output['json']['order']['reference'];

        try {
        	$this->getEmailReader()->ensureAnEmailIsSentTo(
        		$this->getRegistrationAddress(),
        		300,
        		['body' => ['contains' => $reference]]
        	);
        } catch (\Exception $e) {
        	throw new FailedTestException(
        		"No valid order confirmation email received by customer in the 5 minutes after placing an order."
        	);
        }
	}
}
