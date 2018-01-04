<?php

include_once 'SRBObject.php';
include_once 'SRBOrder.php';

class SRBReturn extends SRBObject
{
    const RETURN_TABLE_NAME_NO_PREFIX = 'shoprunback_returns';
    const RETURN_TABLE_NAME = _DB_PREFIX_ . self::RETURN_TABLE_NAME_NO_PREFIX;
    const RETURN_INDEX_NAME = 'index_srb_return_id';
    const RETURN_INDEX_COLUMNS = 'srb_return_id';

    public $id_srb_return;
    public $mode;
    public $order_id;
    public $order;
    public $state;
    public $created_at;

    public function __construct ($psReturn) {
        $this->ps = $psReturn;
        $this->id_srb_return = isset($psReturn['id_srb_return']) ? $psReturn['id_srb_return'] : '';
        $this->order_id = $psReturn['id_order'];
        $this->order = isset($psReturn['order']) ? $psReturn['order'] : SRBOrder::getById($this->order_id);
        $this->mode = $psReturn['mode'];
        $this->state = $psReturn['state'];
        $this->created_at = $psReturn['created_at'];
    }

    static public function getMapType () {
        return 'shipbacks';
    }

    static public function getIdentifier () {
        return 'id_srb_return';
    }

    static public function getDisplayNameAttribute () {
        return 'id_srb_return';
    }

    static public function getTableName () {
        return 'srbr';
    }

    static public function getIdColumnName () {
        return 'id_srb_return';
    }

    public function getPublicUrl () {
        return Synchronizer::SRB_WEB_URL . '/shipbacks/' . $this->id_srb_return;
    }

    public function getReturnDetails () {
        $sql = self::findAllQuery();
        $sql->innerJoin('order_detail', 'od', 'ord.id_order_detail = od.id_order_detail');
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . pSQL($this->id));
        $sql->groupBy('ord.id_order_detail');

        return Db::getInstance()->executeS($sql);
    }

    public function sync () {
        $this->order->sync();
        Logger::addLog('[ShopRunBack] SYNCHRONIZING ' . self::getMapType() . ' "' . $this->{self::getIdentifier()} . '"', 0, null, self::getMapType(), $this->ps[self::getIdColumnName()], true);
        return Synchronizer::sync($this, self::getMapType());
    }

    static public function syncAll ($newOnly = false) {
        $returns = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($returns as $return) {
            $responses[] = $return->sync();
        }

        return $responses;
    }

    public function save () {
        $returnToUpdate = [
            'state' => $this->state,
            'mode' => $this->mode,
            'created_at' => $this->created_at,
        ];

        $sql = Db::getInstance();
        $result = $sql->update(SRBReturn::RETURN_TABLE_NAME_NO_PREFIX, $returnToUpdate, 'id_srb_return = "' . pSQL($this->id_srb_return) . '"');

        Logger::addLog('[ShopRunBack] ' . self::getMapType() . ' "' . $this->{self::getIdentifier()} . '" updated', 0, null, self::getMapType(), $this->ps[self::getIdColumnName()], true);

        $this->sync();

        return $result;
    }

    static public function createReturnFromOrderId ($orderId) {
        if (! $orderId) {
            return false;
        }

        if (self::getByOrderId($orderId)) {
            return false;
        }

        $psReturn = [
            'id_srb_return' => 0,
            'id_order' => $orderId,
            'state' => '0',
            'mode' => 'postal',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $srbReturn = new self($psReturn);
        $result = json_decode($srbReturn->sync());

        if (isset($result->shipback) && isset($result->shipback->errors)) {
            $id = explode('(', $result->shipback->errors[0])[1];
            $id = str_replace(')', '', $id);
            $returnById = self::getById($id);

            if (! $returnById && strpos($result->shipback->errors[0], 'Order already\'s got return associated') !== false) {
                $returnGet = json_decode(Synchronizer::APICall('shipbacks/' . $id, 'GET'));
                self::insertReturnFromSyncResult($returnGet, $orderId);
                $returnById = self::getById($id);
            }

            $result = $returnById;
        } else {
            self::insertReturnFromSyncResult($result, $orderId);
        }

        return $result;
    }

    static private function insertReturnFromSyncResult ($result, $orderId) {
        $returnToInsert = [
            'id_srb_return' => $result->id,
            'id_order' => $orderId,
            'state' => $result->state,
            'mode' => $result->mode,
            'created_at' => $result->created_at
        ];

        Logger::addLog('[ShopRunBack] ' . self::getMapType() . ' "' . $this->{self::getIdentifier()} . '" inserted', 0, null, self::getMapType(), $this->ps[self::getIdColumnName()], true);
        $sql = Db::getInstance();
        return $sql->insert(SRBReturn::RETURN_TABLE_NAME_NO_PREFIX, $returnToInsert);
    }

    private function findAllByCreateDateQuery () {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->orderBy('created_at DESC');

        return $sql;
    }

    static public function getAllByCreateDate () {
        $sql = self::findAllByCreateDateQuery();
        $returnsFromDB = Db::getInstance()->executeS($sql);

        return self::generateReturnsFromDBResult($returnsFromDB);
    }

    static public function getLikeOrderIdByCreateDate ($orderId) {
        $sql = self::findAllByCreateDateQuery();
        $sql->where(self::getTableName() . '.id_order LIKE "%' . pSQL($orderId) . '%"');
        $returnsFromDB = Db::getInstance()->executeS($sql);

        return self::generateReturnsFromDBResult($returnsFromDB);
    }

    static public function getLikeCustomerByCreateDate ($customer) {
        $sql = self::findAllByCreateDateQuery();
        $sql->where('
            c.firstname LIKE "%' . pSQL($customer) . '%" OR
            c.lastname LIKE "%' . pSQL($customer) . '%" OR
            CONCAT(c.firstname, " ", c.lastname) LIKE "%' . pSQL($customer) . '%"'
        );
        $returnsFromDB = Db::getInstance()->executeS($sql);

        return self::generateReturnsFromDBResult($returnsFromDB);
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

        $returnFromDB = $result[0];
        $returnFromDB['order'] = SRBOrder::createFromReturn($returnFromDB);

        return new self($returnFromDB);
    }

    static private function generateReturnsFromDBResult ($returnsFromDB) {
        $returns = [];
        foreach ($returnsFromDB as $key => $return) {
            $return['order'] = SRBOrder::createFromReturn($return);
            $returns[] = new self($return);
        }

        return $returns;
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, o.*');
        $sql->from(SRBReturn::RETURN_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->innerJoin('orders', 'o', self::getTableName() . '.id_order = o.id_order');
        $sql->groupBy(self::getTableName() . '.' . self::getIdColumnName());

        return $sql;
    }
}
