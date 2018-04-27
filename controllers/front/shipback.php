<?php
class ShopRunBackShipbackModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/srbGlobal.css');
        $this->actionResult = false;

        if ($_GET && isset($_GET['action'])) {
            $function = $_GET['action'];
            $this->actionResult = $this->{$function}();
        }
    }

    public function initContent()
    {
        $redirectUrl = $this->context->link->getPageLink('index') . '?controller=order-detail';

        if (!isset($_GET['orderId'])) {
            return $redirectUrl;
        }

        $redirectUrl .= '&id_order=' . $_GET['orderId'];
        $shipback = SRBShipback::createShipbackFromOrderId($_GET['orderId']);

        if (!$shipback || isset($shipback->shipback)) {
            return $redirectUrl;
        }

        if ($shipback == 'Order not found') {
            return $this->context->link->getPageLink('index');
        }

        return [
            'shipbackPublicUrl' => $shipback->public_url,
            'redirectUrl' => $redirectUrl
        ];

        $this->context->smarty->assign('newTabUrl', $shipback->public_url);
        $this->context->smarty->assign('redirectUrl', $redirectUrl);
        parent::initContent();

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:' . $this->module->name . '/views/templates/front/redirect.tpl');
        } else {
            $this->setTemplate('redirect.tpl');
        }
    }
}
