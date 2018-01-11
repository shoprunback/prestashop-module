<?php

class SRBAddress
{
    public $id;
    public $line1;
    public $line2;
    public $zipcode;
    public $country_code;
    public $city;
    public $state;

    public function __construct ($address)
    {
        $this->id = $address['id_address'];
        $this->line1 = $address['address1'];
        $this->line2 = $address['address2'];
        $this->zipcode = $address['postcode'];
        $this->country_code = $address['iso_code'];
        $this->city = $address['city'];
        $this->state = $address['stateName'];
    }

    static public function getTableName ()
    {
        return 'a';
    }

    static public function getIdColumnName ()
    {
        return 'id_address';
    }

    static public function getIdentifier ()
    {
        return 'id';
    }

    static public function getByCustomerId ($customerId)
    {
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS(self::findByCustomerIdQuery($customerId)));
    }

    static public function getByOrderId ($orderId)
    {
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS(self::findByOrderIdQuery($orderId))[0]);
    }

    static public function createFromOrder ($psOrder)
    {
        return new self($psOrder);
    }

    static protected function findAllQuery ()
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, co.*, s.name as stateName');
        $sql->from('address', self::getTableName());
        $sql->innerJoin('country', 'co', self::getTableName() . '.id_country = co.id_country');
        $sql->leftJoin('state', 's', self::getTableName() . '.id_state = s.id_state');

        return $sql;
    }

    static protected function findByCustomerIdQuery ($customerId)
    {
        return self::findAllQuery()->where('id_customer = ' . pSQL($customerId));
    }

    static protected function findByOrderIdQuery ($orderId)
    {
        return self::findAllQuery()->innerJoin('orders', 'o', 'o.id_address_delivery = ' . self::getTableName() . '.id_address')->where('o.id_order = ' . pSQL($orderId));
    }
}
