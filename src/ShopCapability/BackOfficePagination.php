<?php

namespace PrestaShop\ShopCapability;

class BackOfficePagination extends ShopCapability
{
	protected $settings = [
		'AdminCountries' => [
			'container_selector' => '#form-country',
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
		]
	];

	public function getPaginatorFor($for)
	{
		return new Helper\BackOfficePaginator($this, $this->settings[$for]);
	}
}
