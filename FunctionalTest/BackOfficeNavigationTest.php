<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\LazyTestCase;

class BackOfficeNavigationTest extends LazyTestCase
{
    public function testLogin()
    {
        $this->shop->getBackOfficeNavigator()->login();
    }

    public function testMenuLinks()
    {
        $links = $this->shop->getBackOfficeNavigator()->getMenuLinks();

        $this->assertArrayHasKey('AdminDashboard', $links);
        $this->assertArrayHasKey('AdminProducts', $links);
        $this->assertArrayHasKey('AdminCustomers', $links);
        $this->assertArrayHasKey('AdminOrders', $links);
        $this->assertArrayHasKey('AdminModules', $links);
        $this->assertArrayHasKey('AdminCartRules', $links);
        $this->assertArrayHasKey('AdminPreferences', $links);
        $this->assertArrayHasKey('AdminPPreferences', $links);

        $this->shop->getBackOfficeNavigator()->visit('AdminProducts');
    }

    /**
	* @expectedException PrestaShop\PSTAF\Exception\AdminControllerNotFoundException
	*/
    public function testNonExistingMenuLinkIsNotFound()
    {
        $this->shop->getBackOfficeNavigator()->visit('ThisControllerIsVeryUnlikelyToExist');
    }
}
