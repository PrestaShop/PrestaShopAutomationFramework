<?php

namespace PrestaShop\PSTAF\ShopCapability;

use PrestaShop\PSTAF\OptionProvider;

class BackOfficeNavigation extends ShopCapability
{
    public static $crud_url_settings;
    private $controller_links;
    private $default_id_lang;

    public function setup()
    {
        static::$crud_url_settings = [
            'AdminTaxes' => ['object_name' => 'tax'],
            'AdminTaxRulesGroup' => ['object_name' => 'tax_rules_group'],
            'AdminCategories' => ['object_name' => 'category'],
            'AdminProducts' => ['object_name' => 'product'],
            'AdminCarriers' => ['object_name' => 'carrier'],
            'AdminOrders' => ['object_name' => 'order'],
            'AdminCartRules' => ['object_name' => 'cart_rule'],
            'AdminCarriers' => ['object_name' => 'carrier']
        ];
    }

    /**
	* Returns an array with controller names as key and URLs as values.
	* Assumes the browser is on a Back-Office page
	*/
    public function getMenuLinks()
    {
        $links = [];

        $browser = $this->getShop()->getBrowser();
        $maintabs = $browser->find('li.maintab', ['unique' => false]);
        foreach ($maintabs as $maintab) {
            foreach ($maintab->all('a') as $a) {
                $href = $a->getAttribute('href');
                $m = [];
                if (preg_match('/\?controller=(\w+)\b/', $href, $m)) {
                    $links[$m[1]] = $href;
                }
            }
        }

        return $links;
    }

    public function deleteEntityById($controller_name, $id)
    {
        $url = $this->getCRUDLink($controller_name, 'delete', $id);
        $this->getBrowser()
        ->visit($url)
        ->ensureStandardSuccessMessageDisplayed();

        return $this;
    }

    /**
	 * Construct a normalized link to a CRUD action
	 *
	 * @param  string $controller_name
	 * @param  string $action [new, view, edit, delete]
	 * @param  int $id optional id of entity
	 * @return string
	 */
    public function getCRUDLink($controller_name, $action = null, $id = null)
    {
        if (isset(static::$crud_url_settings[$controller_name]) || $action === null) {
            if (!$this->controller_links)
                $this->controller_links = $this->getMenuLinks();

            $data = isset(static::$crud_url_settings[$controller_name]) ? static::$crud_url_settings[$controller_name] : null;

            if ($id === null && $action !== null && $action !== 'new')
                throw new \Exception('Missing id parameter for action other than `new`.');

            if (!isset($this->controller_links[$controller_name]))
                throw new \PrestaShop\PSTAF\Exception\AdminControllerNotFoundException($controller_name);

            $base = $this->controller_links[$controller_name];

            if ($action === null)
                return $base;

            $actmap = [
                'new' => 'add'.$data['object_name'],
                'view' => 'view'.$data['object_name'],
                'edit' => 'update'.$data['object_name'],
                'delete' => 'delete'.$data['object_name']
            ];

            if (!isset($actmap[$action]))
                throw new \Exception(sprintf('Unknown action %s.', $action));

            $link = $base.'&'.$actmap[$action];

            if ($action !== 'new')
                $link .= '&id_'.$data['object_name'].'='.$id;

            return $link;
        } else
            throw new \Exception(sprintf('CRUD parameters for %s are not defined.', $controller_name));
    }

    /**
	 * Logs in to the back-office.
	 * Options may include: admin_email, admin_password, stay_logged_in
	 */
    public function login($options = [])
    {
        $options = $this->getOptionProvider()->getValues('BackOfficeLogin', $options);

        $browser = $this->getShop()->getBrowser();
        $browser
        ->visit($this->getShop()->getBackOfficeURL())
        ->waitFor('#email', 15) // yeah, takes time sometimes
        ->fillIn('#email', $options['admin_email'])
        ->fillIn('#passwd', $options['admin_password'])
        ->checkbox('#stay_logged_in', $options['stay_logged_in'])
        ->clickButtonNamed('submitLogin');

        try {
            $browser->ensureElementShowsUpOnPage('#maintab-AdminDashboard', 15);
        } catch (\Exception $e) {
            throw new \PrestaShop\PSTAF\Exception\CouldNotLoginToTheBackOfficeException();
        }

        $this->default_id_lang = $this->getShop()->getPageObject('AdminLocalization')
                                      ->visit()
                                      ->getDefaultLanguageId();

        if (!$this->controller_links)
                $this->controller_links = $this->getMenuLinks();

        return $this;
    }

    public function getDefaultIdLang()
    {
        return $this->default_id_lang;
    }

    /**
	* Visit a controller page
	* e.g. AdminDashboard
	*
	* Preconditions: be on a back-office page
	*/
    public function visit($controller_name, $action = null, $id = null)
    {
        $browser = $this->getShop()->getBrowser();

        return $browser->visit($this->getCRUDLink($controller_name, $action, $id));
    }

    public function visitModuleConfigurationPage($module_name)
    {
        $browser = $this->getShop()->getBrowser();
        $url = $this->getCRUDLink('AdminModules', null, null).'&configure='.$module_name;

        return $browser->visit($url);
    }

    public function installModule($module_name)
    {
        $browser = $this->getShop()->getBrowser();
        $url = $this->getCRUDLink('AdminModules', null, null).'&install='.$module_name;

        return $browser->visit($url);
    }

    public function deleteModule($module_name)
    {
        $browser = $this->getShop()->getBrowser();
        $url = $this->getCRUDLink('AdminModules', null, null).'&delete='.$module_name.'&module_name='.$module_name;

        return $browser->visit($url);
    }
}
