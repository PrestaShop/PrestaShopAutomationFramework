<?php

namespace PrestaShop\PSTAF\OnDemand;

use PrestaShop\PSTAF\EmailReader\GmailReader;
use PrestaShop\PSTAF\Helper\Spinner;
use Symfony\Component\DomCrawler\Crawler;

class AccountCreation
{
	private $homePage;
	private $browser;

	public static $expectedActivationEmailButtonTitle = [
		'en' => 'Activate my account',
		'fr' => 'Activer mon compte',
		'es' => 'Activar mi cuenta',
		'it' => 'Attivare il mio account',
		'pt' => '?',
		'nl' => 'Activeer mijn account'
	];

	public function __construct($homePage)
	{
		$this->homePage = $homePage;
		$this->browser = $homePage->getBrowser();
	}

	public function createAccountAndShop(array $options)
	{
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

		$waitForEmail = new Spinner('Could not find activation email.', 60);

		$reader = new GmailReader(
			$this->homePage->getSecrets()['customer']['email'],
			$this->homePage->getSecrets()['customer']['gmail_password']
		);

		$expectedActivationEmailButtonTitle = static::$expectedActivationEmailButtonTitle[$options['language']];

		$activationLink = null;

		$waitForEmail->assertBecomesTrue(function () use ($reader, $options, $expectedActivationEmailButtonTitle, &$activationLink) {

			$emails = $reader->readEmails($options['email']);

			foreach ($emails as $email) {
				$crawler = new Crawler('', 'http://www.example.com');
				$crawler->addHtmlContent($email['body']);

				$crawler = $crawler->selectLink($expectedActivationEmailButtonTitle);

				if ($crawler->count() === 0) {
					return false;
				} else {
					$activationLink = $crawler->link()->getUri();
					return true;
				}				
			}

			return false;
		}, false);

		$this->browser->visit($activationLink);
		
		//$this->browser->waitForUserInput();
	}
}