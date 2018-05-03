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

    // To prevent the display of the template with the ajax's response
    public function initContent()
    {
        die;
    }

    public function ajaxCreateShipback()
    {
        $redirectUrl = $this->context->link->getPageLink('index') . '?controller=order-detail';

        if (!isset($_GET['orderId'])) {
            echo $redirectUrl;
            return true;
        }

        $redirectUrl .= '&id_order=' . $_GET['orderId'];

        try {
            $shipback = SRBShipback::createShipbackFromOrderId($_GET['orderId']);
        } catch (\shoprunback\Error\Error $e) {
            echo $redirectUrl;
            return true;
        }

        echo json_encode([
            'shipbackPublicUrl' => $shipback->public_url,
            'redirectUrl' => $redirectUrl
        ]);
        return true;
    }
}
