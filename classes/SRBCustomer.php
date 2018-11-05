<?php

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
