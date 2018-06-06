<?php

use Shoprunback\Elements\Order as LibOrder;

class SRBOrder extends LibOrder implements PSElementInterface
{
    use PSElementTrait {
        findAllByMappingDateQuery as protected trait_findAllByMappingDateQuery;
    }

    public function __construct($psOrder, $withNestedElements = true)
    {
        $this->ps = $psOrder;
        $this->order_number = $this->extractOrderNumberFromPSArray($psOrder);
        $this->ordered_at = Util::convertDateFormatForDB($psOrder['date_add']);
        $this->customer = SRBCustomer::createFromOrder($psOrder);
        if ($withNestedElements) {
            $this->items = SRBItem::createItemsFromOrderId($this->getDBId());
        }

        if ($srbId = $this->getMapId()) {
            parent::__construct($srbId);
        } else {
            parent::__construct();
        }
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return 'orders';
    }

    public static function getTableName()
    {
        return 'o';
    }

    static public function getIdColumnName()
    {
        return 'id_order';
    }

    static public function getIdentifier()
    {
        return 'order_number';
    }

    static public function getPreIdentifier()
    {
        return 'order_number';
    }

    static public function getDisplayNameAttribute()
    {
        return 'order_number';
    }

    static public function getObjectTypeForMapping()
    {
        return 'order';
    }

    static public function getPathForAPICall()
    {
        return 'orders';
    }

    static public function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->from('orders', self::getTableName());
        $sql = self::addComponentsToQuery($sql);
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function getComponentsForShipbacks($sql)
    {
        $sql->select(SRBShipback::getTableName() . '.' . SRBShipback::getIdColumnName() . ', ' . SRBShipback::getTableName() . '.state, os.delivery');
        $sql->leftJoin( // We use leftJoin because orders may not have a return associated
            SRBShipback::SHIPBACK_TABLE_NAME_NO_PREFIX,
            SRBShipback::getTableName(),
            SRBShipback::getTableName() . '.id_order = ' . self::getTableIdentifier()
        );
        return $sql;
    }

    static public function getAllWithMapping($onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $sql = self::findAllWithMappingQuery($onlySyncElements, $limit, $offset);
        $sql = self::getComponentsForShipbacks($sql);
        $sql = self::getComponentsToFindOrderState($sql);

        return self::addMappingAttributesToElements(self::convertPSArrayToElements(Db::getInstance()->executeS($sql), $withNestedElements));
    }

    // Own functions
    static private function extractOrderNumberFromPSArray($psOrderArrayName)
    {
        if (isset($psOrderArrayName['reference'])){
            return $psOrderArrayName['reference'];
        } elseif (isset($psOrderArrayName['id_order'])) {
            return $psOrderArrayName['id_order'];
        } else {
            return $psOrderArrayName['id'];
        }
    }

    public function getProducts()
    {
        $products = [];
        foreach ($this->items as $item) {
            $products[] = $item->product;
        }

        return $products;
    }

    static public function createFromShipback($shipback, $withNestedElements = true)
    {
        return new self($shipback, $withNestedElements);
    }

    static public function addComponentsToQuery($sql)
    {
        $sql->select(self::getTableName() . '.*, c.id_customer, c.firstname, c.lastname, c.email, a.id_address, a.address1, a.address2, a.postcode, a.city, a.phone, s.name as stateName, co.*');
        $sql->innerJoin('customer', 'c', self::getTableName() . '.id_customer = c.id_customer');
        $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }

    // Returns the attribute "shipped" of an order
    public function isShipped()
    {
        $sql = new DbQuery();
        $sql->from('orders', self::getTableName());
        $sql = self::getComponentsToFindOrderState($sql);
        $sql->where('oh.id_order = ' . $this->ps['id_order']);
        $sql->select('os.shipped');

        return Db::getInstance()->getRow($sql)['shipped'];
    }

    // Base query to get the order_state, available by passing through the order_history
    static public function getComponentsToFindOrderState($sql)
    {
        // LeftJoin because the order may have no history
        // id_order_history is the primary key of order_history
        // Each order can have many lines of history, so we catch the MAX id_order_history to have the most recent state of the order
        $sql->leftJoin(
            'order_history',
            'oh',
            'oh.id_order = ' . self::getTableIdentifier() . ' AND oh.id_order_history IN (
                SELECT MAX(oh.id_order_history)
                FROM ' . _DB_PREFIX_ . 'order_history oh
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

    static public function getCountLikeOrderNumber($orderNumber)
    {
        return self::getCountOfQuery(self::findLikeOrderNumberQuery($orderNumber));
    }

    static public function getLikeOrderNumber($orderNumber, $onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $sql = self::findLikeOrderNumberQuery($orderNumber, $limit, $offset, $onlySyncElements);
        $sql->groupBy(static::getTableIdentifier());
        return self::addMappingAttributesToElements(self::convertPSArrayToElements(Db::getInstance()->executeS($sql), $withNestedElements));
    }

    static public function findAllByMappingDateQuery($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $sql = self::trait_findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        $sql = self::getComponentsForShipbacks($sql);
        $sql = self::getComponentsToFindOrderState($sql);
        return $sql;
    }

    static public function findLikeOrderNumberQuery($orderNumber, $limit = 0, $offset = 0, $onlySyncElements = false)
    {
        $sql = self::findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        $sql->where(static::getTableName() . '.reference LIKE "%' . $orderNumber . '%"');
        return $sql;
    }

    static public function getLikeCustomer($customer, $onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $sql = self::findLikeCustomerQuery($customer, $limit, $offset, $onlySyncElements);
        $sql->groupBy(static::getTableIdentifier());
        return self::addMappingAttributesToElements(self::convertPSArrayToElements(Db::getInstance()->executeS($sql), $withNestedElements));
    }

    static public function getCountLikeCustomer($customer)
    {
        return self::getCountOfQuery(self::findLikeCustomerQuery($customer));
    }

    static public function findLikeCustomerQuery($customer, $limit = 0, $offset = 0, $onlySyncElements = false)
    {
        $sql = self::findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        $sql->where('
            c.firstname LIKE "%' . pSQL($customer) . '%" OR
            c.lastname LIKE "%' . pSQL($customer) . '%" OR
            CONCAT(c.firstname, " ", c.lastname) LIKE "%' . pSQL($customer) . '%"'
        );
        $sql->orderBy('srbm.last_sent_at ASC');

        return $sql;
    }
}