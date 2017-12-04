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

    static public function syncAll () {
        $orders = self::getAll();

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

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select('o.*, c.*, a.*, s.name as stateName, co.*');
        $sql->from('orders', 'o');
        $sql->innerJoin('customer', 'c', 'o.id_customer = c.id_customer');
        $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }
}
