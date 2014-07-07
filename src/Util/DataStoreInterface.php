<?php

namespace PrestaShop\Util;

interface DataStoreInterface
{
    public function get($key);
    public function set($key, $value);
    public function toArray();
}
