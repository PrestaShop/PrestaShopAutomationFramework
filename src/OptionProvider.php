<?php

namespace PrestaShop\PSTAF;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

class OptionProvider
{
    private static $optionClasses = [
        'ShopInstallation' => [
            'language'              => [
                'description'   => 'Installation language',
                'short'         => 'l',
                'default'       => 'en'
            ],
            'shop_name'             => [
                'description'   => 'Name of the shop',
                'default'       => 'PrestaShop'
            ],
            'main_activity'         => [
                'description'   => 'Shop activity'
            ],
            'country'               => [
                'description'   => 'Country where the shop is located',
                'short'         => 'c',
                'default'       => 'us'
            ],
            'timezone'              => [
                'description'   => 'Timezone of the shop'
            ],
            'admin_firstname'       => [
                'description'   => 'Firstname of the main shop administrator',
                'default'       => 'John'
            ],
            'admin_lastname'        => [
                'description'   => 'Lastname of the main shop administrator',
                'default'       => 'Doe'
            ],
            'admin_email'           => [
                'description'   => 'Email/Login of the main shop administrator account',
                'default'       => 'pub@prestashop.com'
            ],
            'admin_password'        => [
                'description'   => 'Password of the main shop administrator account',
                'default'       => '123456789'
            ],
            'newsletter'            => [
                'description'   => 'Sign-up to the newsletter',
                'default'       => false,
                'type'          => InputOption::VALUE_NONE
            ]
        ],
        'BackOfficeLogin' => [
            'admin_email'           => [
                'default'       => 'pub@prestashop.com'
            ],
            'admin_password'        => [
                'default'       => '123456789'
            ],
            'stay_logged_in'        => [
                'description'   => 'Stay logged in (Back-Office)',
                'default'       => true
            ]
        ],
        'FrontOfficeLogin' => [
            'customer_email'        => [
                'default'       => 'pub@prestashop.com'
            ],
            'customer_password'     => [
                'default'       => '123456789'
            ]
        ],
        'CustomerRegistration'  => [
            'customer_email'        => [
                'default'       =>  'carrie.m@example.com'
            ],
            'customer_password' => [
                'default'       =>  'abcdef1234'
            ],
            'customer_firstname' => [
                'default'       =>  'Carrie'
            ],
            'customer_lastname' => [
                'default'       =>  'Murray'
            ],
            'customer_gender'   => [
                'default'       => 'female'
            ],
            'customer_birthdate'   => [
                'default'       => [
                    'day' => 29,
                    'month' => 2,
                    'year' => 1988
                ]
            ],
            'newsletter' => [
                'default' => true
            ],
            'partners' => [
                'default' => true
            ]
        ]
    ];

    private $defaultValues;

    public static function getOptions($type)
    {
        if (!isset(self::$optionClasses[$type])) {
            throw new \Exception("No default options for $type!");
        }
        
        $options = self::$optionClasses[$type];

        $defaultKeys = [
            'description'   => null,
            'short'         => null,
            'default'       => null,
            'type'          => InputOption::VALUE_REQUIRED
        ];

        foreach ($options as $key => $data) {
            foreach ($defaultKeys as $defaultKey => $defaultValue) {
                if (!array_key_exists($defaultKey, $data)) {
                    $options[$key][$defaultKey] = $defaultValue;
                }
            }
        }

        return $options;        
    }

    public function setDefaultValues(array $values = null)
    {
        $this->defaultValues = $values;
    }

    public function getDefaults($type)
    {
        $defaults = static::getOptions($type);

        foreach ($defaults as $key => $data) {
            if ($this->defaultValues && array_key_exists($type, $this->defaultValues)) {
                if (array_key_exists($key, $this->defaultValues[$type])) {
                    $defaults[$key]['default'] = $this->defaultValues[$type][$key];
                }
            }
        }

        return $defaults;
    }

    public function getValues($type, $input)
    {
        $values = [];
        $defaults = $this->getDefaults($type);
        
        foreach ($defaults as $key => $data) {
            if ($input instanceof  InputInterface && $input->hasOption($key)) {
                $values[$key] = $input->getOption($key);
            } elseif (is_array($input) && array_key_exists($key, $input)){
                $values[$key] = $input[$key];
            } else {
                $values[$key] = $data['default'];
            }
        }

        return $values;
    }
}
