<?php

include_once 'SRBObject.php';
include_once 'SRBCustomer.php';
include_once 'SRBItem.php';

use Shoprunback\Elements\Order;
use Shoprunback\Error\NotFoundError;
use Shoprunback\Error\RestClientError;

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
        $this->ordered_at = $this->convertDateFormatForDB($psOrder['date_add']);
        $this->customer = SRBCustomer::createFromOrder($psOrder);
        $this->items = SRBItem::createItemsFromOrderId($this->getDBId());

        $this->attributesToSend = ['order_number', 'ordered_at', 'customer', 'items'];
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

    public function createLibElementFromSRBObject()
    {
        $order = false;
        if ($mapId = SRBMap::getMappingIdIfExists($this->getDBId(), self::getObjectTypeForMapping())) {
            try {
                $order = Order::retrieve($mapId);
                return $order;
            } catch (NotFoundError $e) {

            }
        }

        try {
            $order = Order::retrieve($this->getReference());
            return $order;
        } catch (NotFoundError $e) {

        }

        $order = new Order();
        $order->order_number = $this->order_number;
        $order->ordered_at = $this->ordered_at;
        $order->customer = $this->customer;
        $order->items = $this->items;

        return $order;
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
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        $order = $this->createLibElementFromSRBObject();
        return $order->save();
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

    static public function getAllWithMapping ($onlySyncItems = false, $limit = 0, $offset = 0)
    {
        $sql = self::findAllWithMappingQuery($onlySyncItems, $limit, $offset);
        $sql->select(SRBShipback::getTableName() . '.' . SRBShipback::getIdColumnName() . ', ' . SRBShipback::getTableName() . '.state, os.delivery');
        $sql->leftJoin( // We use leftJoin because orders may not have a return associated
            SRBShipback::SHIPBACK_TABLE_NAME_NO_PREFIX,
            SRBShipback::getTableName(),
            SRBShipback::getTableName() . '.id_order = ' . self::getTableName() . '.' . self::getIdColumnName()
        );
        $sql = self::getComponentsToFindOrderState($sql);

        $items = self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($sql));

        foreach ($items as $key => $item) {
            $items[$key]->id_item_srb = $item->ps['id_item_srb'];
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
        $sql->select(self::getTableName() . '.*, c.id_customer, c.firstname, c.lastname, c.email, a.id_address, a.address1, a.address2, a.postcode, a.city, a.phone, s.name as stateName, co.*');
        $sql->innerJoin('customer', 'c', self::getTableName() . '.id_customer = c.id_customer');
        $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }

    static public function findAllQuery ($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->from('orders', self::getTableName());
        $sql = self::addComponentsToQuery($sql);
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static protected function findAllWithMappingQuery ($onlySyncItems = false, $limit = 0, $offset = 0)
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
                    FROM ' . SRBMap::getMapperTableName() . ' srb
                    WHERE srb.type = "' . $type . '"
                    GROUP BY srb.id_item
            )'
        );
        $sql->groupBy(static::getTableName() . '.' . $identifier);
        $sql->orderBy('srb.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    // Returns the attribute "shipped" of an order
    public function isShipped ()
    {
        $sql = new DbQuery();
        $sql->from('orders', self::getTableName());
        $sql = self::getComponentsToFindOrderState($sql);
        $sql->where('oh.id_order = ' . $this->ps['id_order']);
        $sql->select('os.shipped');

        return Db::getInstance()->getRow($sql)['shipped'];
    }

    // Base query to get the order_state, available by passing through the order_history
    static public function getComponentsToFindOrderState ($sql)
    {
        // LeftJoin because the order may have no history
        // id_order_history is the primary key of order_history
        // Each order can have many lines of history, so we catch the MAX id_order_history to have the most recent state of the order
        $sql->leftJoin(
            'order_history',
            'oh',
            'oh.id_order = ' . self::getTableName() . '.' . self::getIdColumnName() . ' AND oh.id_order_history IN (
                SELECT MAX(oh.id_order_history)
                FROM ps_order_history oh
                GROUP BY id_order
            )'
        );
        // To catch a state, we need to go through an history, so we keep the leftJoin
        $sql->leftJoin(
            'order_state',
            'os',
            'os.id_order_state = oh.id_order_state'
        );

        return $sql;
    }
}
