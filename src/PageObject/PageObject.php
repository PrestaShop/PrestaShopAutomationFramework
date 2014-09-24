<?php

namespace PrestaShop\PageObject;

abstract class PageObject extends \PrestaShop\ShopCapability\ShopCapability
{
	abstract public function visit($url = null);
}