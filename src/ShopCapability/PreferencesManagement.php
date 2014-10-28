<?php

namespace PrestaShop\ShopCapability;

class PreferencesManagement extends ShopCapability
{
    public static function getRoundingModes()
    {
        return [
            'up' => 0,
            'down' => 1,
            'half_up' => 2,
            'half_down' => 3,
            'half_even' => 4,
            'half_odd' => 5
        ];
    }

    public static function getRoundingTypes()
    {
        return [
            'item' => 1,
            'line' => 2,
            'total' => 3
        ];
    }

    public function setRoundingMode($str)
    {
        $mapping = self::getRoundingModes();

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
        $mapping = self::getRoundingTypes();

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

    public function setRoundingDecimals($n)
    {
        $this->getShop()
        ->getBackOfficeNavigator()
        ->visit('AdminPreferences')
        ->fillIn('[name="PS_PRICE_DISPLAY_PRECISION"]', $n)
        ->clickButtonNamed('submitOptionsconfiguration')
        ->ensureStandardSuccessMessageDisplayed();

        if ((int)$this->getBrowser()->getValue('[name="PS_PRICE_DISPLAY_PRECISION"]') !== (int)$n) {
            throw new \Exception("PrestaShop did not store the number of decimals correctly!");
        }

        return $this;
    }
}
