<?php

namespace PrestaShop\ShopCapability;

class BackOfficePagination extends ShopCapability
{
	protected $settings;

	public function setup()
	{
		$this->settings = [
			'AdminCountries' => [
				'container_selector' => $this->shopVersionBefore('1.6.0.9') ? '#country' : '#form-country',
				'table_selector' => 'table.table.country',
				'columns' => [
					null,
					'id',
					'name',
					'iso_code',
					'call_prefix',
					'zone',
					['name' => 'enabled', 'type' => 'switch:icon-check']
				]
			],
			'AdminTaxRulesGroup' => [
				'container_selector' => '#content',
				'table_selector' => 'table.table.tax_rule',
				'columns' => [
					null,
					'country',
					'state',
					'zip_code',
					'behavior',
					['name' => 'tax', 'type' => 'i18n:percent'],
					'description',
					null
				]
			]
		];
	}

	public function getPaginatorFor($for)
	{
		if (!isset($this->settings[$for]))
			throw new \Exception('There is no known paginator for '.$for);

		return new Helper\BackOfficePaginator($this, $this->settings[$for]);
	}
}
