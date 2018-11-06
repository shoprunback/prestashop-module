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

use Shoprunback\Elements\Customer as LibCustomer;

class SRBCustomer extends LibCustomer implements PSInterface
{
    use PSElementTrait;

    public function __construct($customer)
    {
        $this->ps = $customer;
        $this->id = $this->extractIdFromPSArray($customer);
        $this->first_name = $customer['firstname'];
        $this->last_name = $customer['lastname'];
        $this->email = $customer['email'];
        $this->locale = Configuration::get('PS_LANG_DEFAULT');
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return 'customer';
    }

    public static function getTableName()
    {
        return 'c';
    }

    public static function getIdColumnName()
    {
        return 'id_customer';
    }

    public static function getIdentifier()
    {
        return 'id';
    }

    public static function getPreIdentifier()
    {
        return 'id';
    }

    // Own functions
    private static function extractIdFromPSArray($psCustomerArrayName)
    {
        return isset($psCustomerArrayName['id_customer']) ? $psCustomerArrayName['id_customer'] : $psCustomerArrayName['id'];
    }

    public static function createFromOrder($psOrderArray)
    {
        $customer = new self($psOrderArray);
        $customer->address = SRBAddress::createFromOrder($psOrderArray);
        $customer->phone = $psOrderArray['phone'];

        return $customer;
    }
}
