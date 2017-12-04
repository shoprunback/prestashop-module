<?php
if (! defined('_PS_VERSION_')) {
    exit;
}

include_once 'sqlQueries.php';
include_once 'classes/SRBBrand.php';
include_once 'classes/SRBOrder.php';
include_once 'classes/SRBProduct.php';

class ShopRunBack extends Module {
    public $formalizer;
    public $dirurl;
    public $url;

    public function __construct () {
        // Mandatory parameters
        $this->name = 'shoprunback';
        $this->author = 'ShopRunBack';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->tab = 'administration';
        $this->tabs = [
            'AdminShoprunback' => ['name' => 'ShopRunBack', 'parent' => 'SELL']
        ];

        parent::__construct();

        $this->displayName = $this->trans('ShopRunBack');
        $this->description = $this->trans('ShopRunBack helps you by registering all your products\' updates, additions or deletions');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete ShopRunBack?');
        $this->bootstrap = true;

        // Custom parameters
        $this->dirurl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $this->url = 'http://localhost:3000';
        // $this->url = 'https://dashboard.shoprunback.com';
        $message = '';
        if (Tools::getValue('message') && Tools::getValue('messageType')) {
            $message = $_GET['message'];
            $type = Tools::getValue('messageType');
            $this->context->controller->{$type}[] = $this->trans($message);
        }
    }

    private function installTab($controllerClassName, $tabName, $tabParentControllerName = false) {
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

    private function uninstallTab($controllerClassName) {
        $tab = new Tab((int)Tab::getIdFromClassName($controllerClassName));
        return $tab->delete();
    }

    public function install() {
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
        ) {
            return false;
        }

        return true;
    }

    public function uninstall() {
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
        ) {
            return false;
        }

        return true;
    }

    private function executeQueries ($queries) {
        foreach ($queries as $key => $query) {
            if (! Db::getInstance()->Execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function installSQL () {
        $queries = [];

        $queries[] = createTableQuery();
        $queries[] = createIndexQuery();

        return $this->executeQueries($queries);
    }

    private function uninstallSQL () {
        $queries = [];

        $queries[] = dropTableQuery();

        return $this->executeQueries($queries);
    }

    // Configuration page
    public function getContent() {
        $output = null;

        if (Tools::isSubmit('submittoken')) {
            $moduleName = strval(Tools::getValue($this->name));
            if (! $moduleName || empty($moduleName) || !Validate::isGenericName($moduleName)) {
                $output = $this->displayError($this->trans('Invalid value'));
            } else {
                $oldToken = '';
                if (Configuration::get('token')) {
                    $oldToken = Configuration::get('token');
                }

                Configuration::updateValue('token', $moduleName);

                $user = $this->APIcall('me', 'GET');

                if (! $user) {
                    Configuration::updateValue('token', $oldToken);
                    $output = $this->displayError($this->trans('This token doesn\'t exist'));
                } else {
                    $output = $this->displayConfirmation($this->trans('Token registered, good to see you ' . $user->first_name . ' ' . $user->last_name . '!'));
                }
            }
        }

        return Configuration::get('params') . $output . $this->configForm();
    }

    private function configForm() {
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $this->trans('My ShopRunBack account'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('API Token'),
                    'name' => $this->name,
                    'size' => 40,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->trans('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submittoken';
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->trans('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&savetoken&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->trans('Back to list')
            )
        );

        // Load current value
        $helper->fields_value[$this->name] = Configuration::get('token');

        return $helper->generateForm($fieldsForm);
    }

    public function postBrand ($manufacturer) {
        if (is_numeric($manufacturer)) {
            $manufacturerSql = new DbQuery();
            $manufacturerSql->select('m.*');
            $manufacturerSql->from('manufacturer', 'm');
            $manufacturerSql->where('m.id_manufacturer = ' . $manufacturer);
            $manufacturerFromDB = Db::getInstance()->executeS($manufacturerSql)[0];
            $manufacturer = $this->formalizer->arrayToObject($manufacturerFromDB);
        }

        $manufacturerToSend = $this->formalizer->formalizeBrandForAPI($manufacturer);

        $response = '';
        $manufacturerFromSRB = $this->APIcall('brands/' . $manufacturerToSend->reference, 'GET');
        if ($manufacturerFromSRB != '') {
            $response = $this->APIcall('brands/' . $manufacturerToSend->reference, 'PUT', json_encode($manufacturerToSend));
        } else {
            $response = $this->APIcall('brands', 'POST', json_encode($manufacturerToSend));
        }

        $this->insertApiCallLog($manufacturer, 'manufacturer');

        return $response;
    }

    public function postAllBrands ($newOnly = false) {
        $manufacturerSql = new DbQuery();
        $manufacturerSql->select('m.*');
        $manufacturerSql->from('manufacturer', 'm');
        if ($newOnly) {
            $manufacturerSql->where('m.id_manufacturer NOT IN (
                                            SELECT srb.id_item
                                            FROM ps_srb_api_calls srb
                                            WHERE srb.type = "manufacturer"
                                        )');
        }
        $manufacturers = Db::getInstance()->executeS($manufacturerSql);

        $response = [
            'errors' => ['brand' => []],
            'success' => ['brand' => []]
        ];
        foreach ($manufacturers as $manufacturer) {
            $manufacturerObject = $this->formalizer->arrayToObject($manufacturer);
            $resultBrand = json_decode($this->postBrand($manufacturerObject));

            if (isset($resultBrand->errors)) {
                $response['errors']['brand'][$resultBrand->name] = $resultBrand->errors;
            } elseif ($resultBrand) {
                $response['success']['brand'][$resultBrand->name] = $resultBrand;
            }
        }

        return $response;
    }

    public function postProduct ($product, $brandChecked = false) {
        if (is_numeric($product)) {
            $productSql = new DbQuery();
            $productSql->select('p.*, pl.*');
            $productSql->from('product', 'p');
            $productSql->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product');
            $productSql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
            $productSql->where('p.id_product = ' . $product);
            $productFromDB = Db::getInstance()->executeS($productSql)[0];
            $product = $this->formalizer->arrayToObject($productFromDB);
        }

        $productToSend = $this->formalizer->formalizeProductForAPI($product, (int)Configuration::get('PS_LANG_DEFAULT'));

        if (is_string($productToSend)) {
            return Tools::displayError($productToSend);
        }

        if (! $brandChecked) {
            $postBrandResult = $this->postBrand($productToSend->brand);
        }

        $response = '';
        $productFromSRB = $this->APIcall('products/' . $productToSend->reference, 'GET');
        if ($productFromSRB != '') {
            $response = $this->APIcall('products/' . $productToSend->reference, 'PUT', json_encode($productToSend));
        } else {
            $response = $this->APIcall('products', 'POST', json_encode($productToSend));
        }

        $this->insertApiCallLog($product, 'product');

        return $response;
    }

    public function postAllProducts ($newOnly = false) {
        $result = $this->postAllBrands();

        $productSql = new DbQuery();
        $productSql->select('p.*, pl.*');
        $productSql->from('product', 'p');
        $productSql->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product');
        $productSql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
        if ($newOnly) {
            $productSql->where('p.id_product NOT IN (
                                            SELECT srb.id_item
                                            FROM ps_srb_api_calls srb
                                            WHERE srb.type = "product"
                                        )');
        }
        $products = Db::getInstance()->executeS($productSql);
        var_dump($products);die;

        $response = [
            'errors' => [
                'general' => [],
                'brand' => $result['errors']['brand'],
                'product' => []
            ],
            'success' => [
                'product' => []
            ]
        ];

        foreach ($products as $product) {
            $productObject = $this->formalizer->arrayToObject($product);
            $resultProduct = json_decode($this->postProduct($productObject, true));

            if (isset($resultProduct->errors)) {
                $response['errors']['general'][$productObject->name] = $resultProduct->errors;
            }
            elseif (isset($resultProduct->brand) && isset($resultProduct->brand->errors)) {
                $response['errors']['brand'][$productObject->name] = $resultProduct->brand->errors;
            }
            elseif (isset($resultProduct->product) && isset($resultProduct->brand->errors)) {
                $response['errors']['product'][$productObject->name] = $resultProduct->product->errors;
            }
            else {
                $response['success']['product'][] = $resultProduct;
            }
        }

        return $response;
    }

    public function postOrder ($order) {
        if (is_numeric($order)) {
            $orderSql = new DbQuery();
            $orderSql->select('o.*, c.*, a.*, s.*, co.*');
            $orderSql->from('orders', 'o');
            $orderSql->innerJoin('customer', 'c', 'o.id_customer = c.id_customer');
            $orderSql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
            $orderSql->innerJoin('country', 'co', 'a.id_country = co.id_country');
            $orderSql->leftJoin('state', 's', 'a.id_state = s.id_state');
            $orderSql->where('o.id_order = ' . $order);
            $orderFromDB = Db::getInstance()->executeS($orderSql)[0];
            $order = $this->formalizer->arrayToObject($orderFromDB);
        }
        $orderToSend = $this->formalizer->formalizeOrderForAPI($order);

        if (is_string($orderToSend)) {
            return $orderToSend;
        }

        foreach ($orderToSend->items as $item) {
            $this->postProduct($item->product);
        }

        $response = '';
        $orderFromSRB = $this->APIcall('orders/' . $orderToSend->order_number, 'GET');
        if ($orderFromSRB === '') {
            $response = $this->APIcall('orders', 'POST', json_encode($orderToSend));
            $this->insertApiCallLog($order, 'order');
        }

        return $response;
    }

    public function postAllOrders ($newOnly = false) {
        $orderSql = new DbQuery();
        $orderSql->select('o.*, c.*, a.*, s.*, co.*');
        $orderSql->from('orders', 'o');
        $orderSql->innerJoin('customer', 'c', 'o.id_customer = c.id_customer');
        $orderSql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $orderSql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $orderSql->leftJoin('state', 's', 'a.id_state = s.id_state');
        if ($newOnly) {
            $orderSql->where('o.id_order NOT IN (
                                            SELECT srb.id_item
                                            FROM ps_srb_api_calls srb
                                            WHERE srb.type = "order"
                                        )');
        }
        $orders = Db::getInstance()->executeS($orderSql);

        $response = [
            'errors' => ['orders' => []],
            'success' => ['orders' => []]
        ];
        foreach ($orders as $order) {
            $orderObject = $this->formalizer->arrayToObject($order);
            $resultOrder = json_decode($this->postOrder($orderObject));

            if (isset($resultOrder->errors)) {
                $response['errors']['orders'][$resultOrder->name] = $resultOrder->errors;
            } elseif ($resultOrder) {
                $response['success']['orders'][$resultOrder->name] = $resultOrder;
            }
        }

        return $response;
    }

    public function hookActionProductDelete($params) {
        $product = $params['product'];
        $result = $this->APIcall('products/' . $product->reference, 'DELETE');
    }

    public function hookActionProductUpdate($params) {
        $this->postProduct($params['product']);
    }

    public function hookActionOrderStatusPostUpdate($params) {
        $query = new DbQuery();
        $query->select('o.*, c.*, a.*, s.*, co.*');
        $query->from('orders', 'o');
        $query->innerJoin('customer', 'c', 'o.id_customer = c.id_customer');
        $query->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $query->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $query->leftJoin('state', 's', 'a.id_state = s.id_state');
        $query->where('o.id_order = ' . $params['id_order']);
        $order = $this->formalizer->arrayToObject(Db::getInstance()->executeS($query)[0]);

        $this->postOrder($order);
    }
}
