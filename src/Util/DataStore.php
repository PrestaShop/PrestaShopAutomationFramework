<?php

namespace PrestaShop\Util;

class DataStore implements DataStoreInterface
{
    private $values = [];

    public function get($key)
    {
        $path = explode('.', $key);
        $value = $this->values;
        foreach ($path as $name)
        {
            if (isset($value[$name]))
                $value = $value[$name];
            else
            {
                $value = null;
                break;
            }
        }
        return $value;
    }

    public function set($key, $v)
    {
        $path = explode('.', $key);
        $value = &$this->values;
        foreach ($path as $n => $name)
        {
            if ($n === count($path) - 1)
            {
                $value[$name] = $v;
            }
            elseif (!isset($value[$name]))
            {
                $value[$name] = [];
            }
            $value = &$value[$name];
        }
        return $this;
    }

    public function toArray()
    {
        return $this->values;
    }
}
