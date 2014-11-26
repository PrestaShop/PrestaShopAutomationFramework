<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\EmailReader\GmailReader;
use PrestaShop\PSTAF\FunctionalTest\InvoiceTest;
use PrestaShop\PSTAF\Helper\Spinner;

function shortenMd5($input)
{	
	static $decimal = [
		'0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
		'6' => 6, '7' => 7, '8' => 8, '9' => 9, 'a' => 10, 'b' => 11,
		'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15
	];

	static $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	$input = strtolower($input);
	$output = '';

	for ($pos = 0; $pos < strlen($input) - 1; $pos += 2) {
		$hex = substr($input, $pos, 2);
		$dec = 16 * $decimal[$hex[0]] + $decimal[$hex[1]];

		$out = '';
		if ($dec < strlen($alphabet)) {
			$out .= $alphabet[$dec];
		} else {
			$rem = $dec % strlen($alphabet);
			$div = ($dec - $rem) / strlen($alphabet);
			$out .= $alphabet[$div].$alphabet[$rem];
		}

		$output .= $out;
	}
	return $output;
}

class EndToEndTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
	/**
	 * This is used to make reasonably unique, human readable names.
	 */
	private function _randomAnimalName()
	{
		static $data;

		if (!$data) {
			$data = json_decode(file_get_contents(__DIR__.'/data/animals.json'), true);
		}

		$item = $data[rand(0, count($data) - 1)];
		$item = implode('', array_reverse(explode(',', $item)));
		$m = [];
		$n = preg_match_all('/\w+/', $item, $m);
		return implode('', array_map('ucfirst', $m[0]));
	}

	public function randomAnimalName($maxLength = 15)
	{
		do {
			$animalName = $this->_randomAnimalName();
		} while (strlen($animalName) > $maxLength);

		return $animalName;
	}

	public function newUid()
	{
		$left  = $this->randomAnimalName(15);
		$right = '_'.date("dMYhis").getmypid();

		return $left.$right;
	}

	public function languageAndCountryPairs()
	{
		return [
			['en', 'United States'	, ['language' => 'English (English)']],
			['fr', 'France'			, ['language' => 'Français (French)']],
			['es', 'Spain'			, ['language' => 'Español (Spanish)']],
			['it', 'Italy'			, ['language' => 'Italiano (Italian)']],
			['nl', 'Netherlands'	, ['language' => 'Nederlands (Dutch)']],
			['pt', 'Brazil'			, ['language' => 'Português BR (Portuguese)']]
		];
	}

	public function getLanguageAndCountryIdentifier($language, $country)
	{
		$countryParts = preg_split('/\s+/', $country);
		if (count($countryParts) > 1) {
			$c = implode('', array_map(function ($part) {
				return strtoupper($part[0]);
			}, $countryParts));
		} else {
			$c = ucfirst(strtolower(substr($country, 0, 2)));
		}
		$l = substr(strtolower($language), 0, 2);
		return $l.$c;
	}

	public function getEmailReader()
	{
		$reader = new GmailReader(
			$this->getSecrets()['customer']['email'],
			$this->getSecrets()['customer']['gmail_password']
		);

		return $reader;
	}

	/**
	 * @maxattempts 1
	 * @dataProvider languageAndCountryPairs
	 * @parallelize
	 */
	public function testFullProcess($language, $country, array $expect)
	{
		$this->browser->clearCookies();
		$this->homePage->getBrowser()->clearCookies();
		$accountCreation = new AccountCreation($this->homePage);

		$secrets = $this->getSecrets();

		$uid = $this->newUid().'_'.$this->getLanguageAndCountryIdentifier($language, $country);

		$uid = shortenMd5(md5($uid));

		self::setValue('uid', $uid);

		$email =  implode("+$uid@", explode('@', $secrets['customer']['email']));
		$shop_name = $uid;

		$data = $accountCreation->createAccountAndShop([
			'email' 	=> $email,
			'password'	=> $secrets['customer']['password'],
			'shop_name' => $shop_name,
			'country'	=> $country,
			'language'	=> $language
		]);

		$this->shop = $data['shop'];
		self::setShop($this->shop);

		$this->shop->getBackOfficeNavigator()->login();

		$loc = $this->shop->getPageObject('AdminLocalization')->visit();

		$actualLanguage = $loc->getDefaultLanguageName();
		$actualCountry = $loc->getDefaultCountryName();

		// Check that the shop is setup with the same country as defined during onboarding
		$this->assertEquals(
			$country,
			$actualCountry,
			"Shop doesn't have the expected default country, expected `$country` but got `$actualCountry`."
		);

		$this->assertEquals(
			$expect['language'],
			$actualLanguage,
			"Shop doesn't have the expected default language, expected `{$expect['language']}` but got `$actualLanguage`."
		);

		$this->customersCanRegister();
		$this->basicSellingFeatures();
		$this->emailsAreSent();
	}

	
	public function customersCanRegister()
	{
		$this->browser->clearCookies();
		$uid = self::getValue('uid');
		$registrationAddress =  implode("+{$uid}_registration@", explode('@', $this->getSecrets()['customer']['email']));
		$this->shop->getRegistrationManager()->registerCustomer([
			'customer_email' => $registrationAddress
		]);
	}

	
	public function emailsAreSent()
	{
		$this->browser->clearCookies();
		$uid = self::getValue('uid');
		$this->shop->getBackOfficeNavigator()->login();
		$emails = $this->shop->getPageObject('AdminEmails')->visit();

		$emailTestAddress =  implode("+{$uid}_emailTest@", explode('@', $this->getSecrets()['customer']['email']));


		$spinner = new Spinner('Could not send an email.', 300, 5000);

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
	}
}