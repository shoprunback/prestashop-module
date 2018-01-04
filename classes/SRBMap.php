<?php
class SRBMap
{
    const MAPPER_TABLE_NAME_NO_PREFIX = 'shoprunback_mapper';
    const MAPPER_TABLE_NAME = _DB_PREFIX_ . self::MAPPER_TABLE_NAME_NO_PREFIX;
    const MAPPER_INDEX_NAME = 'index_type_id_item';
    const MAPPER_INDEX_COLUMNS = 'type, id_item';

    public $id_srb_map;
    public $id_item;
    public $id_item_srb;
    public $type;
    public $last_sent;

    public function __construct ($psMap) {
        $this->id_srb_map = isset($psMap[self::getIdColumnName()]) ? $psMap[self::getIdColumnName()] : 0;
        $this->id_item = $psMap['id_item'];
        $this->id_item_srb = $psMap['id_item_srb'];
        $this->type = $psMap['type'];
        $this->last_sent = $psMap['last_sent'];
    }

    static public function getTableName () {
        return 'srbm';
    }

    static public function getIdColumnName () {
        return 'id_srb_map';
    }

    public function save () {
        $mapArray = [
            'id_item' => $this->id_item,
            'id_item_srb' => $this->id_item_srb,
            'type' => $this->type,
            'last_sent' => $this->last_sent,
        ];

        if (! isset($this->id_srb_map) || $this->id_srb_map == 0) {
            $mapFromDB = SRBMap::getByIdItemAndIdType($this->id_item, $this->type);

            if ($mapFromDB) {
                $this->id_srb_map = $mapFromDB->id_srb_map;
            }
        }

        $result = '';
        $sql = Db::getInstance();
        if (isset($this->id_srb_map) && $this->id_srb_map != 0) {
            $result = $sql->update(SRBMap::MAPPER_TABLE_NAME_NO_PREFIX, $mapArray, 'id_item_srb = "' . pSQL($this->id_item_srb) . '"');
            Logger::addLog('[ShopRunBack] Map of ' . ucfirst($this->type) . ' ' . $this->id_item . ' updated', 0, null, $this->type, $this->id_item, true);
        } else {
            $result = $sql->insert(SRBMap::MAPPER_TABLE_NAME_NO_PREFIX, $mapArray);
            Logger::addLog('[ShopRunBack] ' . ucfirst($this->type) . ' ' . $this->id_item . ' mapped', 0, null, $this->type, $this->id_item, true);
        }

        return $result;
    }

    static public function getById ($id) {
        $sql = self::findAllQuery();
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . pSQL($id));
        $result = Db::getInstance()->executeS($sql);

        return (is_array($result) && isset($result[0])) ? new self($result[0]) : false;
    }

    static public function getByType ($type) {
        $returnsFromDB = Db::getInstance()->executeS(self::findByTypeQuery($type));

        $returns = [];
        foreach ($returnsFromDB as $return) {
            $returns[] = new self($return);
        }

        return $returns;
    }

    static public function getByIdItemAndIdType ($idItem, $type) {
        $sql = self::findAllQuery();
        $sql->where(self::getTableName() . '.id_item = ' . pSQL($idItem) . ' AND ' . pSQL(self::getTableName()) . '.type = "' . $type . '"');
        $result = Db::getInstance()->executeS($sql);

        return (is_array($result) && isset($result[0])) ? new self($result[0]) : false;
    }

    static public function getAll () {
        $returnsFromDB = Db::getInstance()->executeS(self::findAllQuery());

        $returns = [];
        foreach ($returnsFromDB as $return) {
            $returns[] = new self($return);
        }

        return $returns;
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from(self::MAPPER_TABLE_NAME_NO_PREFIX, self::getTableName());

        return $sql;
    }

    static public function findByTypeQuery ($type) {
        $sql = self::findAllQuery();
        $sql->where(self::getTableName() . '.type = "' . $type . '"');

        return $sql;
    }

    static public function findOnlyIdItemByTypeQuery ($type) {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.id_item');
        $sql->from(self::MAPPER_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->where(self::getTableName() . '.type = "' . $type . '"');

        return $sql;
    }

    static public function findOnlyLastSentByTypeQuery ($type) {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.last_sent');
        $sql->from(self::MAPPER_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->where(self::getTableName() . '.type = "' . $type . '"');

        return $sql;
    }
}
