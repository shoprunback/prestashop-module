<?php

include_once 'SRBAddress.php';

class SRBCustomer
{
    public $first_name;
    public $last_name;
    public $email;
    public $address;
    public $phone;

    public function __construct ($customer)
    {
        $this->id = $this->extractIdFromPSArray($customer);
        $this->first_name = $customer['firstname'];
        $this->last_name = $customer['lastname'];
        $this->email = $customer['email'];
        $this->locale = Configuration::get('PS_LANG_DEFAULT');
    }

    static public function getTableName ()
    {
        return 'c';
    }

    static public function getIdColumnName ()
    {
        return 'id_customer';
    }

    static public function getIdentifier ()
    {
        return 'id';
    }

    static private function extractIdFromPSArray ($psCustomerArrayName)
    {
        return isset($psCustomerArrayName['id_customer']) ? $psCustomerArrayName['id_customer'] : $psCustomerArrayName['id'];
    }

    static protected function findAllQuery ()
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('customer', self::getTableName());

        return $sql;
    }

    static public function createFromOrder ($psOrderArray)
    {
        $customer = new self($psOrderArray);
        $customer->address = SRBAddress::createFromOrder($psOrderArray);
        $customer->phone = $psOrderArray['phone'];

        return $customer;
    }
}
