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
        $this->items = SRBItem::createItemsFromOrderId($this->ps['id_order']);
    }

    static public function getMapType () {
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

    public function isDelivered () {
        $sql = new DbQuery();
        $sql->select('os.delivery');
        $sql->from('orders', self::getTableName());
        $sql->leftJoin(
            'order_history',
            'oh',
            'oh.id_order = ' . self::getTableName() . '.' . self::getIdColumnName() . ' AND oh.id_order_history IN (
                SELECT MAX(oh.id_order_history)
                FROM ps_order_history oh
                GROUP BY id_order
            )'
        );
        $sql->leftJoin(
            'order_state',
            'os',
            'os.id_order_state = oh.id_order_state'
        );
        $sql->where('oh.id_order = ' . $this->ps['id_order']);

        return Db::getInstance()->executeS($sql)[0]['delivery'];
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
        Logger::addLog('[ShopRunBack] SYNCHRONIZING ' . self::getMapType() . ' "' . $this->{self::getIdentifier()} . '"', 0, null, self::getMapType(), $this->ps[self::getIdColumnName()], true);
        return Synchronizer::sync($this, self::getMapType());
    }

    static private function extractOrderNumber ($psOrderArrayName) {
        $return = '';

        if (isset($psOrderArrayName['reference'])) {
            $return = $psOrderArrayName['reference'];
        } elseif (isset($psOrderArrayName['id_order'])) {
            $return = $psOrderArrayName['id_order'];
        } else {
            $return = $psOrderArrayName['id'];
        }

        return $return;
    }

    static public function getAllWithSRBApiCallQuery ($onlySyncItems = false) {
        $sql = self::findWithMapQuery($onlySyncItems);
        $sql->select('srbr.id_srb_return, srbr.state, os.delivery');
        $sql->leftJoin(
            SRBReturn::RETURN_TABLE_NAME_NO_PREFIX,
            'srbr',
            'srbr.id_order = ' . self::getTableName() . '.' . self::getIdColumnName()
        );
        $sql->leftJoin(
            'order_history',
            'oh',
            'oh.id_order = ' . self::getTableName() . '.' . self::getIdColumnName() . ' AND oh.id_order_history IN (
                SELECT MAX(oh.id_order_history)
                FROM ps_order_history oh
                GROUP BY id_order
            )'
        );
        $sql->leftJoin(
            'order_state',
            'os',
            'os.id_order_state = oh.id_order_state'
        );
        $items = self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($sql));

        foreach ($items as $key => $item) {
            $items[$key]->last_sent = $item->ps['last_sent'];
            $items[$key]->id_srb_return = $item->ps['id_srb_return'];
            $items[$key]->state = $item->ps['state'];
            $items[$key]->delivery = $item->ps['delivery'];
        }

        return $items;
    }

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

    protected function findWithMapQuery ($onlySyncItems = false) {
        $identifier = static::getIdColumnName();
        $type = static::getMapType();
        $joinType = $onlySyncItems ? 'innerJoin' : 'leftJoin';

        $sql = static::findAllQuery();
        $sql->select('srb.*');
        $sql->{$joinType}(
            SRBMap::MAPPER_TABLE_NAME_NO_PREFIX,
            'srb',
            'srb.id_item = ' . static::getTableName() . '.' . $identifier . '
                AND srb.type = "' . $type . '"
                AND srb.last_sent IN (
                    SELECT MAX(srb.last_sent)
                    FROM ' . SRBMap::MAPPER_TABLE_NAME . ' srb
                    WHERE srb.type = "' . $type . '"
                    GROUP BY srb.id_item
            )'
        );
        $sql->groupBy(static::getTableName() . '.' . $identifier);
        $sql->orderBy('srb.last_sent DESC');

        return $sql;
    }
}
