<?php
if (! defined('_PS_VERSION_')) {
    exit;
}

class shoprunback extends Module {
    private $url;

    public function __construct () {
        // Mandatory parameters
        $this->name = 'shoprunback';
        $this->author = 'ShopRunBack';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->tabs = [];

        parent::__construct();

        $this->displayName = $this->trans('ShopRunBack');
        $this->description = $this->trans('ShopRunBack helps you by registering all your products\' updates, additions or deletions');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete ShopRunBack?');

        // Custom parameters
        // $this->url = 'http://localhost:3000/api/v1';
        $this->url = 'https://dashboard.shoprunback.com/api/v1';
    }

    private function installTab($controllerClassName, $tabName, $tabParentControllerName = false) {
        $tab = new Tab();
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

        if (! parent::install()
            || ! $this->registerHook('actionProductDelete')
            || ! $this->registerHook('actionProductUpdate')
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

        if (! parent::uninstall()
            || ! $this->unregisterHook('actionProductDelete')
            || ! $this->unregisterHook('actionProductUpdate')
        ) {
            return false;
        }

        return true;
    }

    private function APIcall ($path, $type, $json = '') {
        $url = $this->url . '/' . $path;

        $headers = ['accept: application/json'];
        $headers = ['Content-Type: application/json'];

        if (Configuration::get('token')) {
            $headers[] = 'Authorization: Token token=' . Configuration::get('token');
        }

        $opts = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        ];

        if ($type == 'POST' || $type == 'PUT') {
            if (! $json) {
                return false;
            }

            if ($type == 'POST') {
                $opts[CURLOPT_POST] = count($json);
            } elseif ($type == 'PUT') {
                $opts[CURLOPT_PUT] = count($json);
            }

            $opts[CURLOPT_POSTFIELDS] = $json;
        } elseif ($type == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        curl_close($curl);

        if (! $response) {
            return $response;
        }

        return json_decode($response);
    }

    // Configuration page
    public function getContent() {
        $output = null;

        if (Tools::isSubmit('submittoken')) {
            $moduleName = strval(Tools::getValue($this->name));
            if (! $moduleName || empty($moduleName) || !Validate::isGenericName($moduleName)) {
                $output = $this->displayError($this->l('Invalid value'));
            } else {
                $oldToken = '';
                if (Configuration::get('token')) {
                    $oldToken = Configuration::get('token');
                }

                Configuration::updateValue('token', $moduleName);

                $user = $this->APIcall('me', 'GET');

                if (! $user) {
                    Configuration::updateValue('token', $oldToken);
                    $output = $this->displayError($this->l('This token doesn\'t exist'));
                } else {
                    $output = $this->displayConfirmation($this->l('Token registered, good to see you ' . $user->first_name . ' ' . $user->last_name . '!'));
                }
            }
        }

        return $output . $this->configForm();
    }

    private function configForm() {
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('My ShopRunBack account'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('API Token'),
                    'name' => $this->name,
                    'size' => 40,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
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
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&savetoken&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value[$this->name] = Configuration::get('token');

        return $helper->generateForm($fieldsForm);
    }

    private function formalizeProductForAPI ($product) {
        $category = new Category((int)$product->id_category_default, (int)$this->context->language->id);

        if (! $category) {
            return 'Your product needs to have a category to be sent to ShopRunBack';
        }

        $categoryFormalized = new stdClass();
        $categoryFormalized->name = $category->name;
        $categoryFormalized->reference = $category->name;

        $productFormalized = new stdClass();
        $productFormalized->label = $product->name[1];
        $productFormalized->reference = $product->reference;
        $productFormalized->weight_grams = $product->weight*1000;
        $productFormalized->width_mm = $product->width;
        $productFormalized->height_mm = $product->height;
        $productFormalized->length_mm = $product->depth;
        $productFormalized->brand = $categoryFormalized;

        return $productFormalized;
    }

    private function postProduct ($product) {
        $productToSend = $this->formalizeProductForAPI($product);

        if (is_string($productToSend)) {
            return Tools::displayError($productToSend);
        }

        $brandSRB = $this->APIcall('brands/' . $productToSend->brand->reference, 'GET');
        if (! $brandSRB) {
            $SBRcatID = $this->APIcall('brands', 'POST', json_encode($productToSend->brand));
        }

        $callType = 'POST';
        if ($this->APIcall('products/' . $productToSend->reference, 'GET')) {
            $callType = 'PUT';
        }

        return $this->APIcall('products', $callType, json_encode($productToSend));
    }

    private function postAllProducts () {
        $sql = new DbQuery();
        $sql->select('p.*, pl.*');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'pl.id_product=p.id_product');
        $products = Db::getInstance()->executeS($sql);

        foreach ($products as $product) {
            $this->postProduct($product);
        }

        return true;
    }

    public function hookActionProductDelete($params) {
        $product = $params['product'];
        $result = $this->APIcall('products/' . $product->reference, 'DELETE');
    }

    public function hookActionProductUpdate($params) {
        $this->postProduct($params['product']);
    }
}
