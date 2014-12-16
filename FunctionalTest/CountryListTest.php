<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\LazyTestCase;

class CountryListTest extends LazyTestCase
{
    /**
     * @maxattempts 1
     */
    public function testRetrievalOfCountryList()
    {
        $browser = static::getShop()
        ->getBackOfficeNavigator()
        ->login()
        ->visit('AdminCountries');

        $paginator = static::getShop()
        ->getBackOfficePaginator()
        ->getPaginatorFor('AdminCountries');

        $this->assertEquals(1, $paginator->getCurrentPageNumber());

        $this->assertEquals(2, $paginator->getNextPageNumber());

        $this->assertEquals(5, $paginator->getLastPageNumber());

        $paginator->gotoPage(2);
        $this->assertEquals(2, $paginator->getCurrentPageNumber());
        $this->assertEquals(3, $paginator->getNextPageNumber());

        $content = $paginator->scrapeAll();

        $this->assertEquals(244, count($content));
    }
}
