<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\LazyTestCase;
use PrestaShop\PSTAF\Helper\Spinner;

/**
 * This test creates 3 categories, each a child of the previous one,
 * adds the "root" to the blocktopmenu, then
 * goes to the Front-Office and checks the categories are in the menu.
 */

class CategoryManagementTest extends LazyTestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();

        self::getShop()->getBackOfficeNavigator()->login();
    }

    public function testSimpleCreation()
    {
        $shop = self::getShop();
        $shop->getCategoryManager()->createCategory(['name' => 'Hello']);
    }

    public function testCreateTree()
    {
        $shop = self::getShop();
        $cm = $shop->getCategoryManager();

        $cats = ['Selenium Tree Root', 'Selenium Child 1', 'Selenium Child 2'];
        $tree = [2];
        $tree[] = $cm->createCategory(['name' => $cats[0], 'parent' => end($tree)]);
        $tree[] = $cm->createCategory(['name' => $cats[1], 'parent' => end($tree)]);
        $tree[] = $cm->createCategory(['name' => $cats[2], 'parent' => end($tree)]);

        // Add our category to blocktopmenu
        $browser = $shop->getBackOfficeNavigator()
        ->visitModuleConfigurationPage('blocktopmenu');
        $browser
        ->click('#availableItems option[value="CAT'.$tree[1].'"]')
        ->click('#addItem')
        ->click('#module_form_submit_btn');

        $shop->expectStandardSuccessMessage();

        $shop->getFrontOfficeNavigator()->visitHome();

        $found = [];

        $spinner = new Spinner();

        $spinner->assertBecomesTrue(function () use ($browser, $cats, &$found) {
            $browser->hover('#block_top_menu a[title="'.$cats[0].'"]');
            sleep(1);
            $links = $browser->find('#block_top_menu ul.submenu-container a', ['unique' => false]);
            foreach ($links as $n => $link) {
                $text = strtolower(trim($link->getText()));
                if ($text !== '')
                    $found[$text] = true;
            }

            return count($found) > 0;
        });

        foreach ($cats as $n => $cat)
            if ($n > 0 && !isset($found[strtolower(trim($cat))]))
                throw new \PrestaShop\PSTAF\Exception\FailedTestException("Did not find this cat in the topmenu: $cat.");
    }
}
