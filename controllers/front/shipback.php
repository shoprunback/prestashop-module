<?php
class ShopRunBackShipbackModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->addCSS(_PS_MODULE_DIR_ . '/shoprunback/views/css/srbGlobal.css');
        $this->actionResult = false;

        if ($_GET && isset($_GET['action'])) {
            $function = $_GET['action'];

            if (strpos($function, 'ajax') >= 0) {
                $this->ssl = true;
            }

            $this->actionResult = $this->{$function}();
        }
    }

    public function ajaxCreateShipback()
    {
        $redirectUrl = $this->context->link->getPageLink('index') . '?controller=order-detail';

        if (!isset($_GET['orderId'])) {
            echo $redirectUrl;
            return;
        }

        $redirectUrl .= '&id_order=' . $_GET['orderId'];

        try {
            $shipback = SRBShipback::createShipbackFromOrderId($_GET['orderId']);
        } catch (\shoprunback\Error\Error $e) {
            echo $redirectUrl;
            return;
        }

        // Mandatory to prevent "Missing var" error
        $this->context->smarty->assign('newTabUrl', $shipback->public_url);
        $this->context->smarty->assign('redirectUrl', $redirectUrl);
        // parent::initContent();
        // Mandatory to prevent "Missing template name" error
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:' . $this->module->name . '/views/templates/front/redirect.tpl');
        } else {
            $this->setTemplate('redirect.tpl');
        }

        echo json_encode([
            'shipbackPublicUrl' => $shipback->public_url,
            'redirectUrl' => $redirectUrl
        ]);
    }
}
