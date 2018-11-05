<?php
class ElementMapper
{
    const MAPPER_TABLE_NAME_NO_PREFIX = 'shoprunback_mapper';
    const MAPPER_INDEX_NAME = 'index_type_id_item';
    const MAPPER_INDEX_COLUMNS = 'type, id_item';

    public $id_srb_map;
    public $id_item;
    public $id_item_srb;
    public $type;
    public $last_sent_at;

    public function __construct($psMap)
    {
        $this->id_srb_map = isset($psMap[self::getIdColumnName()]) ? $psMap[self::getIdColumnName()] : 0;
        $this->id_item = $psMap['id_item'];
        $this->id_item_srb = $psMap['id_item_srb'];
        $this->type = $psMap['type'];
        $this->last_sent_at = Util::convertDateFormatForDB($psMap['last_sent_at']);
    }

    public static function getMapperTableName()
    {
        return _DB_PREFIX_ . self::MAPPER_TABLE_NAME_NO_PREFIX;
    }

    public static function getTableName()
    {
        return 'srbm';
    }

    public static function getIdColumnName()
    {
        return 'id_srb_map';
    }

    public static function getTableIdentifier()
    {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    public function save()
    {
        $mapArray = array();

        foreach ($this as $key => $value) {
            $mapArray[$key] = $value;
        }

        unset($mapArray['id_srb_map']);

        if (!isset($this->id_srb_map) || $this->id_srb_map == 0) {
            $mapFromDB = ElementMapper::getByIdItemAndItemType($this->id_item, $this->type);

            if ($mapFromDB) {
                $this->id_srb_map = $mapFromDB->id_srb_map;
            }
        }

        $result = '';
        $sql = Db::getInstance();
        if (isset($this->id_srb_map) && $this->id_srb_map != 0) {
            $result = $sql->update(ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX, $mapArray, 'id_item_srb = "' . pSQL($this->id_item_srb) . '" && type = "' . pSQL($this->type) . '"');
            SRBLogger::addLog('Map of ' . ucfirst($this->type) . ' ' . $this->id_item . ' updated', SRBLogger::INFO, $this->type, $this->id_item);
        } elseif (self::getByIdItemAndItemType($this->id_item, $this->type)) {
            $result = $sql->update(ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX, $mapArray, 'id_item = "' . pSQL($this->id_item) . '" && type = "' . pSQL($this->type) . '"');
            SRBLogger::addLog('Map of ' . ucfirst($this->type) . ' ' . $this->id_item . ' updated', SRBLogger::INFO, $this->type, $this->id_item);
        } else {
            $result = $sql->insert(ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX, $mapArray);
            SRBLogger::addLog(ucfirst($this->type) . ' ' . $this->id_item . ' mapped', SRBLogger::INFO, $this->type, $this->id_item);
        }

        return $result;
    }

    private static function returnResult($result)
    {
        return $result ? new self($result) : false;
    }

    public static function getMappingIdIfExists($itemId, $itemType)
    {
        $map = self::getByIdItemAndItemType($itemId, $itemType);

        if ($map) {
            return $map->id_item_srb;
        }

        return null;
    }

    public static function getById($id)
    {
        $sql = self::findAllQuery();
        $sql->where(self::getTableIdentifier() . ' = ' . pSQL($id));
        $result = Db::getInstance()->getRow($sql);

        return self::returnResult($result);
    }

    public static function getByType($type)
    {
        $mappingsFromDB = Db::getInstance()->executeS(self::findByTypeQuery($type));

        $mappings = array();
        foreach ($mappingsFromDB as $mapping) {
            $mappings[] = new self($mapping);
        }

        return $mappings;
    }

    public static function getByIdItemAndItemType($idItem, $type)
    {
        if (is_string($idItem) && !is_numeric($idItem)) {
            return static::getByIdItemSRBAndItemType($idItem, $type);
        }

        $sql = self::findAllQuery();
        $sql->where(pSQL(self::getTableName()) . '.id_item = ' . pSQL($idItem) . ' AND ' . pSQL(self::getTableName()) . '.type = "' . pSQL($type) . '"');
        $result = Db::getInstance()->getRow($sql);

        return self::returnResult($result);
    }

    public static function getByIdItemSRBAndItemType($idItemSrb, $type)
    {
        $sql = self::findAllQuery();
        $sql->where(pSQL(self::getTableName()) . '.id_item_srb = "' . pSQL($idItemSrb) . '" AND ' . pSQL(self::getTableName()) . '.type = "' . pSQL($type) . '"');
        $result = Db::getInstance()->getRow($sql);

        return self::returnResult($result);
    }

    private static function generateMappers($mappingsFromDB)
    {
        $mappings = array();
        foreach ($mappingsFromDB as $mapping) {
            $mappings[] = new self($mapping);
        }

        return $mappings;
    }

    public static function getAll()
    {
        return self::generateMappers(Db::getInstance()->executeS(self::findAllQuery()));
    }

    public static function addSelectAllToQuery(&$sql)
    {
        $sql->select(
            pSQL(self::getTableName()) . '.id_srb_map, ' .
            pSQL(self::getTableName()) . '.id_item_srb, ' .
            pSQL(self::getTableName()) . '.id_item, ' .
            pSQL(self::getTableName()) . '.type, ' .
            pSQL(self::getTableName()) . '.last_sent_at'
        );
    }

    public static function findAllQuery()
    {
        $sql = new DbQuery();
        self::addSelectAllToQuery($sql);
        $sql->from(self::MAPPER_TABLE_NAME_NO_PREFIX, self::getTableName());

        return $sql;
    }

    public static function findByTypeQuery($type)
    {
        $sql = self::findAllQuery();
        $sql->where(self::getTableName() . '.type = "' . $type . '"');

        return $sql;
    }

    public static function findOnlyIdItemByTypeQuery($type)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.id_item');
        $sql->from(self::MAPPER_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->where(self::getTableName() . '.type = "' . $type . '"');

        return $sql;
    }

    public static function findOnlyLastSentByTypeQuery($type)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.last_sent_at');
        $sql->from(self::MAPPER_TABLE_NAME_NO_PREFIX, self::getTableName());
        $sql->where(self::getTableName() . '.type = "' . $type . '"');

        return $sql;
    }

    public static function getMappingsForIdsAndType($ids, $type)
    {
        return self::generateMappers(Db::getInstance()->executeS(self::findByTypeAndListOfIdsQuery($ids, $type)));
    }

    public static function findByTypeAndListOfIdsQuery($ids, $type)
    {
        $sql = static::findByTypeQuery($type);
        $sql->where(pSQL(self::getTableName()) . '.id_item IN (' . implode(', ', array_map('intval', $ids)) . ')');

        return $sql;
    }

    public static function truncateTable()
    {
        Db::getInstance()->execute('TRUNCATE TABLE ' . pSQL(self::getMapperTableName()));
    }
}
