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
        $this->tabUrl = Context::getContext()->link->getAdminLink('AdminShoprunback');

        if ($_GET && isset($_GET['action'])) {
            $function = $_GET['action'];
            $this->actionResult = $this->{$function}();
        }
    }

    private function handleConfig () {
        $srbtoken = Tools::getValue('srbtoken');

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
            SRBLogger::addLog('Invalid API token: ' . $srbtoken, 1, null, 'apitoken');
            Configuration::updateValue('srbtoken', $oldsrbToken);
            return $this->module->displayError($this->l('error.no_token'));
        }

        SRBLogger::addLog('API token saved: ' . substr($srbtoken, 0, 3) . '...' . substr($srbtoken, -3), 0, null, 'apitoken');

        Synchronizer::APIcall('company', 'PUT', ['webhook_url' => $this->module->webhookUrl]);

        Configuration::updateValue('sandbox', Tools::getValue('sandbox'));
        SRBLogger::addLog('Sandbox mode: ' . Tools::getValue('sandbox'), 0, null, 'sandbox');

        return $this->module->displayConfirmation(sprintf($this->l('success.token'), $user->first_name, $user->last_name));
    }

    public function initContent () {
        $link = new Link();
        parent::initContent();

        $this->context->smarty->assign('token', Configuration::get('srbtoken'));
        $this->context->smarty->assign('shoprunbackURL', $this->module->url);
        $this->context->smarty->assign('shoprunbackAPIURL', Synchronizer::SRB_API_URL);
        $this->context->smarty->assign('srbManager', $this->tabUrl);
        $this->context->smarty->assign('asyncCall', $this->tabUrl . '&action=asyncCall');
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

            $this->getConfigForm();

            $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/config.css');
        } else {
            switch ($itemType) {
                case 'returns':
                    if (Tools::getValue('orderId') !== false) {
                        $items = SRBShipback::getLikeOrderIdByCreateDate(Tools::getValue('orderId'));
                    } elseif (Tools::getValue('customer') !== false) {
                        $items = SRBShipback::getLikeCustomerByCreateDate(Tools::getValue('customer'));
                    } else {
                        $items = SRBShipback::getAllByCreateDate();
                    }

                    $externalLink .= '/shipbacks/';

                    $actionUrl = Context::getContext()->link->getAdminLink('AdminShoprunback') . '&itemType=returns';
                    $this->context->smarty->assign('actionUrl', $actionUrl);
                    $this->context->smarty->assign('searchOrderId', Tools::getValue('orderId'));
                    $this->context->smarty->assign('searchCustomer', Tools::getValue('customer'));
                    break;
                case 'products':
                    $items = SRBProduct::getAllWithMapping();
                    $externalLink .= '/products/';
                    break;
                case 'orders':
                    $items = SRBOrder::getAllWithMapping();
                    $externalLink .= '/orders/';
                    break;
                case 'brands':
                    $items = SRBBrand::getAllWithMapping();
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

    private function getConfigForm () {
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
                ],
                [
                    'type' => 'radio',
                    'label' => $this->l('config.form.sandbox'),
                    'name' => 'sandbox',
                    'required' => true,
                    'values' => [
                        [
                            'id' => 'yes',
                            'value' => 1,
                            'label' => $this->l('config.form.yes')
                        ],
                        [
                            'id' => 'no',
                            'value' => 0,
                            'label' => $this->l('config.form.no')
                        ]
                    ],
                    'is_bool' => true,
                ]
            ],
            'submit' => [
                'title' => $this->l('config.form.save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];
        // $translator = $this->module->getTranslator();
        // var_dump($this->l('config.form.title'));
        // die;

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminShoprunback');
        $helper->currentIndex = $this->tabUrl . '&itemType=config';

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
                'desc' => $this->module->l('config.form.save'),
                'href' => $helper->currentIndex,
            )
        );

        // Load current value
        $helper->fields_value['srbtoken'] = Configuration::get('srbtoken');
        $helper->fields_value['sandbox'] = Configuration::get('sandbox');

        $this->context->smarty->assign('form', $helper->generateForm(array($fieldsForm[0])));
    }

    public function asyncCall () {
        require_once($this->module->SRBModulePath . '/asyncCall.php');
    }
}
