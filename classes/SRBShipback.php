<?php

include_once 'SRBObject.php';
include_once 'SRBOrder.php';

use Shoprunback\Elements\Shipback;
use Shoprunback\Error\NotFoundError;
use Shoprunback\Error\RestClientError;

class SRBShipback extends SRBObject
{
    const SHIPBACK_TABLE_NAME_NO_PREFIX = 'shoprunback_shipbacks';

    public $id_srb_shipback;
    public $mode;
    public $order_id;
    public $order;
    public $state;
    public $created_at;

    public function __construct ($psReturn)
    {
        $this->ps = $psReturn;
        $this->id_srb_shipback = isset($psReturn['id_srb_shipback']) ? $psReturn['id_srb_shipback'] : '';
        $this->order = isset($psReturn['order']) ? $psReturn['order'] : SRBOrder::getById($this->ps['id_order']);
        $this->order_id = $this->order->order_number;
        $this->mode = $psReturn['mode'];
        $this->state = $psReturn['state'];
        $this->created_at = $this->convertDateFormatForDB($psReturn['created_at']);
        $this->public_url = $psReturn['public_url'];

        $this->attributesToSend = ['order', 'order_id'];
    }

    static public function getShipbackTableName()
    {
        return _DB_PREFIX_ . self::SHIPBACK_TABLE_NAME_NO_PREFIX;
    }

    static public function getObjectTypeForMapping ()
    {
        return 'shipback';
    }

    static public function getPathForAPICall ()
    {
        return 'shipbacks';
    }

    static public function getIdentifier ()
    {
        return self::getIdColumnName();
    }

    static public function getDisplayNameAttribute ()
    {
        return self::getIdColumnName();
    }

    static public function getTableName ()
    {
        return 'srbr';
    }

    static public function getIdColumnName ()
    {
        return 'id_srb_shipback';
    }

    public function createLibElementFromSRBObject()
    {
        return Shipback::retrieve($this->id);
    }

    public function getShipbackDetails ()
    {
        $sql = self::findAllQuery();
        $sql->innerJoin('order_detail', 'od', 'ord.id_order_detail = od.id_order_detail');
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . pSQL($this->id));
        $sql->groupBy('ord.id_order_detail');

        return Db::getInstance()->executeS($sql);
    }

    public function sync ()
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        $this->order->sync();

        if (isset($this->order_id) && $this->order_id != '') {
            $order = $this->order;
            unset($this->order);
        }

        $result = Synchronizer::sync($this);

        if (isset($this->order_id) && $this->order_id != '') {
            $this->order = $order;
        }

        return $result;
    }

    public function save ()
    {
        $shipbackToUpdate = [
            'state' => $this->state,
            'mode' => $this->mode,
            'created_at' => $this->created_at,
            'public_url' => $this->public_url,
        ];

        $sql = Db::getInstance();
        $result = $sql->update(self::SHIPBACK_TABLE_NAME_NO_PREFIX, $shipbackToUpdate, SRBShipback::getIdColumnName() . ' = "' . pSQL($this->id_srb_shipback) . '"');

        SRBLogger::addLog(self::getObjectTypeForMapping() . ' "' . $this->getReference() . '" updated', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        return $result;
    }

    static public function createShipbackFromOrderId ($orderId)
    {
        if (self::getByOrderIdIfExists($orderId)) {
            return false;
        }

        $order = SRBOrder::getById($orderId);
        if (! $order->isShipped()) {
            return false;
        }

        $psReturn = [
            'id_srb_shipback' => 0,
            'id_order' => $orderId,
            'order' => $order,
            'state' => '0',
            'mode' => 'postal',
            'created_at' => date('Y-m-d H:i:s'),
            'public_url' => ''
        ];
        $srbShipback = new self($psReturn);
        $result = json_decode($srbShipback->sync());

        if (isset($result->shipback) && isset($result->shipback->errors)) {
            $id = explode('(', $result->shipback->errors[0])[1];
            $id = str_replace(')', '', $id);

            try {
                $shipbackById = self::getById($id);

                if (isset($shipbackById->shipback) && isset($shipbackById->shipback->errors)) {
                    return $shipbackById;
                }

                if (! $shipbackById && strpos($result->shipback->errors[0], 'Order already\'s got return associated') !== false) {
                    $shipbackGet = json_decode(Synchronizer::APICall('shipbacks/' . $id, 'GET'));
                    self::createReturnFromSyncResult($shipbackGet, $orderId);
                    try {
                        $shipbackById = self::getById($id);
                    } catch (ShipbackException $e) {
                        SRBLogger::addLog($e, 'order', $orderId);
                    }
                }

                $result = $shipbackById;
            } catch (ShipbackException $e) {
                SRBLogger::addLog($e, SRBLogger::ERROR, 'order', $orderId);
            }
        } else {
            $result = self::createReturnFromSyncResult($result, $orderId);
        }

        return $result;
    }

    static private function createReturnFromSyncResult ($item, $orderId)
    {
        $shipbackToInsert = [
            'id_srb_shipback' => $item->id,
            'id_order' => $orderId,
            'state' => $item->state,
            'mode' => $item->mode,
            'created_at' => self::convertDateFormatForDB($item->created_at),
            'public_url' => $item->public_url
        ];

        $sql = Db::getInstance();
        $result = $sql->insert(self::SHIPBACK_TABLE_NAME_NO_PREFIX, $shipbackToInsert);
        SRBLogger::addLog(self::getObjectTypeForMapping() . ' "' . $item->id . '" inserted', SRBLogger::INFO, self::getObjectTypeForMapping(), $item->id);

        return self::getById($item->id);
    }

    static private function findAllByCreateDateQuery ($limit = 0, $offset = 0)
    {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->orderBy('created_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function getAllByCreateDate ($limit = 0, $offset = 0)
    {
        return self::generateReturnsFromDBResult(Db::getInstance()->executeS(self::findAllByCreateDateQuery($limit, $offset)));
    }

    static public function getLikeOrderReferenceByCreateDate ($orderReference, $limit = 0, $offset = 0)
    {
        return self::generateReturnsFromDBResult(Db::getInstance()->executeS(self::findLikeOrderIdByCreateDateQuery($orderReference, $limit, $offset)));
    }

    static public function getCountLikeOrderReferenceByCreateDate ($orderReference)
    {
        return self::getCountOfQuery(self::findLikeOrderIdByCreateDateQuery($orderReference));
    }

    static public function getLikeCustomerByCreateDate ($customer, $limit = 0, $offset = 0)
    {
        return self::generateReturnsFromDBResult(Db::getInstance()->executeS(self::findLikeCustomerByCreateDateQuery($customer, $limit, $offset)));
    }

    static public function getCountLikeCustomerByCreateDate ($customer)
    {
        return self::getCountOfQuery(self::findLikeCustomerByCreateDateQuery($customer));
    }

    static public function findLikeOrderIdByCreateDateQuery ($orderReference, $limit = 0, $offset = 0)
    {
        $sql = self::findAllByCreateDateQuery($limit, $offset);
        $sql->where(SRBOrder::getTableName() . '.reference LIKE "%' . pSQL($orderReference) . '%"');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function findLikeCustomerByCreateDateQuery ($customer, $limit = 0, $offset = 0)
    {
        $sql = self::findAllByCreateDateQuery($limit, $offset);
        $sql->where('
            c.firstname LIKE "%' . pSQL($customer) . '%" OR
            c.lastname LIKE "%' . pSQL($customer) . '%" OR
            CONCAT(c.firstname, " ", c.lastname) LIKE "%' . pSQL($customer) . '%"'
        );
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function getByOrderId ($orderId)
    {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->where(self::getTableName() . '.id_order = ' . pSQL($orderId));
        $sql->orderBy('created_at', 'DESC');
        $shipbackFromDB = Db::getInstance()->getRow($sql);

        if (! $shipbackFromDB) {
            throw new ShipbackException('No shipback found for order ' . $orderId, SRBLogger::ERROR);
        }

        $shipbackFromDB['order'] = SRBOrder::createFromShipback($shipbackFromDB);

        return new self($shipbackFromDB);
    }

    static public function getByOrderIdIfExists ($orderId)
    {
        try {
            return self::getByOrderId($orderId);
        } catch (ShipbackException $e) {
            return false;
        }
    }

    static private function generateReturnsFromDBResult ($shipbacksFromDB)
    {
        $shipbacks = [];
        foreach ($shipbacksFromDB as $key => $shipback) {
            $shipback['order'] = SRBOrder::createFromShipback($shipback);
            $shipbacks[] = new self($shipback);
        }

        return $shipbacks;
    }

    static public function findAllQuery ($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, ' . SRBOrder::getTableName() . '.*');
        $sql->from(self::SHIPBACK_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->innerJoin('orders', SRBOrder::getTableName(), self::getTableName() . '.id_order = ' . SRBOrder::getTableName() . '.' . SRBOrder::getIdColumnName());
        $sql->groupBy(self::getTableName() . '.' . self::getIdColumnName());
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function truncateTable ()
    {
        $sql = 'TRUNCATE TABLE ' . self::getShipbackTableName();
        Db::getInstance()->execute($sql);
    }
}
