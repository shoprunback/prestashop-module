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

    public function __construct ($psOrder)
    {
        $this->ps = $psOrder;
        $this->order_number = $this->extractOrderNumberFromPSArray($psOrder);
        $this->ordered_at = $psOrder['date_add'];
        $this->customer = SRBCustomer::createFromOrder($psOrder);
        $this->items = SRBItem::createItemsFromOrderId($this->getDBId());
    }

    static public function getObjectTypeForMapping ()
    {
        return 'order';
    }

    static public function getPathForAPICall ()
    {
        return 'orders';
    }

    static public function getIdentifier ()
    {
        return 'order_number';
    }

    static public function getDisplayNameAttribute ()
    {
        return 'order_number';
    }

    static public function getTableName ()
    {
        return 'o';
    }

    static public function getIdColumnName ()
    {
        return 'id_order';
    }

    public function getProducts ()
    {
        $products = [];
        foreach ($this->items as $item) {
            $products[] = $item->product;
        }

        return $products;
    }

    public function sync ()
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->{self::getIdentifier()} . '"', self::getObjectTypeForMapping(), $this->getDBId());
        return Synchronizer::sync($this);
    }

    static private function extractOrderNumberFromPSArray ($psOrderArrayName)
    {
        if (isset($psOrderArrayName['reference'])){
            return $psOrderArrayName['reference'];
        } elseif (isset($psOrderArrayName['id_order'])) {
            return $psOrderArrayName['id_order'];
        } else {
            return $psOrderArrayName['id'];
        }
    }

    static public function getAllWithMapping ($onlySyncItems = false)
    {
        $sql = self::findAllWithMappingQuery($onlySyncItems);
        $sql->select('srbr.id_srb_shipback, srbr.state, os.delivery');
        $sql->leftJoin( // We use leftJoin because orders may not have a return associated
            SRBShipback::SHIPBACK_TABLE_NAME_NO_PREFIX,
            'srbr',
            'srbr.id_order = ' . self::getTableName() . '.' . self::getIdColumnName()
        );
        $sql->leftJoin( // We use leftJoin because orders may not have an history associated
            'order_history',
            'oh',
            'oh.id_order = ' . self::getTableName() . '.' . self::getIdColumnName() . ' AND oh.id_order_history IN (
                SELECT MAX(oh.id_order_history)
                FROM ps_order_history oh
                GROUP BY id_order
            )'
        );
        $sql->leftJoin( // Follows order_history
            'order_state',
            'os',
            'os.id_order_state = oh.id_order_state'
        );
        $items = self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($sql));

        foreach ($items as $key => $item) {
            $items[$key]->last_sent_at = $item->ps['last_sent_at'];
            $items[$key]->id_srb_shipback = $item->ps['id_srb_shipback'];
            $items[$key]->state = $item->ps['state'];
            $items[$key]->delivery = $item->ps['delivery'];
        }

        return $items;
    }

    static public function createFromShipback ($shipback)
    {
        return new self($shipback);
    }

    static public function addComponentsToQuery ($sql)
    {
        $sql->select(self::getTableName() . '.*, c.*, a.*, s.name as stateName, co.*');
        $sql->innerJoin('customer', 'c', self::getTableName() . '.id_customer = c.id_customer');
        $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }

    static protected function findAllQuery ()
    {
        $sql = new DbQuery();
        $sql->from('orders', self::getTableName());
        $sql = self::addComponentsToQuery($sql);

        return $sql;
    }

    protected function findAllWithMappingQuery ($onlySyncItems = false)
    {
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $joinType = $onlySyncItems ? 'innerJoin' : 'leftJoin';

        $sql = static::findAllQuery();
        $sql->select('srb.*');
        $sql->{$joinType}(
            SRBMap::MAPPER_TABLE_NAME_NO_PREFIX,
            'srb',
            'srb.id_item = ' . static::getTableName() . '.' . $identifier . '
                AND srb.type = "' . $type . '"
                AND srb.last_sent_at IN (
                    SELECT MAX(srb.last_sent_at)
                    FROM ' . SRBMap::MAPPER_TABLE_NAME . ' srb
                    WHERE srb.type = "' . $type . '"
                    GROUP BY srb.id_item
            )'
        );
        $sql->groupBy(static::getTableName() . '.' . $identifier);
        $sql->orderBy('srb.last_sent_at DESC');

        return $sql;
    }
}
