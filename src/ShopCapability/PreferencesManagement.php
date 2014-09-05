<?php

namespace PrestaShop\ShopCapability;

class PreferencesManagement extends ShopCapability
{
	public function setRoundingMode($str)
	{
		$mapping = [
			'up' => 0,
			'down' => 1,
			'half_up' => 2,
			'half_down' => 3,
			'half_even' => 4,
			'half_odd' => 5
		];
		
		if (!isset($mapping[$str]))
			throw new \Exception("Invalid rounding mode: $str.");


		$this->getShop()
		->getBackOfficeNavigator()
		->visit('AdminPreferences')
		->select('#PS_PRICE_ROUND_MODE', $mapping[$str])
		->clickButtonNamed('submitOptionsconfiguration')
		->ensureStandardSuccessMessageDisplayed();

		return $this;
	}

	public function setRoundingType($str)
	{
		$mapping = [
			'item' => 1,
			'line' => 2,
			'total' => 3
		];
		
		if (!isset($mapping[$str]))
			throw new \Exception("Invalid rounding trype: $str.");


		$this->getShop()
		->getBackOfficeNavigator()
		->visit('AdminPreferences')
		->select('#PS_ROUND_TYPE', $mapping[$str])
		->clickButtonNamed('submitOptionsconfiguration')
		->ensureStandardSuccessMessageDisplayed();

		return $this;
	}
}