<?php
class AdminShoprunbackController extends ModuleAdminController
{
    public $token;
    private $actionResult;

    public function __construct() {
        parent::__construct();
        $this->bootstrap = true;
        $this->token = isset($_GET['token']) ? $_GET['token'] : '';
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/srbGlobal.css');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/header.css');
        $this->actionResult = false;

        if ($_GET && isset($_GET['action'])) {
            $function = $_GET['action'];
            $this->actionResult = $this->{$function}();
        }
    }

    private function handleConfig () {
        $srbtoken = isset($_POST['srbtoken']) ? $_POST['srbtoken'] : '';

        if ($srbtoken == '') {
            return false;
        }

        $oldsrbToken = '';
        if (Configuration::get('srbtoken')) {
            $oldsrbToken = Configuration::get('srbtoken');
        }

        Configuration::updateValue('srbtoken', $srbtoken);

        $user = json_decode(Synchronizer::APIcall('me', 'GET'));

        if (! $user) {
            Configuration::updateValue('srbtoken', $oldsrbToken);
            return $this->module->displayError($this->l('error.no_token'));
        }

        Synchronizer::APIcall('company', 'PUT', ['webhook_url' => $this->module->webhookUrl]);

        return $this->module->displayConfirmation(sprintf($this->l('success.token'), $user->first_name, $user->last_name));
    }

    public function initContent () {
        $link = new Link();
        parent::initContent();
        $srbManager = Context::getContext()->link->getAdminLink('AdminShoprunback');

        $this->context->smarty->assign('token', Configuration::get('srbtoken'));
        $this->context->smarty->assign('shoprunbackURL', $this->module->url);
        $this->context->smarty->assign('shoprunbackAPIURL', Synchronizer::SRB_API_URL);
        $this->context->smarty->assign('srbManager', $srbManager);
        $this->context->smarty->assign('asyncCall', $srbManager . '&action=asyncCall');
        $this->context->smarty->assign('link', $link);

        $conditionsToSend = '';
        $pages = 1;
        $itemType = (isset($_GET['itemType'])) ? $_GET['itemType'] : 'returns';
        $items = [];
        $template = 'srbManager';
        $externalLink = $this->module->url;
        $message = '';
        $this->context->smarty->assign('itemType', $itemType);

        if ($itemType == 'config') {
            if (Tools::isSubmit('submittoken')) {
                $message = $this->handleConfig();
            }

            $template = 'config';

            $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

            $fieldsForm[0]['form'] = [
                'legend' => [
                    'title' => $this->l('config.form.title'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('config.form.token'),
                        'name' => 'srbtoken',
                        'size' => 40,
                        'required' => true
                    ]
                ],
                'submit' => [
                    'title' => $this->l('config.form.save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ];

            $helper = new HelperForm();

            // Module, token and currentIndex
            $helper->module = $this->module;
            $helper->name_controller = $this->module->name;
            $helper->token = Tools::getAdminTokenLite('AdminShoprunback');
            $helper->currentIndex = $srbManager . '&itemType=config';

            // Language
            $helper->default_form_language = $defaultLang;
            $helper->allow_employee_form_lang = $defaultLang;

            // Title and toolbar
            $helper->title = $this->module->displayName;
            $helper->show_toolbar = true;
            $helper->toolbar_scroll = true;
            $helper->submit_action = 'submittoken';
            $helper->toolbar_btn = array(
                'save' => array(
                    'desc' => $this->l('config.form.save'),
                    'href' => $helper->currentIndex,
                ),
                'back' => array(
                    'href' => $helper->currentIndex,
                    'desc' => $this->l('config.form.back')
                )
            );

            // Load current value
            $helper->fields_value['srbtoken'] = Configuration::get('srbtoken');

            $this->context->smarty->assign('form', $helper->generateForm(array($fieldsForm[0])));
            $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/config.css');
        } else {
            switch ($itemType) {
                case 'returns':
                    if (Tools::getValue('orderId')) {
                        $items = SRBReturn::getLikeOrderIdByCreateDate(Tools::getValue('orderId'));
                    } else {
                        $items = SRBReturn::getAllByCreateDate();
                    }

                    $conditionsToSend = $this->l('return.description');
                    $externalLink .= '/shipbacks/';

                    $actionUrl = Context::getContext()->link->getAdminLink('AdminShoprunback') . '&itemType=returns';
                    $this->context->smarty->assign('actionUrl', $actionUrl);
                    break;
                case 'products':
                    $items = SRBProduct::getAllWithSRBApiCallQuery();
                    $conditionsToSend = $this->l('product.description');
                    $externalLink .= '/products/';
                    break;
                case 'orders':
                    $items = SRBOrder::getAllWithSRBApiCallQuery();
                    $conditionsToSend = $this->l('order.description');
                    $externalLink .= '/orders/';
                    break;
                case 'brands':
                    $items = SRBBrand::getAllWithSRBApiCallQuery();
                    $conditionsToSend = $this->l('brand.description');
                    $externalLink .= '/brands/';
                    break;
            }

            $itemsByPage = 20;
            $currentPage = (isset($_GET['currentPage'])) ? $_GET['currentPage'] : 1;

            $countItems = count($items);
            $pages = ceil($countItems / $itemsByPage);
            $currentPage = ($currentPage <= $pages) ? $currentPage : 1;

            $itemMin = ($currentPage - 1) * $itemsByPage;
            $itemMax = $currentPage * $itemsByPage;
            $itemsToShow = [];
            for ($itemMin; $itemMin < $itemMax; $itemMin++) {
                if (isset($items[$itemMin])) {
                    $itemsToShow[] = $items[$itemMin];
                }
            }

            $this->context->smarty->assign('currentPage', $currentPage);
            $this->context->smarty->assign('pages', $pages);
            $this->context->smarty->assign('items', $itemsToShow);
            $this->context->smarty->assign('conditionsToSend', $conditionsToSend);
            $this->context->smarty->assign('externalLink', $externalLink);
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/admin/srbManager.js');
            $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/srbManager.css');
        }

        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/override.css');
        $this->addCSS('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

        $this->context->smarty->assign('template', $template);
        $this->context->smarty->assign('message', $message);
        $this->setTemplate('../../../../modules/' . $this->module->name . '/views/templates/admin/layout.tpl');
    }

    public function asyncCall () {
        require_once($this->module->SRBModulePath . '/asyncCall.php');
    }

    public function test () {
        // $products = [];
        // $products[] = SRBOrder::getAll();
        $product = SRBProduct::getById(11);
        $result = $product->sync();
        // $result = SRBProduct::getAllWithSRBApiCallQuery();
        // $result = SRBProduct::getAllNotSync();
        // $result = $product->sync();
        // $product = SRBProduct::getById(14);
        // $result = $product->sync();
        // $result = SRBBrand::syncAll();
        // echo '<pre>';print_r($products);echo '</pre>';
        // die;
        // $result = $this->module->postProduct($product);

        // $product = new stdClass();
        // $product->id_product = 15;
        // $product->name = 'New';
        // $product->reference = 'newew';
        // $product->id_manufacturer = 1;
        // $product->height = 1;
        // $product->width = 1;
        // $product->depth = 1;
        // $product->weight = 1;
        // $result = $this->module->postProduct($product);

        // $result = $this->module->postBrand(2);

        // $_POST['action'] = 'syncAll';
        // $_POST['className'] = 'SRBProduct';
        // $_POST['params'] = true;
        // $result = $this->asyncCall();

        // $sql = new DbQuery();
        // $sql->select('o.*, c.*, a.*, s.*, co.*, ca.*');
        // $sql->from('orders', 'o');
        // $sql->innerJoin('customer', 'c', 'c.id_customer = o.id_customer');
        // $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        // $sql->innerJoin('state', 's', 's.id_state = a.id_state');
        // $sql->innerJoin('country', 'co', 's.id_country = co.id_country');
        // $sql->innerJoin('cart', 'ca', 'o.id_cart = ca.id_cart');
        // $sql->where('o.id_order = 3');
        // $order = $this->module->formalizer->arrayToObject(Db::getInstance()->executeS($sql)[0]);
        // $result = $this->module->postOrder($order);

        // $result = $this->module->postAllProducts();

        // $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "srb_api_calls`(
        //     `id_srb_api_calls` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        //     `id_item` INT(11) NOT NULL,
        //     `type` VARCHAR(256) NOT NULL,
        //     `last_sent` DATETIME NOT NULL
        // )";
        // $sql = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "srb_api_calls`";
        // $sqls[] = "CREATE INDEX IF NOT EXISTS index_type_id_item ON TABLE `" . _DB_PREFIX_ . "srb_api_calls` (type, id_item)";
        // echo $sql;

        // $result = Db::getInstance()->Execute($sql);

        echo '<pre>';print_r($result);echo '</pre>';
        // var_dump($result);
        die;
    }
}
