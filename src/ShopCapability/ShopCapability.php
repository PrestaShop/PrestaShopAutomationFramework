<?php

namespace PrestaShop\ShopCapability;

abstract class ShopCapability
{
	protected $shop;

	public function __construct($shop)
	{
		$this->shop = $shop;
	}

	public function getShop()
	{
		return $this->shop;
	}
}
