<?php

namespace PrestaShop\PSTAF\OnDemand;

use PrestaShop\PSTAF\Exception\InvalidParameterException;
use PrestaShop\PSTAF\Exception\FailedTestException;

class YourAccountPage extends OnDemandPage
{
	public function fillFirstname($firstname)
	{
		$this->getBrowser()->fillIn('#firstname', $firstname);

		return $this;
	}

	public function fillLastname($lastname)
	{
		$this->getBrowser()->fillIn('#lastname', $lastname);

		return $this;
	}

	public function fillPassword($password)
	{
		$this->getBrowser()->fillIn('#password', $password);

		return $this;
	}

	public function fillPasswordConfirmation($password)
	{
		$this->getBrowser()->fillIn('#confirm_password', $password);

		return $this;
	}

	public function acceptTandC()
	{
		$this->getBrowser()->clickLabelFor('cgv');

		return $this;
	}

	public function submit()
	{
		$this->getBrowser()->click('input[name="submitstep2"]');

		return $this;
	}
}