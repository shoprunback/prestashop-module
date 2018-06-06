<?php

use Shoprunback\RestClient;
use Shoprunback\Elements\Account;
use Shoprunback\Elements\Company;

class AdminShoprunbackController extends ModuleAdminController
{
    const SUCCESS_CONFIG = 'success.config';
    const ERROR_NO_TOKEN = 'error.no_token';
    const ELEMENTS_BY_PAGE = 10;

    public $token;
    private $actionResult;

    public function __construct()
    {
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

    private function handleConfig()
    {
        $srbtoken = Tools::getValue('srbtoken');

        if ($srbtoken == '') {
            return false;
        }

        $oldSrbToken = RestClient::getClient()->getToken();

        RestClient::getClient()->setToken($srbtoken);
        $user = Account::getOwn();
        if (!$user) {
            SRBLogger::addLog('Invalid API token: ' . $srbtoken, SRBLogger::WARNING, 'configuration');
            RestClient::getClient()->setToken($oldSrbToken);
            return self::ERROR_NO_TOKEN;
        }

        // If the user switches from a valid token to another valid token, the mapping table must be reset
        if ($oldSrbToken != '' && $oldSrbToken != RestClient::getClient()->getToken()) {
            $this->truncateTables();
        }

        Configuration::updateValue('srbtoken', $srbtoken);
        SRBLogger::addLog('API token saved: ' . substr($srbtoken, 0, 3) . '...' . substr($srbtoken, -3), SRBLogger::INFO, 'configuration');

        $company = Company::getOwn();
        $company->webhook_url = $this->module->webhookUrl;
        $company->save();

        // If the user switches from production to sandbox mode (or the opposite), the mapping table must be reset
        $currentProductionMode = Configuration::get('production');
        if ($currentProductionMode != Tools::getValue('production')) {
            $this->truncateTables();
        }

        // If the application goes to production mode, the PS returns' system must be turned off
        Configuration::updateValue('production', Tools::getValue('production'));
        if (Configuration::get('production') == 1) {
            Configuration::updateValue('PS_ORDER_RETURN', false);
        }

        SRBLogger::addLog('Sandbox mode: ' . Tools::getValue('production'), SRBLogger::INFO, 'configuration');

        return self::SUCCESS_CONFIG;
    }

    private function truncateTables()
    {
        ElementMapper::truncateTable();
        SRBShipback::truncateTable();
        SRBNotification::truncateTable();
    }

    public function initContent()
    {
        $link = Context::getContext()->link;
        parent::initContent();

        $elementType = (isset($_GET['elementType'])) ? $_GET['elementType'] : '';
        $elements = [];
        $template = 'srbManager';
        $message = '';
        $this->context->smarty->assign('elementType', $elementType);

        if ($elementType == 'config') {
            if (Tools::getValue('srbtoken')) {
                $message = $this->handleConfig();

                if ($message == self::ERROR_NO_TOKEN) {
                    $this->context->smarty->assign('messageType', 'danger');
                }
                if ($message == self::SUCCESS_CONFIG) {
                    $this->context->smarty->assign('messageType', 'success');
                }
            }

            $template = 'config';

            $this->getConfigFormValues();

            $this->context->smarty->assign('exportLogsUrl', $this->tabUrl . '&itemType=config&action=exportLogs');

            $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/config.css');
        } else {
            $this->getElements($elementType);
        }

        $notifications = SRBNotification::allUnread();
        if ($notifications) {
            $this->context->smarty->assign('notifications', $notifications);
            $this->context->smarty->assign('putNotificationUrl', $this->tabUrl . '&action=markAsReadNotification');
            $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/admin/notification.js');
        }

        $this->context->smarty->assign('srbtoken', RestClient::getClient()->getToken());
        $this->context->smarty->assign('shoprunbackURL', $this->module->url);
        $this->context->smarty->assign('shoprunbackURLProd', $this->module->urlProd);
        $this->context->smarty->assign('srbManager', $this->tabUrl);
        $this->context->smarty->assign('asyncCall', $this->tabUrl . '&action=asyncCall');
        $this->context->smarty->assign('link', $link);
        $this->context->smarty->assign('template', $template);
        $this->context->smarty->assign('message', $message);
        $this->setTemplate('../../../../modules/' . $this->module->name . '/views/templates/admin/layout.tpl');
    }

    private function getElements($elementType = 'return')
    {
        $externalLink = $this->module->url;
        $countElements = 0;
        $searchCondition = false;

        $currentPage = (isset($_GET['currentPage'])) ? $_GET['currentPage'] : 1;
        $class = 'SRBShipback';
        $function = 'getAllByCreateDate';
        $this->context->smarty->assign('actionUrl', Context::getContext()->link->getAdminLink('AdminShoprunback') . '&elementType=' . $elementType);

        $searchCustomer = 'customer';
        $this->context->smarty->assign('searchCustomerName', $searchCustomer);
        $this->context->smarty->assign('searchCustomer', Tools::getValue($searchCustomer));

        switch ($elementType) {
            case 'return':
                $externalLink .= '/shipbacks/';
                $searchOrderReference = 'orderReference';
                $this->context->smarty->assign('searchOrderReferenceName', $searchOrderReference);
                $this->context->smarty->assign('searchOrderReference', Tools::getValue($searchOrderReference));

                if (Tools::getValue($searchOrderReference) !== false) {
                    $searchCondition = $searchOrderReference;
                    $function = 'getLikeOrderReferenceByCreateDate';
                    $countElements = SRBShipback::getCountLikeOrderReferenceByCreateDate(Tools::getValue($searchCondition));
                } elseif (Tools::getValue($searchCustomer) !== false) {
                    $searchCondition = $searchCustomer;
                    $function = 'getLikeCustomerByCreateDate';
                    $countElements = SRBShipback::getCountLikeCustomerByCreateDate(Tools::getValue($searchCondition));
                } else {
                    $countElements = SRBShipback::getCountAll();
                }
                break;
            case 'brand':
                $externalLink .= '/brands/';
                $countElements = SRBBrand::getCountAll();
                $class = 'SRBBrand';
                $function = 'getAllWithMapping';
                break;
            case 'product':
                $externalLink .= '/products/';
                $class = 'SRBProduct';
                $searchLabel = 'label';
                $this->context->smarty->assign('searchName', $searchLabel);
                $this->context->smarty->assign('search', Tools::getValue($searchLabel));

                if (Tools::getValue($searchLabel) !== false) {
                    $searchCondition = $searchLabel;
                    $function = 'getLikeLabel';
                    $countElements = SRBProduct::getCountLikeLabel(Tools::getValue($searchLabel));
                } else {
                    $function = 'getAllWithMapping';
                    $countElements = SRBProduct::getCountAll();
                }
                break;
            case 'order':
                $externalLink .= '/orders/';
                $class = 'SRBOrder';
                $searchOrderNumber = 'orderNumber';
                $this->context->smarty->assign('searchOrderNumberName', $searchOrderNumber);
                $this->context->smarty->assign('searchOrderNumber', Tools::getValue($searchOrderNumber));

                if (Tools::getValue($searchOrderNumber) !== false) {
                    $searchCondition = $searchOrderNumber;
                    $function = 'getLikeOrderNumber';
                    $countElements = SRBOrder::getCountLikeOrderNumber(Tools::getValue($searchOrderNumber));
                } elseif (Tools::getValue($searchCustomer) !== false) {
                    $searchCondition = $searchCustomer;
                    $function = 'getLikeCustomer';
                    $countElements = SRBOrder::getCountLikeCustomer(Tools::getValue($searchCondition));
                } else {
                    $function = 'getAllWithMapping';
                    $countElements = SRBOrder::getCountAll();
                }
                break;
            default:
                Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminShoprunback') . '&elementType=return');
                break;
        }

        $pages = ceil($countElements / self::ELEMENTS_BY_PAGE);
        $currentPage = ($currentPage <= $pages) ? $currentPage : 1;
        $elementMin = ($currentPage - 1) * self::ELEMENTS_BY_PAGE;

        $elements = $searchCondition ?
            $class::$function(Tools::getValue($searchCondition), false, self::ELEMENTS_BY_PAGE, $elementMin, false) :
            $class::$function(false, self::ELEMENTS_BY_PAGE, $elementMin, false);

        if ($elementType == 'product') {
            $noBrand = [];
            foreach ($elements as $product) {
                if (is_null($product->brand)) {
                    $noBrand[] = $product->getDBId();
                }
            }

            $this->context->smarty->assign('noBrand', $noBrand);
        }

        $this->context->smarty->assign('pages', $pages);
        $this->context->smarty->assign('currentPage', $currentPage);
        $this->context->smarty->assign('elements', $elements);
        $this->context->smarty->assign('externalLink', $externalLink);
        $this->context->smarty->assign('searchCondition', $searchCondition);
        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/admin/srbManager.js');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/admin/srbManager.css');
    }

    private function getConfigFormValues()
    {
        $this->context->smarty->assign('PSOrderReturn', Configuration::get('PS_ORDER_RETURN'));
        $this->context->smarty->assign('formActionUrl', $this->tabUrl . '&elementType=config');
        $this->context->smarty->assign('production', Configuration::get('production'));
    }

    public function asyncCall()
    {
        require_once($this->module->SRBModulePath . '/asyncCall.php');
    }

    public function markAsReadNotification()
    {
        return SRBNotification::markAsReadById($_POST['id']);
    }

    public function exportLogs()
    {
        $filename = 'logs_ps_shoprunback_' . Context::getContext()->shop->name . '_' . date('Y-m-d_H.i.s') . '.txt';

        try {
            // TODO Add dynamic values for export
            $logs = SRBLogger::getLogs();

            $content = '';
            foreach ($logs as $log) {
                $content .= '[' . $log['date_add'] . '] ' . "\n";

                if (!empty($log['object_type'])) $content .= 'ObjectType: ' . $log['object_type'] . "\n";

                if (!empty($log['object_id'])) $content .= 'ObjectID: ' . $log['object_id'] . "\n";

                $content .= 'Employee: ' . $log['firstname'] . ' ' . $log['lastname'] . ' <' . $log['email'] . '>' . "\n";
                $content .= 'Message: ' . $log['message'] . "\n\n";
            }

            $file = fopen(_PS_MODULE_DIR_ . $this->module->name . '/' . $filename, 'w');
            fwrite($file, $content);

            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: Binary');
            header('Content-disposition: attachment; filename=' . $filename);
            readfile(_PS_MODULE_DIR_ . $this->module->name . '/' . $filename);

            unlink(_PS_MODULE_DIR_ . $this->module->name . '/' . $filename);
            exit();
        } catch (Exception $e) {
            SRBLogger::addLog('Log export failed: ' . json_encode($e), SRBLogger::FATAL, 'configuration');
        }
    }
}