<?php
if (! defined('_PS_VERSION_')) {
    exit;
}

define ('PRODUCTION_MODE', Configuration::get('production'));
define ('DASHBOARD_URL', getenv('DASHBOARD_URL') ? getenv('DASHBOARD_URL') : (PRODUCTION_MODE ? 'https://dashboard.shoprunback.com' : 'https://sandbox.dashboard.shoprunback.com'));
define ('DASHBOARD_PROD_URL', 'https://dashboard.shoprunback.com');

include_once 'classes/Synchronizer.php';
include_once 'classes/SRBShipback.php';
include_once 'classes/SRBLogger.php';
include_once 'exceptions/ConfigurationException.php';
include_once 'exceptions/OrderException.php';
include_once 'exceptions/ProductException.php';
include_once 'exceptions/ShipbackException.php';
include_once 'exceptions/SynchronizerException.php';
include_once 'sqlQueries.php';

class ShopRunBack extends Module
{
    public $formalizer;
    public $dirurl;
    public $url;
    public $webhookUrl;

    public function __construct ()
    {
        // Mandatory parameters
        $this->name = 'shoprunback';
        $this->author = 'ShopRunBack';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7.2', 'max' => _PS_VERSION_);
        $this->tab = 'administration';
        $this->tabs = [
            'AdminShoprunback' => ['name' => 'ShopRunBack', 'parent' => 'SELL']
        ];

        parent::__construct();

        $this->displayName = 'ShopRunBack';
        $this->description = $this->l('module.description');
        $this->confirmUninstall = $this->l('module.uninstall.alert');
        $this->bootstrap = true;

        // Custom parameters
        $this->dirurl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $this->SRBModulePath = _PS_MODULE_DIR_ . $this->name;
        $this->webhookUrl = $this->context->link->getModuleLink('shoprunback', 'webhook', []);
        $this->url = DASHBOARD_URL;
        $this->urlProd = DASHBOARD_PROD_URL;
        $message = '';
        if (Tools::getValue('message') && Tools::getValue('messageType')) {
            $message = $_GET['message'];
            $type = Tools::getValue('messageType');
            $this->context->controller->{$type}[] = $this->l($message);
        }
    }

    private function installTab ($controllerClassName, $tabName, $tabParentControllerName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $controllerClassName;

        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        $tab->id_parent = 0;
        if ($tabParentControllerName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentControllerName);
        }

        $tab->module = $this->name;

        return $tab->add();
    }

    private function uninstallTab ($controllerClassName)
    {
        $tab = new Tab((int)Tab::getIdFromClassName($controllerClassName));
        return $tab->delete();
    }

    public function install ()
    {
        foreach ($this->tabs as $index => $tab) {
            if (! $this->installTab($index, $tab['name'], $tab['parent'])) {
                return false;
            }
        }

        if (! $this->installSQL()) {
            return false;
        }

        if (! parent::install()
            || ! $this->registerHook('actionProductDelete')
            || ! $this->registerHook('actionProductUpdate')
            || ! $this->registerHook('actionOrderStatusPostUpdate')
            || ! $this->registerHook('displayAdminProductsMainStepLeftColumnMiddle')
            || ! $this->registerHook('displayOrderDetail')
            || ! $this->registerHook('newOrder')
        ) {
            return false;
        }

        Configuration::updateValue('production', false);

        SRBLogger::addLog('Module installed', SRBLogger::INFO);
        return true;
    }

    public function uninstall ()
    {
        foreach ($this->tabs as $index => $tab) {
            if (! $this->uninstallTab($index)) {
                return false;
            }
        }

        if (! $this->uninstallSQL()) {
            return false;
        }

        if (! parent::uninstall()
            || ! $this->unregisterHook('actionProductDelete')
            || ! $this->unregisterHook('actionProductUpdate')
            || ! $this->unregisterHook('actionOrderStatusPostUpdate')
            || ! $this->unregisterHook('displayAdminProductsMainStepLeftColumnMiddle')
            || ! $this->unregisterHook('displayOrderDetail')
            || ! $this->unregisterHook('newOrder')
        ) {
            return false;
        }

        Configuration::updateValue('srbtoken', '');

        SRBLogger::addLog('Module uninstalled', SRBLogger::INFO);
        return true;
    }

    private function executeQueries ($queries)
    {
        foreach ($queries as $key => $query) {
            if (! Db::getInstance()->execute($query)) {
                SRBLogger::addLog(Db::getInstance()->getMsgError(), SRBLogger::INFO);
                return false;
            }
        }

        return true;
    }

    private function installSQL ()
    {
        $queries = [];

        $queries[] = createTableQuery();

        $indexExists = Db::getInstance()->getValue(checkIfIndexExists());
        SRBLogger::addLog('Count index: ' . $indexExists, SRBLogger::INFO);
        if ($indexExists < 1) {
            $queries[] = createIndexQuery();
        }

        $queries[] = createReturnTableQuery();
        $queries[] = enableReturns();

        return $this->executeQueries($queries);
    }

    private function uninstallSQL ()
    {
        $queries = [];

        $queries[] = dropTableQuery();
        $queries[] = dropReturnTableQuery();

        return $this->executeQueries($queries);
    }

    // Redirect to configuration page
    public function getContent ()
    {
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminShoprunback') . '&itemType=config');
    }

    public function hookActionProductDelete ($params)
    {
        if (Configuration::get('srbtoken')) {
            $productParam = $params['product'];

            $productArray = ['id_product' => $params['id_product']];
            foreach ($productParam as $key => $value) {
                $productArray[$key] = $value;
            }

            $product = new SRBProduct($productArray);

            if ($product) {
                $product->deleteWithCheck();
            }
        }
    }

    public function hookNewOrder ($params)
    {
        if (Configuration::get('srbtoken')) {
            try {
                $order = SRBOrder::getById($params['order']->id);
                $order->sync();
            } catch (OrderException $e) {
                return $e;
            }
        }
    }

    public function hookActionProductUpdate ($params)
    {
        if (Configuration::get('srbtoken')) {
            try {
                $product = SRBProduct::getById($params['product']->id);
                $product->sync();
            } catch (ProductException $e) {
                return $e;
            }
        }
    }

    public function hookActionOrderStatusPostUpdate ($params)
    {
        if (Configuration::get('srbtoken')) {
            try {
                $order = SRBOrder::getById($params['id_order']);
                $order->sync();
            } catch (OrderException $e) {
                return $e;
            }
        }
    }

    public function hookDisplayAdminProductsMainStepLeftColumnMiddle ()
    {
        return $this->display(__FILE__, 'views/templates/admin/product/productMainLeftMiddle.tpl');
    }

    public function hookDisplayOrderDetail ($params)
    {
        if (Configuration::get('srbtoken')) {
            try {
                $order = SRBOrder::getById($_GET['id_order']);

                if (! $order->isShipped()) {
                    return false;
                }

                $srbfcLink = $this->context->link->getModuleLink('shoprunback', 'shipback', []);
                $this->context->smarty->assign('createReturnLink', $srbfcLink);
                $this->context->smarty->assign('order', $order);

                $shipback = SRBShipback::getByOrderIdIfExists($_GET['id_order']);
                $this->context->smarty->assign('shipback', $shipback);

                $srbwebhookLink = $this->webhookUrl;
                $this->context->smarty->assign('webhookLink', $srbwebhookLink);

                $this->context->controller->addJs(_PS_MODULE_DIR_ . $this->name . '/views/js/front/orderDetail.js');
                $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/srbGlobal.css');
                $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/front/orderDetail.css');

                return $this->display(__FILE__, 'orderDetail.tpl');
            } catch (OrderException $e) {
                return $e;
            }
        }
    }
}
