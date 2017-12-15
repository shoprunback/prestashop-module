<?php

include_once 'SRBObject.php';
include_once 'SRBOrder.php';

class SRBReturn extends SRBObject
{
    public $id_srb_return;
    public $mode;
    public $order_id;
    public $order;
    public $state;
    public $created_at;

    public function __construct ($psReturn) {
        $this->id_srb_return = $psReturn['id_srb_return'];
        $this->order_id = $psReturn['id_order'];
        $this->order = isset($psReturn['order']) ? $psReturn['order'] : SRBOrder::getById($this->order_id);
        $this->mode = $psReturn['mode'];
        $this->state = $psReturn['state'];
        $this->created_at = $psReturn['created_at'];
    }

    static public function getSRBApiCallType () {
        return 'shipback';
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
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . $this->id);
        $sql->groupBy('ord.id_order_detail');

        return Db::getInstance()->executeS($sql);
    }

    public function sync () {
        $this->order->sync();
        return Synchronizer::sync($this, 'shipbacks');
    }

    public function save () {
        $returnToUpdate = [
            'state' => $this->state,
            'mode' => $this->mode,
            'created_at' => $this->created_at,
        ];

        $sql = Db::getInstance();
        $result = $sql->update(Synchronizer::RETURN_TABLE_NAME_NO_PREFIX, $returnToUpdate, 'id_srb_return = "' . $this->id_srb_return . '"');

        $this->sync();

        return $result;
    }

    // SQL object extractors

    static public function syncAll ($newOnly = false) {
        $returns = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($returns as $return) {
            $responses[] = $return->sync();
        }

        return $responses;
    }

    static public function createReturnFromOrderId ($orderId) {
        if (! $orderId) {
            return false;
        }

        $psReturn = [
            'id_srb_return' => '1',
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

        $sql = Db::getInstance();
        return $sql->insert(Synchronizer::RETURN_TABLE_NAME_NO_PREFIX, $returnToInsert);
    }

    // private (class) methods

    private function findAllByCreateDateQuery () {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->orderBy('created_at DESC');

        return $sql;
    }

    static public function getAllByCreateDate () {
        $sql = self::findAllByCreateDateQuery();
        $returnsFromDB = Db::getInstance()->executeS($sql);

        $returns = [];
        foreach ($returnsFromDB as $key => $return) {
            $return['order'] = SRBOrder::createFromReturn($return);
            $returns[] = new self($return);
        }

        return $returns;
    }

    static public function getLikeOrderIdByCreateDate ($orderId) {
        $sql = self::findAllByCreateDateQuery();
        $sql->where(self::getTableName() . '.id_order LIKE "%' . $orderId . '%"');
        $returnsFromDB = Db::getInstance()->executeS($sql);

        $returns = [];
        foreach ($returnsFromDB as $key => $return) {
            $return['order'] = SRBOrder::createFromReturn($return);
            $returns[] = new self($return);
        }

        return $returns;
    }

    static public function getByOrderId ($orderId) {
        $sql = self::findAllQuery();
        $sql = SRBOrder::addComponentsToQuery($sql);
        $sql->where(self::getTableName() . '.id_order = ' . $orderId);
        $sql->orderBy('created_at', 'DESC');
        $returnFromDB = Db::getInstance()->executeS($sql)[0];
        $returnFromDB['order'] = SRBOrder::createFromReturn($returnFromDB);

        return new self($returnFromDB);
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, o.*');
        $sql->from(Synchronizer::RETURN_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->innerJoin('orders', 'o', self::getTableName() . '.id_order = o.id_order');

        return $sql;
    }
}
