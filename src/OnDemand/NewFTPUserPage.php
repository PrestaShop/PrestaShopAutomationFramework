<?php

namespace PrestaShop\PSTAF\OnDemand;

class NewFTPUserPage extends OnDemandPage
{
	public function createUser($username, $password, $accountPassword)
	{
		$this->getBrowser()
		->fillIn('#inputUser', $username)
		->fillIn('#inputPassword', $password)
		->click('button.pwd_required')
		->fillIn('#modal_password_confirm', $accountPassword)
		->click('#confirm_pwd_action')
		->ensureStandardSuccessMessageDisplayed();

		$settingsPage = new SettingsPage($this);

		return $settingsPage;
	}
}