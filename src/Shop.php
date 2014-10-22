<?php

namespace PrestaShop;

class Shop
{
    /**
	* Mysql host
	*/
    protected $mysql_host;

    /**
	* Mysql port
	*/
    protected $mysql_port;

    /**
	* Mysql user
	*/
    protected $mysql_user;

    /**
	* Mysql pass
	*/
    protected $mysql_pass;

    /**
	* Mysql database name
	*/
    protected $mysql_database;

    /**
	* Mysql database prefix
	*/
    protected $database_prefix;

    /**
	* Physical location of the shop in the filesystem
	*/
    protected $filesystem_path;

    /**
	* Front-Office URL
	*/
    protected $front_office_url;

    /**
	* Name of the back-office folder (e.g. admin-dev)
	*/
    protected $back_office_folder_name;

    /**
	* Name of the back-office folder (e.g. admin-dev)
	*/
    protected $install_folder_name;

    /**
	* Version of the PrestaShop software
	*/
    protected $prestashop_version;

    protected $browser;

    protected $data_store;

    /**
	* Capabilities
	*
	* Added with addShopCapability, see below.
	*
	*/
    protected $capabilities = [];
    protected $page_objects = [];

    private $selenium_settings;

    private $temporary = false;

    /**
	* Create a new shop object.
	* Settings come from the "shop" property of the configuration file.
	* Filesystem path is the root of the installation, e.g. /var/www/prestashop
	*/
    public function __construct($shop_settings, $selenium_settings)
    {
        $this->data_store = new Util\DataStore();
        $this->selenium_settings = $selenium_settings;

        $import = [
            'mysql_host',
            'mysql_port',
            'mysql_user',
            'mysql_pass',
            'mysql_database',
            'database_prefix',
            'front_office_url',
            'back_office_folder_name',
            'install_folder_name',
            'prestashop_version',
            'filesystem_path'
        ];

        foreach ($import as $prop) {
            if (isset($shop_settings[$prop]))
                $this->$prop = $shop_settings[$prop];
        }

        $this->addShopCapability('\PrestaShop\ShopCapability\InformationRetrieval', 'getInformationRetriever');
        $this->addShopCapability('\PrestaShop\ShopCapability\ShopInstallation', 'getInstaller');
        $this->addShopCapability('\PrestaShop\ShopCapability\DatabaseManagement', 'getDatabaseManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\FileManagement', 'getFileManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\BackOfficeNavigation', 'getBackOfficeNavigator');
        $this->addShopCapability('\PrestaShop\ShopCapability\FrontOfficeNavigation', 'getFrontOfficeNavigator');
        $this->addShopCapability('\PrestaShop\ShopCapability\BackOfficePagination', 'getBackOfficePaginator');
        $this->addShopCapability('\PrestaShop\ShopCapability\TaxManagement', 'getTaxManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\FixtureManagement', 'getFixtureManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\ProductManagement', 'getProductManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\CarrierManagement', 'getCarrierManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\CartRulesManagement', 'getCartRulesManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\PreferencesManagement', 'getPreferencesManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\CheckoutManagement', 'getCheckoutManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\OrderManagement', 'getOrderManager');
        $this->addShopCapability('\PrestaShop\ShopCapability\CategoryManagement', 'getCategoryManager');
    }

    /**
	* Adds a ShopCapability to the shop. Capabilities are lazy loaded
	* and behave as singletons for this shop instance.
	*/
    public function addShopCapability($classname, $getter)
    {
        $this->capabilities[$getter] = ['classname' => $classname, 'instance' => null];
    }

    public function __call($name, array $arguments)
    {
        if (isset($this->capabilities[$name])) {
            if ($this->capabilities[$name]['instance'] === null) {
                $cap = new $this->capabilities[$name]['classname']($this);
                $cap->setup();
                $this->capabilities[$name]['instance'] = $cap;
            }

            return $this->capabilities[$name]['instance'];
        }

        $class = get_called_class();
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$name() in $file on line $line", E_USER_ERROR);
    }

    public function getPageObject($name)
    {
        if (!isset($this->page_objects[$name])) {
            $class = "\PrestaShop\PageObject\\$name";
            $this->page_objects[$name] = new $class($this);
            $this->page_objects[$name]->setup();
        }

        return $this->page_objects[$name];
    }

    public static function getFromCWD()
    {
        $conf = ConfigurationFile::getFromCWD();
        $shop = new Shop('.', $conf->get('shop'), SeleniumManager::getMyPort());

        return $shop;
    }

    public function getBrowser()
    {
        if (!$this->browser) {
            $this->browser = new Browser($this->selenium_settings);
        }

        return $this->browser;
    }

    public function setBrowser(Browser $browser)
    {
        $this->browser = $browser;

        return $this->browser;
    }

    /**
	* Get the installer URL
	*/
    public function getInstallerURL()
    {
        return rtrim($this->front_office_url, '/').'/'.trim($this->install_folder_name, '/').'/';
    }

    /**
	* Get the Back-Office URL
	*/
    public function getBackOfficeURL()
    {
        return rtrim($this->front_office_url, '/').'/'.trim($this->back_office_folder_name, '/').'/';
    }

    public function getFrontOfficeURL()
    {
        return $this->front_office_url;
    }

    public function getMysqlHost()
    {
        return $this->mysql_host;
    }

    public function getMysqlPort()
    {
        return $this->mysql_port;
    }

    public function getMysqlUser()
    {
        return $this->mysql_user;
    }

    public function getMysqlPass()
    {
        return $this->mysql_pass;
    }

    public function getMysqlDatabase()
    {
        return $this->mysql_database;
    }

    public function getDatabasePrefix()
    {
        return $this->database_prefix;
    }

    public function getDataStore()
    {
        return $this->data_store;
    }

    public function getFilesystemPath()
    {
        return $this->filesystem_path;
    }

    public function getPrestaShopVersion()
    {
        return $this->prestashop_version;
    }

    public function setTemporary($temporary = true)
    {
        $this->temporary = $temporary;

        return $this;
    }

    public function isTemporary()
    {
        return $this->temporary;
    }

    public function expectStandardSuccessMessage()
    {
        $this->getBrowser()
        ->ensureStandardSuccessMessageDisplayed()
        ->ensureStandardErrorMessageNotDisplayed();

        return $this;
    }
}
