<?php

namespace PrestaShop\PSTAF\ShopCapability;

abstract class ShopCapability
{
    protected $shop;

    public function __construct($shop)
    {
        $this->shop = $shop;
    }

    /**
	* Called immediately after construction, do your initializations here.
	*/
    public function setup()
    {
        
    }

    public function getShop()
    {
        return $this->shop;
    }

    public function getBrowser()
    {
        return $this->getShop()->getBrowser();
    }

    public function getOptionProvider()
    {
        return $this->getShop()->getOptionProvider();
    }

    public function isAvailable()
    {
        return true;
    }

    /**
	* Return correct name for input field in the Back-Office:
	* name => name_1 if active language has id 1, name_2 if it has id 2, etc.
	*/
    public function i18nFieldName($name)
    {
        // TODO: Implement
        return $name.'_1';
    }

    /**
	* Return standardized value from localized representation
	* Almost useless right now, but will become useful once CLDR is implemented in PrestaShop
	*/
    public function i18nParse($value, $type = 'float')
    {
        if ($type === 'float') {
            return (float) $value;
        } elseif ($type === 'percent') {
            return $this->i18nParse(trim(str_replace('%', '', $value)), 'float');
        } else {
            return $value;
        }
    }

    public function shopVersionBefore($v)
    {
        return version_compare($this->getShop()->getPrestaShopVersion(), $v, '<');
    }
}
