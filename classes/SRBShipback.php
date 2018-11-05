<?php

use Shoprunback\Elements\Shipback as LibShipback;

class SRBShipback extends LibShipback implements PSElementInterface
{
    use PSElementTrait;

    const SHIPBACK_TABLE_NAME_NO_PREFIX = 'shoprunback_shipbacks';

    public function __construct($psReturn, $withNestedElements = true)
    {
        $this->ps = $psReturn;
        $this->id_srb_shipback = isset($psReturn['id_srb_shipback']) ? $psReturn['id_srb_shipback'] : '';
        $this->order = isset($psReturn['order']) ? $psReturn['order'] : SRBOrder::getById($this->ps['id_order'], $withNestedElements);
        $this->order_id = $this->order->order_number;
        $this->mode = $psReturn['mode'];
        $this->state = $psReturn['state'];
        $this->created_at = Util::convertDateFormatForDB($psReturn['created_at']);
        $this->public_url = $psReturn['public_url'];

        if ($srbId = $this->getMapId()) {
            parent::__construct($srbId);
        } else {
            parent::__construct();
        }
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return self::SHIPBACK_TABLE_NAME_NO_PREFIX;
    }

    public static function getTableName()
    {
        return 'srbs';
    }

    public static function getIdColumnName()
    {
        return 'id_srb_shipback';
    }

    public static function getIdentifier()
    {
        return self::getIdColumnName();
    }

    public static function getPreIdentifier()
    {
        return self::getIdColumnName();
    }

    public static function getDisplayNameAttribute()
    {
        return self::getIdColumnName();
    }

    public static function getObjectTypeForMapping()
    {
        return 'shipback';
    }

    public static function getPathForAPICall()
    {
        return 'shipbacks';
    }

    public static function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = static::getBaseQuery();
        $sql->select(pSQL(self::getTableName()) . '.*, ' . pSQL(SRBOrder::getTableName()) . '.*');
        $sql->innerJoin('orders', pSQL(SRBOrder::getTableName()), pSQL(self::getTableName()) . '.id_order = ' . pSQL(SRBOrder::getTableName()) . '.' . pSQL(SRBOrder::getIdColumnName()));
        $sql->groupBy(pSQL(static::getTableIdentifier()));
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    // Own functions
    public static function getShipbackTableName()
    {
        return _DB_PREFIX_ . self::SHIPBACK_TABLE_NAME_NO_PREFIX;
    }

    public function getShipbackDetails()
    {
        $sql = self::findAllQuery();
        $sql->innerJoin('order_detail', 'od', 'ord.id_order_detail = od.id_order_detail');
        $sql->where(pSQL(self::getTableIdentifier()) . ' = ' . pSQL($this->id));
        $sql->groupBy('ord.id_order_detail');

        return Db::getInstance()->executeS($sql);
    }

    public static function createShipbackFromOrderId($orderId)
    {
        if (self::getByOrderIdIfExists($orderId)) {
            return false;
        }

        $order = SRBOrder::getNotSyncById($orderId);
        if (!$order->isShipped()) {
            return false;
        }

        if (!$order->isPersisted() && !ElementMapper::getMappingIdIfExists($order->id, $order::getObjectTypeForMapping())) {
            $order->sync();
        }

        // If the order already has a shipback
        $retrievedOrder = \Shoprunback\Elements\Order::retrieve($order->order_number);
        if (!is_null($retrievedOrder->shipback)) {
            $psReturn = array(
                'id_srb_shipback' => $retrievedOrder->shipback->id,
                'id_order' => $order->getDBId(),
                'order' => $order,
                'state' => $retrievedOrder->shipback->state,
                'mode' => $retrievedOrder->shipback->mode,
                'created_at' => $retrievedOrder->shipback->created_at,
                'public_url' => $retrievedOrder->shipback->public_url
            );
            $srbShipback = new SRBShipback($psReturn);
            $srbShipback->insertOnPS();

            return $srbShipback;
        }

        $psReturn = array(
            'id_srb_shipback' => 0,
            'id_order' => $orderId,
            'order' => $order,
            'state' => '0',
            'mode' => 'postal',
            'created_at' => date('Y-m-d H:i:s'),
            'public_url' => ''
        );
        $srbShipback = new self($psReturn);

        try {
            $result = $srbShipback->sync();
        } catch (\Shoprunback\Error\Error $e) {
            SRBLogger::addLog('Could not create Shipback on ShopRunBack for order ' . $orderId . '. Object: ' . json_encode($srbShipback) . '. |||| Response: ' . json_encode($e), SRBLogger::FATAL, self::getObjectTypeForMapping());
            return false;
        }

        if (!is_null($result)) {
            SRBLogger::addLog('Could not create Shipback on ShopRunBack for order ' . $orderId . '. Object: ' . json_encode($srbShipback) . '. |||| Response: ' . json_encode($result), SRBLogger::FATAL, self::getObjectTypeForMapping());
            return $result;
        } else {
            $srbShipback->id_srb_shipback = $srbShipback->id;
            $srbShipback->insertOnPS();
        }

        return $srbShipback;
    }

    public function insertOnPS()
    {
        $shipbackToInsert = array(
            'id_srb_shipback' => pSQL($this->id),
            'id_order' => pSQL($this->ps['id_order']),
            'state' => pSQL($this->state),
            'mode' => pSQL($this->mode),
            'created_at' => pSQL(Util::convertDateFormatForDB($this->created_at)),
            'public_url' => pSQL($this->public_url)
        );

        $sql = Db::getInstance();
        $sql->insert(self::SHIPBACK_TABLE_NAME_NO_PREFIX, $shipbackToInsert);

        $this->mapApiCall();

        SRBLogger::addLog(self::getObjectTypeForMapping() . ' "' . $this->id . '" inserted', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->id);
    }

    public function updateOnPS()
    {
        $shipbackToUpdate = array(
            'state' => pSQL($this->state),
            'mode' => pSQL($this->mode),
            'created_at' => pSQL($this->created_at),
            'public_url' => pSQL($this->public_url),
        );

        $sql = Db::getInstance();
        $result = $sql->update(self::SHIPBACK_TABLE_NAME_NO_PREFIX, $shipbackToUpdate, pSQL(self::getIdColumnName()) . ' = "' . pSQL($this->id) . '"');

        SRBLogger::addLog(self::getObjectTypeForMapping() . ' "' . $this->getReference() . '" updated', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        $this->mapApiCall();

        return $result;
    }

    public function getMapId()
    {
        return $this->id_srb_shipback;
    }

    private static function findAllByCreateDateQuery($limit = 0, $offset = 0, $byAsc = false)
    {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        if ($byAsc) {
            $sql->orderBy('created_at ASC');
        } else {
            $sql->orderBy('created_at DESC');
        }
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public static function getComponentsToFindAllWithMappingQuery($onlySyncElements = false)
    {
        $sql = static::findAllQuery();
        $sql->select(ElementMapper::getTableName() . '.id_item_srb');
        $sql->innerJoin(
            ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX,
            ElementMapper::getTableName(),
            ElementMapper::getTableName() . '.id_item_srb = ' . static::getTableIdentifier() . '
                AND ' . ElementMapper::getTableName() . '.type = "' . static::getObjectTypeForMapping() . '"'
        );

        return $sql;
    }

    public static function getAllByCreateDate($byAsc = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        return self::generateReturnsFromDBResult(Db::getInstance()->executeS(self::findAllByCreateDateQuery($limit, $offset, $byAsc)), $withNestedElements);
    }

    public static function getLikeOrderReferenceByCreateDate($orderReference, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        return self::generateReturnsFromDBResult(Db::getInstance()->executeS(self::findLikeOrderIdByCreateDateQuery($orderReference, $limit, $offset)), $withNestedElements);
    }

    public static function getCountLikeOrderReferenceByCreateDate($orderReference)
    {
        return self::getCountOfQuery(self::findLikeOrderIdByCreateDateQuery($orderReference));
    }

    public static function getLikeCustomerByCreateDate($customer, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        return self::generateReturnsFromDBResult(Db::getInstance()->executeS(self::findLikeCustomerByCreateDateQuery($customer, $limit, $offset)), $withNestedElements);
    }

    public static function getCountLikeCustomerByCreateDate($customer)
    {
        return self::getCountOfQuery(self::findLikeCustomerByCreateDateQuery($customer));
    }

    public static function findLikeOrderIdByCreateDateQuery($orderReference, $limit = 0, $offset = 0)
    {
        $sql = self::findAllByCreateDateQuery($limit, $offset);
        $sql->where(pSQL(SRBOrder::getTableName()) . '.reference LIKE "%' . pSQL($orderReference) . '%"');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public static function findLikeCustomerByCreateDateQuery($customer, $limit = 0, $offset = 0)
    {
        $sql = self::findAllByCreateDateQuery($limit, $offset);
        self::addLikeCustomerToQuery($sql, $customer);
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public static function getByOrderId($orderId)
    {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->where(pSQL(self::getTableName()) . '.id_order = ' . pSQL($orderId));
        $sql->orderBy('created_at', 'DESC');
        $shipbackFromDB = Db::getInstance()->getRow($sql);

        if (! $shipbackFromDB) {
            throw new ShipbackException('No shipback found for order ' . $orderId, SRBLogger::ERROR);
        }

        $shipbackFromDB['order'] = SRBOrder::createFromShipback($shipbackFromDB);

        return new self($shipbackFromDB);
    }

    public static function getByOrderIdIfExists($orderId)
    {
        try {
            return self::getByOrderId($orderId);
        } catch (ShipbackException $e) {
            return false;
        }
    }

    private static function generateReturnsFromDBResult($shipbacksFromDB, $withNestedElements = true)
    {
        $shipbacks = array();
        foreach ($shipbacksFromDB as $key => $shipback) {
            $shipback['order'] = SRBOrder::createFromShipback($shipback, $withNestedElements);
            $shipbacks[] = new self($shipback, $withNestedElements);
        }

        return $shipbacks;
    }

    public static function truncateTable()
    {
        Db::getInstance()->execute('TRUNCATE TABLE ' . pSQL(self::getShipbackTableName()));
    }
}
