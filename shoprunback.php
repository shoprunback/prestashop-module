<?php
if (! defined('_PS_VERSION_')) {
    exit;
}

include_once 'lib/shoprunback-php/init.php';

define ('PRODUCTION_MODE', Configuration::get('production'));
define ('DASHBOARD_PROD_URL', \Shoprunback\RestClient::getClient()->getProductionUrl());

\Shoprunback\RestClient::getClient()->setToken(Configuration::get('srbtoken'));
\Shoprunback\RestClient::getClient()->setCustomHeaders(['Prestashop-Version: ' . _PS_VERSION_]);
// We set the production environment by default
\Shoprunback\RestClient::getClient()->useProductionEnvironment();

// Then we check which environment we are on and switch to Sandbox if needed
if (!PRODUCTION_MODE) {
    \Shoprunback\RestClient::getClient()->useSandboxEnvironment();
}

define ('DASHBOARD_URL', \Shoprunback\RestClient::getClient()->getApiBaseUrl());

include_once 'classes/ElementMapper.php';
include_once 'classes/Util.php';

include_once 'classes/PSElementInterface.php';
include_once 'classes/PSElementTrait.php';
include_once 'classes/PSInterface.php';

include_once 'classes/SRBAddress.php';
include_once 'classes/SRBBrand.php';
include_once 'classes/SRBCustomer.php';
include_once 'classes/SRBItem.php';
include_once 'classes/SRBOrder.php';
include_once 'classes/SRBProduct.php';
include_once 'classes/SRBShipback.php';

include_once 'classes/SRBLogger.php';
include_once 'classes/SRBNotification.php';
include_once 'exceptions/ConfigurationException.php';
include_once 'exceptions/OrderException.php';
include_once 'exceptions/BrandException.php';
include_once 'exceptions/ProductException.php';
include_once 'exceptions/ShipbackException.php';
include_once 'sqlQueries.php';

class ShopRunBack extends Module
{
    public $formalizer;
    public $dirurl;
    public $url;
    public $webhookUrl;

    public function __construct()
    {
        // Mandatory parameters
        $this->name = 'shoprunback';
        $this->author = 'ShopRunBack';
        $this->version = '1.0.8';
        $this->ps_versions_compliancy = array('min' => '1.6.0.9');
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

    private function installTab($controllerClassName, $tabConf)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $controllerClassName;

        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabConf['name'];
        }

        $tab->id_parent = 0;
        if (version_compare(_PS_VERSION_, '1.7', '>=') && isset($tabConf['parent'])) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabConf['parent']);
        }

        $tab->module = $this->name;

        return $tab->add();
    }

    private function uninstallTab($controllerClassName)
    {
        $tab = new Tab((int)Tab::getIdFromClassName($controllerClassName));

        for ($i = 0; $i < count($tab->name); $i++) {
            $tabToDelete = new Tab((int)Tab::getIdFromClassName($controllerClassName));
            $tabToDelete->delete();
        }

        return true;
    }

    public function install()
    {
        foreach ($this->tabs as $index => $tab) {
            if (! $this->installTab($index, $tab)) {
                return false;
            }
        }

        if (! $this->installSQL()) {
            return false;
        }

        if (! parent::install()
            || ! $this->registerHook('actionProductAdd')
            || ! $this->registerHook('actionProductDelete')
            || ! $this->registerHook('actionProductUpdate')
            || ! $this->registerHook('actionOrderStatusPostUpdate')
            || ! $this->registerHook('displayBackOfficeHeader')
            || ! $this->registerHook('displayHeader')
            || ! $this->registerHook('displayOrderDetail')
            || ! $this->registerHook('newOrder')
        ) {
            return false;
        }

        Configuration::updateValue('production', false);

        \Shoprunback\RestClient::getClient()->setToken('');
        Configuration::updateValue('srbtoken', '');
        SRBLogger::addLog('Module installed', SRBLogger::INFO);
        return true;
    }

    public function uninstall()
    {
        foreach ($this->tabs as $index => $name) {
            if (! $this->uninstallTab($index)) {
                return false;
            }
        }

        if (! $this->uninstallSQL()) {
            return false;
        }

        if (! parent::uninstall()
            || ! $this->unregisterHook('actionProductAdd')
            || ! $this->unregisterHook('actionProductDelete')
            || ! $this->unregisterHook('actionProductUpdate')
            || ! $this->unregisterHook('actionOrderStatusPostUpdate')
            || ! $this->unregisterHook('displayBackOfficeHeader')
            || ! $this->unregisterHook('displayHeader')
            || ! $this->unregisterHook('displayOrderDetail')
            || ! $this->unregisterHook('newOrder')
        ) {
            return false;
        }

        \Shoprunback\RestClient::getClient()->setToken('');

        SRBLogger::addLog('Module uninstalled', SRBLogger::INFO);
        return true;
    }

    private function executeQueries($queries)
    {
        foreach ($queries as $key => $query) {
            if (! Db::getInstance()->execute($query)) {
                SRBLogger::addLog(Db::getInstance()->getMsgError(), SRBLogger::INFO);
                return false;
            }
        }

        return true;
    }

    private function installSQL()
    {
        $queries = [];

        $queries[] = createTableQuery();

        $indexExists = Db::getInstance()->getValue(checkIfIndexExists());
        SRBLogger::addLog('Count index: ' . $indexExists, SRBLogger::INFO);
        if ($indexExists < 1) {
            $queries[] = createIndexQuery();
        }

        $queries[] = createNotificationTableQuery();
        $queries[] = createReturnTableQuery();
        $queries[] = enableReturns();

        return $this->executeQueries($queries);
    }

    private function uninstallSQL()
    {
        $queries = [];

        $queries[] = dropTableQuery();
        $queries[] = dropNotificationTableQuery();
        $queries[] = dropReturnTableQuery();

        return $this->executeQueries($queries);
    }

    // Redirect to configuration page
    public function getContent()
    {
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminShoprunback') . '&elementType=config');
    }

    public function hookNewOrder ($params)
    {
        if (\Shoprunback\RestClient::getClient()->getToken()) {
            try {
                $order = SRBOrder::getNotSyncById($params['order']->id);
                $order->sync();
            } catch (OrderException $e) {
                return $e;
            }
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (\Shoprunback\RestClient::getClient()->getToken()) {
            try {
                $order = SRBOrder::getById($params['id_order']);
                $order->sync();
            } catch (OrderException $e) {
                return $e;
            }
        }
    }

    public function hookActionProductAdd($params)
    {
        if (\Shoprunback\RestClient::getClient()->getToken()) {
            try {
                $product = SRBProduct::getNotSyncById($params['product']->id);
                $product->sync();
            } catch (ProductException $e) {
                return $e;
            }
        }
    }

    public function hookActionProductUpdate($params)
    {
        if (\Shoprunback\RestClient::getClient()->getToken()) {
            // In 1.7 the productAdd hook doesn't exist, so it's productUpdate that must manage the adding
            try {
                $product = SRBProduct::getById($params['product']->id);
                $product->sync();
            } catch (ProductException $e) {
                try {
                    $product = SRBProduct::getNotSyncById($params['product']->id);
                    $product->sync();
                } catch (ProductException $e) {
                    return $e;
                }
            }
        }
    }

    public function hookActionProductDelete($params)
    {
        if (!SRBProduct::canBeDeleted($params['product']->id)) {
            SRBLogger::addLog($this->l('module.product.ordered'), SRBLogger::FATAL, SRBProduct::getObjectTypeForMapping(), $params['product']->id);
            return;
        }

        if (\Shoprunback\RestClient::getClient()->getToken()) {
            try {
                \Shoprunback\Elements\Product::delete(
                    ElementMapper::getMappingIdIfExists($params['product']->id,
                    SRBProduct::getObjectTypeForMapping())
                );
            } catch (Exception $e) {
                $notification = new SRBNotification();
                $notification->severity = SRBLogger::FATAL;
                $notification->objectType = SRBProduct::getObjectTypeForMapping();
                $notification->objectId = $params['product']->id;

                if (is_a($e, 'Shoprunback\Error\RestClientError')) {
                    switch ($e->httpStatus) {
                        case 403:
                            $notification->message = $this->l('module.product.ordered');
                            break;
                        case 404:
                            $notification->message = $this->l('module.product.unknown');
                            break;
                        default:
                            $notification->message = $e->getMessage();
                            break;
                    }
                } else {
                    $notification->message = $e->getMessage();
                }

                $notification->save();

                return;
            }
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        if (\Shoprunback\RestClient::getClient()->getToken()) {
            try {
                $order = SRBOrder::getById($_GET['id_order']);

                if (!$order->isShipped()) {
                    return false;
                }

                // To work everywhere, we must have something like 'shipback?orderId=ID', and not 'shipback&orderId=ID'
                $this->context->smarty->assign(
                    'createReturnLink',
                    str_replace('shipback', 'shipback?orderId=' . $order->getDBId() . '&action=asyncCreateShipback', $this->context->link->getModuleLink('shoprunback', 'shipback', []))
                );
                $this->context->smarty->assign('srborder', $order);

                $shipback = SRBShipback::getByOrderIdIfExists($_GET['id_order']);
                $this->context->smarty->assign('shipback', $shipback);

                return $this->display(__FILE__, 'orderDetail.tpl');
            } catch (OrderException $e) {
                SRBLogger::addLog(
                    'Error on OrderDetail: Order ' . $_GET['id_order'] . ' not found. It may not have been synchronized.',
                    SRBLogger::ERROR,
                    SRBOrder::getObjectTypeForMapping(),
                    $_GET['id_order']
                );
            }
        }
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->SRBModulePath . '/views/css/srbGlobal.css');
        $this->context->controller->addCSS($this->SRBModulePath . '/views/css/front/orderDetail.css');
        $this->context->controller->addJs($this->SRBModulePath . '/views/js/front/orderDetail.js');
    }

    public function hookDisplayBackOfficeHeader() {
        // Add icon to tab
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->context->controller->addJs($this->SRBModulePath . '/views/js/admin/tab-1.6.js');
        } else {
            $this->context->controller->addJs($this->SRBModulePath . '/views/js/admin/tab-1.7.js');
        }
    }
}
