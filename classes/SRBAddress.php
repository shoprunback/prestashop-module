<?php

include_once 'SRBObject.php';

class SRBAddress extends SRBObject
{
    public $line1;
    public $line2;
    public $zipcode;
    public $country_code;
    public $city;
    public $state;

    public function __construct ($address) {
        $this->id = $address['id_address'];
        $this->line1 = $address['address1'];
        $this->line2 = $address['address2'];
        $this->zipcode = $address['postcode'];
        $this->country_code = $address['iso_code'];
        $this->city = $address['city'];
        $this->state = $address['stateName'];
    }

    static public function getTableName () {
        return 'a';
    }

    static public function getIdColumnName () {
        return 'id_address';
    }

    // SQL object extractors

    static public function getByCustomerId ($customerId) {
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS(self::findByCustomerIdQuery($customerId)));
    }

    static public function getByOrderId ($orderId) {
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS(self::findByOrderIdQuery($orderId))[0]);
    }

    static public function getCustomerPhoneFromOrder ($orderId) {
        return Db::getInstance()->executeS(self::findByOrderIdQuery($customerId))[0]['phone'];
    }

    static public function createFromOrder ($psOrder) {
        return new self($psOrder);
    }

    // private (class) methods

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select('a.*, co.*, s.name as stateName');
        $sql->from('address', 'a');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }

    static protected function findByCustomerIdQuery ($customerId) {
        return self::findAllQuery()->where('id_customer = ' . $customerId);
    }

    static protected function findByOrderIdQuery ($orderId) {
        return self::findAllQuery()->innerJoin('orders', 'o', 'o.id_address_delivery = a.id_address')->where('id_order = ' . $orderId);
    }
}
