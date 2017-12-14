<?php

include_once 'SRBObject.php';
include_once 'SRBCustomer.php';
include_once 'SRBItem.php';

class SRBOrder extends SRBObject
{
    public $ordered_at;
    public $customer;
    public $order_number;
    public $items;

    public function __construct ($psOrder) {
        $this->ps = $psOrder;
        $this->order_number = $this->extractOrderNumber($psOrder);
        $this->ordered_at = $psOrder['date_add'];
        $this->customer = SRBCustomer::createFromOrder($psOrder);
        $this->items = SRBItem::createItemsFromOrder($this->order_number);
    }

    static public function getSRBApiCallType () {
        return 'order';
    }

    static public function getIdentifier () {
        return 'order_number';
    }

    static public function getDisplayNameAttribute () {
        return 'order_number';
    }

    static public function getTableName () {
        return 'o';
    }

    static public function getIdColumnName () {
        return 'id_order';
    }

    public function getProducts () {
        $products = [];
        foreach ($this->items as $item) {
            $products[] = $item->product;
        }

        return $products;
    }

    static public function syncAll ($newOnly = false) {
        $orders = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($orders as $order) {
            $responses[] = $order->sync();
        }

        return $responses;
    }

    public function sync () {
        return Synchronizer::sync($this, 'order');
    }

    // SQL object extractors

    static private function extractOrderNumber ($psOrderArrayName) {
        return isset($psOrderArrayName['id_order']) ? $psOrderArrayName['id_order'] : $psOrderArrayName['id'];
    }

    // private (class) methods

    static public function createFromReturn ($return) {
        return new self($return);
    }

    static public function addComponentsToQuery ($sql) {
        $sql->select(self::getTableName() . '.*, c.*, a.*, s.name as stateName, co.*');
        $sql->innerJoin('customer', 'c', self::getTableName() . '.id_customer = c.id_customer');
        $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->from('orders', self::getTableName());
        $sql = self::addComponentsToQuery($sql);

        return $sql;
    }
}
