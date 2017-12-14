<?php

include_once 'SRBObject.php';
include_once 'SRBAddress.php';

class SRBCustomer
{
    public $first_name;
    public $last_name;
    public $email;
    public $address;
    public $phone;

    public function __construct ($customer) {
        $this->id = $this->extractId($customer);
        $this->first_name = $customer['firstname'];
        $this->last_name = $customer['lastname'];
        $this->email = $customer['email'];
    }

    static public function getTableName () {
        return 'c';
    }

    static public function getIdColumnName () {
        return 'id_customer';
    }

    static public function getIdentifier () {
        return 'id';
    }

    // SQL object extractors

    static private function extractId ($psCustomerArrayName) {
        return isset($psCustomerArrayName['id_customer']) ? $psCustomerArrayName['id_customer'] : $psCustomerArrayName['id'];
    }

    // private (class) methods

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select('c.*');
        $sql->from('customer', 'c');

        return $sql;
    }

    static public function createFromOrder ($psOrderArray) {
        $customer = new self($psOrderArray);
        $customer->address = SRBAddress::createFromOrder($psOrderArray);
        $customer->phone = $psOrderArray['phone'];

        return $customer;
    }
}
