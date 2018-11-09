<?php
/**
 * 2007-2018 ShopRunBack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ShopRunBack
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the ShopRunBack module to newer
 * versions in the future.
 *
 * @author ShopRunBack <contact@shoprunback.com>
 * @copyright 2007-2018 ShopRunBack
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of ShopRunBack
 **/

class ShopRunBackShipbackModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/srbGlobal.css');
        $this->actionResult = false;

        if (Tools::getIsset('action')) {
            $function = Tools::getValue('action');
            $this->actionResult = $this->{$function}();
        }
    }

    // To prevent the display of the template with the ajax's response
    public function initContent()
    {
        die;
    }

    public function asyncCreateShipback()
    {
        $redirectUrl = $this->context->link->getPageLink('index') . '?controller=order-detail';

        if (!Tools::getIsset('orderId')) {
            echo $redirectUrl;
            return false;
        }

        $redirectUrl .= '&id_order=' . Tools::getValue('orderId');

        try {
            $shipback = SRBShipback::createShipbackFromOrderId(Tools::getValue('orderId'));
        } catch (\shoprunback\Error\Error $e) {
            echo $redirectUrl;
            return false;
        }

        echo $shipback->public_url;
        return true;
    }
}
