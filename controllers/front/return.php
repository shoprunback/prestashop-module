<?php
class ShopRunBackReturnModuleFrontController extends ModuleFrontController
{
    public function __construct() {
        parent::__construct();
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/srbGlobal.css');
        $this->actionResult = false;

        if ($_GET && isset($_GET['action'])) {
            $function = $_GET['action'];
            $this->actionResult = $this->{$function}();
        }
    }

    public function initContent () {
        $url = $this->context->link->getPageLink('index') . '?controller=order-detail&id_order=' . $_GET['orderId'];
        $return = SRBReturn::createReturnFromOrderId($_GET['orderId']);

        if (! $return) {
            Tools::redirect($url);
        }

        if ($return == 'Order not found') {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            header('Location: ' . $this->context->link->getPageLink('index'));
        }

        $this->context->smarty->assign('returnURL', $return->getPublicUrl());
        $this->context->smarty->assign('url', $url);
        $this->setTemplate('../../../modules/' . $this->module->name . '/views/templates/front/redirect.tpl');
    }
}
