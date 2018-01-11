<?php

include_once 'SRBObject.php';
include_once 'SRBOrder.php';

class SRBShipback extends SRBObject
{
    const SHIPBACK_TABLE_NAME_NO_PREFIX = 'shoprunback_shipbacks';
    const SHIPBACK_TABLE_NAME = _DB_PREFIX_ . self::SHIPBACK_TABLE_NAME_NO_PREFIX;

    public $id_srb_shipback;
    public $mode;
    public $order_id;
    public $order;
    public $state;
    public $created_at;

    public function __construct ($psReturn) {
        $this->ps = $psReturn;
        $this->id_srb_shipback = isset($psReturn['id_srb_shipback']) ? $psReturn['id_srb_shipback'] : '';
        $this->order = isset($psReturn['order']) ? $psReturn['order'] : SRBOrder::getById($this->ps['id_order']);
        $this->order_id = $this->order->order_number;
        $this->mode = $psReturn['mode'];
        $this->state = $psReturn['state'];
        $this->created_at = $psReturn['created_at'];
        $this->public_url = $psReturn['public_url'];
    }

    static public function getObjectTypeForMapping () {
        return 'shipbacks';
    }

    static public function getPathForAPICall () {
        return 'shipbacks';
    }

    static public function getIdentifier () {
        return 'id_srb_shipback';
    }

    static public function getDisplayNameAttribute () {
        return 'id_srb_shipback';
    }

    static public function getTableName () {
        return 'srbr';
    }

    static public function getIdColumnName () {
        return 'id_srb_shipback';
    }

    public function getShipbackDetails () {
        $sql = self::findAllQuery();
        $sql->innerJoin('order_detail', 'od', 'ord.id_order_detail = od.id_order_detail');
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . pSQL($this->id));
        $sql->groupBy('ord.id_order_detail');

        return Db::getInstance()->executeS($sql);
    }

    public function sync () {
        $this->order->sync();
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->{self::getIdentifier()} . '"', 0, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
        return Synchronizer::sync($this, self::getObjectTypeForMapping(), self::getPathForAPICall());
    }

    public function save () {
        $shipbackToUpdate = [
            'state' => $this->state,
            'mode' => $this->mode,
            'created_at' => $this->created_at,
            'public_url' => $this->public_url,
        ];

        $sql = Db::getInstance();
        $result = $sql->update(self::SHIPBACK_TABLE_NAME_NO_PREFIX, $shipbackToUpdate, 'id_srb_shipback = "' . pSQL($this->id_srb_shipback) . '"');

        SRBLogger::addLog(self::getObjectTypeForMapping() . ' "' . $this->{self::getIdentifier()} . '" updated', 0, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);

        $this->sync();

        return $result;
    }

    static public function createShipbackFromOrderId ($orderId) {
        if (! $orderId) {
            return false;
        }

        if (self::getByOrderId($orderId)) {
            return false;
        }

        $psReturn = [
            'id_srb_shipback' => 0,
            'id_order' => $orderId,
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
                    } catch (Exception $e) {
                        SRBLogger::addLog($e, 3, null, 'order', $orderId);
                    }
                }

                $result = $shipbackById;
            } catch (Exception $e) {
                SRBLogger::addLog($e, 3, null, 'order', $orderId);
            }
        } else {
            $result = self::createReturnFromSyncResult($result, $orderId);
        }

        return $result;
    }

    static private function createReturnFromSyncResult ($item, $orderId) {
        $shipbackToInsert = [
            'id_srb_shipback' => $item->id,
            'id_order' => $orderId,
            'state' => $item->state,
            'mode' => $item->mode,
            'created_at' => $item->created_at,
            'public_url' => $item->public_url
        ];

        $sql = Db::getInstance();
        $result = $sql->insert(self::SHIPBACK_TABLE_NAME_NO_PREFIX, $shipbackToInsert);
        SRBLogger::addLog(self::getObjectTypeForMapping() . ' "' . $item->id . '" inserted', 0, null, self::getObjectTypeForMapping(), $item->id);

        return self::getById($item->id);
    }

    private function findAllByCreateDateQuery () {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->orderBy('created_at DESC');

        return $sql;
    }

    static public function getAllByCreateDate () {
        $sql = self::findAllByCreateDateQuery();
        $shipbacksFromDB = Db::getInstance()->executeS($sql);

        return self::generateReturnsFromDBResult($shipbacksFromDB);
    }

    static public function getLikeOrderIdByCreateDate ($orderId) {
        $sql = self::findAllByCreateDateQuery();
        $sql->where(self::getTableName() . '.id_order LIKE "%' . pSQL($orderId) . '%"');
        $shipbacksFromDB = Db::getInstance()->executeS($sql);

        return self::generateReturnsFromDBResult($shipbacksFromDB);
    }

    static public function getLikeCustomerByCreateDate ($customer) {
        $sql = self::findAllByCreateDateQuery();
        $sql->where('
            c.firstname LIKE "%' . pSQL($customer) . '%" OR
            c.lastname LIKE "%' . pSQL($customer) . '%" OR
            CONCAT(c.firstname, " ", c.lastname) LIKE "%' . pSQL($customer) . '%"'
        );
        $shipbacksFromDB = Db::getInstance()->executeS($sql);

        return self::generateReturnsFromDBResult($shipbacksFromDB);
    }

    static public function getByOrderId ($orderId) {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->where(self::getTableName() . '.id_order = ' . pSQL($orderId));
        $sql->orderBy('created_at', 'DESC');
        $result = Db::getInstance()->executeS($sql);

        if (! $result) {
            return false;
        }

        $shipbackFromDB = $result[0];
        $shipbackFromDB['order'] = SRBOrder::createFromShipback($shipbackFromDB);

        return new self($shipbackFromDB);
    }

    static private function generateReturnsFromDBResult ($shipbacksFromDB) {
        $shipbacks = [];
        foreach ($shipbacksFromDB as $key => $shipback) {
            $shipback['order'] = SRBOrder::createFromShipback($shipback);
            $shipbacks[] = new self($shipback);
        }

        return $shipbacks;
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, o.*');
        $sql->from(self::SHIPBACK_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->innerJoin('orders', 'o', self::getTableName() . '.id_order = o.id_order');
        $sql->groupBy(self::getTableName() . '.' . self::getIdColumnName());

        return $sql;
    }
}
