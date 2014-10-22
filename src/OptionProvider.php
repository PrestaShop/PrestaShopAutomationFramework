<?php

namespace PrestaShop;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

class OptionProvider
{
    public static function getDescriptions($type)
    {
        static $options = [
            'ShopInstallation' => [
                'language' => ['description' => 'Installation language'],
                'shop_name' => ['description' => 'Name of the shop'],
                'main_activity' => ['description' => 'Shop activity'],
                'country' => ['description' => 'Country where the shop is located'],
                'timezone' => ['description' => 'Timezone of the shop'],
                'admin_firstname' => ['description' => 'Firstname of the main shop administrator'],
                'admin_lastname' => ['description' => 'Lastname of the main shop administrator'],
                'admin_email' => ['description' => 'Email/Login of the main shop administrator account'],
                'admin_password' => ['description' => 'Password of the main shop administrator account'],
                'newsletter' => ['description' => 'Sign-up to the newsletter', 'type' => InputOption::VALUE_NONE],
            ],
            'BackOfficeLogin' => [
                'admin_email' => [],
                'admin_password' => [],
                'stay_logged_in' => ['description' => 'Stay logged in (Back-Office)', 'default' => true]
            ],
            'FrontOfficeLogin' => [
                'customer_email' => [],
                'customer_password' => []
            ]
        ];

        return static::prepare($options[$type]);

    }

    private static function getDefaults($name, $prop)
    {
        static $defaults = [
            'language' => ['short' => 'l', 'default' => 'en'],
            'shop_name' => ['default' => 'PrestaShop'],
            'country' => ['short' => 'c', 'default' => 'us'],
            'admin_firstname' => ['default' => 'John'],
            'admin_lastname' => ['default' => 'Doe'],
            'admin_email' => ['default' => 'pub@prestashop.com'],
            'admin_password' => ['default' => '123456789'],
            'customer_email' => ['default' => 'pub@prestashop.com'],
            'customer_password' => ['default' => '123456789']
        ];

        if (isset($defaults[$name]) && isset($defaults[$name][$prop]))
            return $defaults[$name][$prop];

        return false;
    }

    public static function fromInput($type, InputInterface $input)
    {
        $options = [];
        foreach (static::getDescriptions($type) as $name => $desc) {
            $options[$name] = $input->getOption($name);
        }

        return $options;
    }

    public static function addDefaults($type, $options)
    {
        foreach (static::getDescriptions($type) as $name => $desc) {
            if (empty($options[$name]))
                $options[$name] = $desc['default'];
        }

        return $options;
    }

    private static function prepare($options)
    {
        foreach ($options as $key => $data) {
            foreach (['description' => null, 'short' => null, 'default' => null, 'type' => InputOption::VALUE_REQUIRED] as $prop => $def) {
                if (!isset($data[$prop])) {
                    if ($v = static::getDefaults($key, $prop))
                        $options[$key][$prop] = $v;
                    else
                        $options[$key][$prop] = $def;
                }
            }
        }

        return $options;
    }
}
