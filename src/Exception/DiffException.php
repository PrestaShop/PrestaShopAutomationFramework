<?php

namespace PrestaShop\Exception;

class DiffException extends \Exception
{
    public function __construct($field = null, $expected = null, $actual = null)
    {
        if ($expected) {
            $msg = sprintf('Invalid value for property `%1$s`: expected `%2$s` but got `%3$s`.', $field, $expected, $actual);
            parent::__construct($msg);
        } else {
            parent::__construct();
        }
    }
}
