<?php

require_once __DIR__.'/../vendor/autoload.php';

use PrestaShop\PSTAF\Util\DataStore;

class PrestaShopUtilDataStoreTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->store = new DataStore();
    }

    public function testOneLevel()
    {
        $this->store->set('hello', 'world');
        $this->assertEquals('world', $this->store->get('hello'));
    }

    public function testTwoLevels()
    {
        $this->store->set('hello.world', 'hihi');
        $this->assertEquals('hihi', $this->store->get('hello.world'));
    }

    public function testThreeLevels()
    {
        $this->store->set('hello.world.third', 'lala');
        $this->assertEquals('lala', $this->store->get('hello.world.third'));
    }

    public function testThreeLevelsBulkRetrieval()
    {
        $this->store->set('hello.world.third', 'lala');
        $this->assertEquals(['world' => ['third' => 'lala']], $this->store->get('hello'));
    }

    public function testUpdate()
    {
        $this->store->set('a.b.c', 42);
        $this->assertEquals(42, $this->store->get('a.b.c'));
        $this->store->set('a', ['b' => ['c' => 24]]);
        $this->assertEquals(24, $this->store->get('a.b.c'));
    }
}
