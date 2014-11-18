<?php

namespace PrestaShop\PSTAF\OnDemand;

use PrestaShop\PSTAF\Exception\InvalidParameterException;
use PrestaShop\PSTAF\Exception\FailedTestException;

class StoreConfigurationPage extends OnDemandPage
{
	public static $countryIds; // filled from countries.json in this directory

	public function chooseCountry($name)
	{
		if (!static::$countryIds) {
			static::$countryIds = [];
			$json = file_get_contents(__DIR__.'/countries.json');
			foreach (json_decode($json, true) as $data) {
				static::$countryIds[$data['name']] = $data['id'];
			}
		}


		if (!isset(static::$countryIds[$name])) {
			throw new InvalidParameterException("Unknown country `$name`.");
		}

		$this->getBrowser()
		->waitFor('#inputCountry')
		->select('#inputCountry', static::$countryIds[$name]);

		return $this;
	}

	public function chooseFirstQualification()
	{
		$options = $this->getBrowser()->getSelectOptions('#id_qualification');
		$option = null;
		foreach ($options as $key => $value) {
			if (is_numeric($key)) {
				$option = $key;
			}
		}
		if (!$option) {
			throw new FailedTestException("Could not find any valid qualification to choose from.");
		}

		$this->getBrowser()->select('#id_qualification', $option);

		return $this;
	}

	public function submit()
	{
		sleep(15);
		$this->getBrowser()->click('#submit-form');
		return new YourAccountPage($this->getBrowser(), $this->getSecrets());
	}
}