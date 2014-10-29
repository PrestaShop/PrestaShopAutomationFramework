<?php

namespace PrestaShop\PSTAF\PageObject;

abstract class PageObject extends \PrestaShop\PSTAF\ShopCapability\ShopCapability
{
    abstract public function visit($url = null);
}
