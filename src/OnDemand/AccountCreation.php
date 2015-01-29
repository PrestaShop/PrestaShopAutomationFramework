<?php

namespace PrestaShop\PSTAF\OnDemand;

use Symfony\Component\DomCrawler\Crawler;

use PrestaShop\PSTAF\EmailReader\GmailReader;
use PrestaShop\PSTAF\Helper\Spinner;
use PrestaShop\PSTAF\Shop;
use PrestaShop\PSTAF\OptionProvider;

use PrestaShop\PSTAF\Exception\FailedTestException;

class AccountCreation
{
	private $homePage;
	private $browser;

	public static $expectedActivationEmailButtonTitle = [
		'en' => 'Activate my account',
		'fr' => 'Activer mon compte',
		'es' => 'Activar mi cuenta',
		'it' => 'Attivare il mio account',
		'pt' => 'Activar a minha conta',
		'nl' => 'Activeer mijn account'
	];

	public function __construct($homePage)
	{
		$this->homePage = $homePage;
		$this->browser = $homePage->getBrowser();
	}

	public function createAccountAndShop(array $options)
	{
		$options = array_merge(['waitForSubdomain' => true], $options);

		$this->homePage
		->visit()
		->setLanguage($options['language'])
		->submitShopCreationBannerForm($options['shop_name'], $options['email'])
		->chooseCountry($options['country'])
		->chooseFirstQualification()
		->submit()
		->fillFirstname('Jøħn')
		->fillLastname('Sölünëum')
		->fillPassword($options['password'])
		->fillPasswordConfirmation($options['password'])
		->acceptTandC()
		->submit()
		;

		$waitForEmail = new Spinner('Could not find activation email.', 300);

		$reader = new GmailReader(
			$this->homePage->getSecrets()['customer']['email'],
			$this->homePage->getSecrets()['customer']['gmail_password']
		);

		$expectedActivationEmailButtonTitle = static::$expectedActivationEmailButtonTitle[$options['language']];

		$activationLink = null;

		/**
		 * @todo : do we want to test the order in which the emails are received?
		 */

		try {
			$waitForEmail->assertBecomesTrue(function () use ($reader, $options, $expectedActivationEmailButtonTitle, &$activationLink) {

				$emails = $reader->readEmails($options['email']);

				foreach ($emails as $email) {
					$crawler = new Crawler('', 'http://www.example.com');
					$crawler->addHtmlContent($email['body']);

					$crawler = $crawler->selectLink($expectedActivationEmailButtonTitle);

					if ($crawler->count() > 0) {
						$activationLink = $crawler->link()->getUri();
						return true;
					}
				}

				return false;
			}, false);
		} catch (\Exception $e) {
			throw new FailedTestException($e->getMessage());
		}

		$this->browser->visit($activationLink);

		try {
			// Proper markup, but not on all environments
			$frontOfficeURL = $this->browser->getAttribute('a[data-sel="fo-link"]', 'href');
			$backOfficeURL 	= $this->browser->getAttribute('a[data-sel="bo-link"]', 'href');
		} catch (\Exception $e) {
			// Fallback to horrible css selectors
			$backOfficeURL 	= $this->browser->getAttribute('a.btn-store:nth-child(1)', 'href');
			$tmpFrontOfficeURL = $this->browser->getAttribute('a.btn-store:nth-child(2)', 'href');
			$frontOfficeURL = preg_replace('/^(\w+:\/\/)([^.]+)/', "\${1}{$options['shop_name']}", $tmpFrontOfficeURL);
		}

		if ($options['waitForSubdomain']) {
			$this->waitFor200($frontOfficeURL);
			sleep(300); // wait 5 minutes for the host to be ready
		}

		$shopSettings = [
			'front_office_url' => $frontOfficeURL,
			'back_office_url' => $backOfficeURL,
			'back_office_folder_name' => 'backoffice',
			'prestashop_version' => '1.6.0.10'
		];

		$shop = new Shop($shopSettings, null);
		$shop->setBrowser($this->browser);

		$optionProvider = new OptionProvider();
		$optionProvider->setDefaultValues([
			'BackOfficeLogin' => [
			    'admin_email'     => $options['email'],
			    'admin_password'  => $options['password']
			]
		]);

		$shop->setOptionProvider($optionProvider);

		$myStores = new MyStoresPage($this->browser, $this->homePage->getSecrets());

		return [
			'shop' => $shop,
			'myStoresPage' => $myStores
		];
	}

	public function waitFor200($url)
	{
		$spinner = new Spinner('Did not find final FO URL in 1 hour.', 3600, 1000);

		$spinner->assertBecomesTrue(function () use ($url) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			return $status == 200;
		});
	}
}
