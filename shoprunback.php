<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShopRunBack extends Module {
    private $url;

    public function __construct () {
        // Mandatory parameters
        $this->name = 'shoprunback';
        $this->author = 'ShopRunBack';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->trans('ShopRunBack');
        $this->description = $this->trans('ShopRunBack allows you to send all your products directly to your ShopRunBack account so you don\'t have to waste your time doing it one by one');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete ShopRunBack?');

        // Custom parameters
        // $this->url = 'https://dashboard.shoprunback.com/api/v1';
        $this->url = 'https://requestb.in/x44ewcx4';
        $this->installTab('AdminShopRunBackManager', 'ShopRunBack Product Manager', 'AdminParentCatalog');
    }

    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install())
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall())
            return false;

        return true;
    }

    public function installTab($ControllerClassName, $tabName, $tabParentControllerName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $ControllerClassName;

        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        $tab->id_parent = 0;
        if ($tabParentControllerName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentControllerName);
        }

        $tab->module = $this->name;

        $tab->add();
    }

    private function APIcall ($path, $type, $json = '') {
        $url = $this->url . '/' . $path;

        $opts = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 30
        ];

        if ($type == 'POST') {
            if (! $json) {
                return false;
            }

            $opts[CURLOPT_POST] = count($json);
            $opts[CURLOPT_POSTFIELDS] = $json;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function postAllProducts () {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('ps_product', 'p');
        $products = Db::getInstance()->executeS($sql);

        $result = $this->APIcall('product', 'POST', json_encode($products));

        if (! $result) {
            return Tools::displayError('An error occured while trying to send your products to ShopRunBack');;
        }

        return true;
    }
}
