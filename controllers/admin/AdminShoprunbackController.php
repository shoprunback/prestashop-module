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
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/override.css');
        $this->addCSS('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
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
            SRBLogger::addLog('Invalid API token: ' . $srbtoken, 'apitoken');
            Configuration::updateValue('srbtoken', $oldsrbToken);
            return 'error.no_token';
        }

        SRBLogger::addLog('API token saved: ' . substr($srbtoken, 0, 3) . '...' . substr($srbtoken, -3), 'apitoken');

        Synchronizer::APIcall('company', 'PUT', ['webhook_url' => $this->module->webhookUrl]);

        Configuration::updateValue('production', Tools::getValue('production'));
        SRBLogger::addLog('Sandbox mode: ' . Tools::getValue('production'), 'production');

        return 'success.token';
    }

    public function initContent () {
        $link = new Link();
        parent::initContent();

        $pages = 1;
        $itemType = (isset($_GET['itemType'])) ? $_GET['itemType'] : 'returns';
        $items = [];
        $template = 'srbManager';
        $message = '';
        $this->context->smarty->assign('itemType', $itemType);

        if ($itemType == 'config') {
            if (Tools::getValue('srbtoken')) {
                $message = $this->handleConfig();

                if ($message == 'error.no_token') {
                    $this->context->smarty->assign('messageType', 'danger');
                }
                if ($message == 'success.token') {
                    $this->context->smarty->assign('messageType', 'success');
                }
            }

            $template = 'config';

            $this->getConfigFormValues();

            $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/config.css');
        } else {
            $this->getItems($itemType);
        }

        $this->context->smarty->assign('token', Configuration::get('srbtoken'));
        $this->context->smarty->assign('shoprunbackURL', $this->module->url);
        $this->context->smarty->assign('shoprunbackURLProd', $this->module->urlProd);
        $this->context->smarty->assign('shoprunbackAPIURL', Synchronizer::SRB_API_URL);
        $this->context->smarty->assign('srbManager', $this->tabUrl);
        $this->context->smarty->assign('asyncCall', $this->tabUrl . '&action=asyncCall');
        $this->context->smarty->assign('link', $link);
        $this->context->smarty->assign('template', $template);
        $this->context->smarty->assign('message', $message);
        $this->setTemplate('../../../../modules/' . $this->module->name . '/views/templates/admin/layout.tpl');
    }

    private function getItems ($itemType = 'returns') {
        $externalLink = $this->module->url;

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

        $this->getPagination($items);

        $this->context->smarty->assign('externalLink', $externalLink);
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/admin/srbManager.js');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/srbManager.css');
    }

    private function getPagination ($items = []) {
        $countItems = count($items);
        $itemsByPage = 10;
        $pages = ceil($countItems / $itemsByPage);
        $currentPage = (isset($_GET['currentPage'])) ? $_GET['currentPage'] : 1;
        $currentPage = ($currentPage <= $pages) ? $currentPage : 1;

        $itemMin = ($currentPage - 1) * $itemsByPage;
        $itemMax = $currentPage * $itemsByPage;
        $itemsToShow = [];
        for ($itemMin; $itemMin < $itemMax; $itemMin++) {
            if (isset($items[$itemMin])) {
                $itemsToShow[] = $items[$itemMin];
            }
        }

        $this->context->smarty->assign('pages', $pages);
        $this->context->smarty->assign('currentPage', $currentPage);
        $this->context->smarty->assign('items', $itemsToShow);
    }

    private function getConfigFormValues () {
        $this->context->smarty->assign('formActionUrl', $this->tabUrl . '&itemType=config');
        $this->context->smarty->assign('srbtoken', Configuration::get('srbtoken'));
        $this->context->smarty->assign('production', Configuration::get('production'));
    }

    public function asyncCall () {
        require_once($this->module->SRBModulePath . '/asyncCall.php');
    }
}
