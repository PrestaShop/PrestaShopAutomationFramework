<?php

namespace PrestaShop\ShopCapability;

class InformationRetrieval extends ShopCapability
{
	public function getCountries()
	{
		$this
		->getShop()
		->getBackOfficeNavigator()
		->visit('AdminCountries');

		$p = $this->getShop()->getBackOfficePaginator()
							 ->getPaginatorFor('AdminCountries');

		$list = $p->scrapeAll();

		$countries = [];

		foreach ($list as $country)
		{
			$countries[strtolower($country['iso_code'])] = $country;
		}

		return $countries;
	}
}
