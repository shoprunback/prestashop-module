<?php

include_once 'Synchronizer.php';
include_once 'SRBMap.php';

abstract class SRBObject
{
    public $id;
    public $identifier;
    public $ps;
    public $attributesToSend;

    abstract static public function getTableName();

    abstract static public function getIdColumnName();

    abstract static public function getIdentifier();

    abstract static public function getDisplayNameAttribute();

    abstract static public function getObjectTypeForMapping();

    abstract static public function getPathForAPICall();

    static public function syncAll ($newOnly = false) {
        $items = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($items as $item) {
            $responses[] = $item->sync();
        }

        return $responses;
    }

    abstract public function sync();

    abstract static protected function findAllQuery($limit = 0, $offset = 0);

    static public function getAll ($limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findAllQuery($limit, $offset)));
    }

    static public function getCountAll ()
    {
        $class = get_called_class();
        return self::getCountOfQuery($class::findAllQuery());
    }

    static public function getAllNotSync ()
    {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findNotSyncQuery()));
    }

    static public function getAllWithMapping ($onlySyncItems = false, $limit = 0, $offset = 0)
    {
        $class = get_called_class();
        $items = self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findAllWithMappingQuery($onlySyncItems, $limit, $offset)));

        foreach ($items as $key => $item) {
            $items[$key]->id_item_srb = $item->ps['id_item_srb'];
            $items[$key]->last_sent_at = $item->ps['last_sent_at'];
        }

        return $items;
    }

    static public function getCountAllWithMapping ($onlySyncItems = false)
    {
        $class = get_called_class();
        return self::getCountOfQuery($class::findCountAllWithMappingQuery($onlySyncItems));
    }

    public function convertDateFormatForDB ($date)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d*H:i:s.???P', $date);

        if ($dateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }

        return $date;
    }

    public function getDBId ()
    {
        return $this->ps[static::getIdColumnName()];
    }

    // Checks if we already have synchronized this object. If yes, we use the SRB ID, else we use the PS reference
    public function getItemReference ()
    {
        $itemType = static::getObjectTypeForMapping();
        $identifier = static::getIdentifier();

        // On creation, a shipback doesn't have a DBId yet (it's the only case where we create on SRB DB before on PS DB)
        if (! $this->getDBId() || ($this->{$identifier} == 0 && $itemType == 'shipback')) {
            return $this->{$identifier};
        }

        $mapId = SRBMap::getMappingIdIfExists($this->getDBId(), $itemType);

        return $mapId ? $mapId : $this->{$identifier};
    }

    public function getName ()
    {
        $name = static::getDisplayNameAttribute();
        return $this->{$name};
    }

    public function getReference ()
    {
        $reference = static::getIdentifier();
        return $this->{$reference};
    }

    protected function convertPSArrayToSRBObjects ($PSArray)
    {
        $class = get_called_class();
        $SRBObjects = [];
        foreach ($PSArray as $PSItem) {
            try {
                $SRBObjects[] =  new $class($PSItem);
            } catch (SRBException $e) {

            }
        }

        return $SRBObjects;
    }

    public function getTableIdentifier ()
    {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    protected function findNotSyncQuery ()
    {
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $mapQuery = SRBMap::findOnlyIdItemByTypeQuery($type);

        return static::findAllQuery()->where(static::getTableName() . '.' . static::getIdColumnName() . ' NOT IN (' . $mapQuery . ')');
    }

    protected function findAllWithMappingQuery ($onlySyncItems = false, $limit = 0, $offset = 0)
    {
        $identifier = static::getIdColumnName();

        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncItems);
        $sql->select('srb.*');
        $sql->groupBy(static::getTableName() . '.' . $identifier);
        $sql->orderBy('srb.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static protected function addCountToQuery ($sql)
    {
        return $sql->select('COUNT(DISTINCT ' . static::getTableName() . '.' . static::getIdColumnName() . ') as count');
    }

    static protected function addLimitToQuery ($sql, $limit = 0, $offset = 0)
    {
        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }

        return $sql;
    }

    protected function findCountAllWithMappingQuery ($onlySyncItems = false)
    {
        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncItems);
        $sql = self::addCountToQuery($sql);

        return $sql;
    }

    static public function getComponentsToFindAllWithMappingQuery ($onlySyncItems = false)
    {
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $joinType = $onlySyncItems ? 'innerJoin' : 'leftJoin';
        $mapQuery = SRBMap::findOnlyLastSentByTypeQuery($type);

        $sql = static::findAllQuery();
        $sql->{$joinType}(
            SRBMap::MAPPER_TABLE_NAME_NO_PREFIX,
            'srb',
            'srb.id_item = ' . static::getTableName() . '.' . $identifier . '
                AND srb.type = "' . $type . '"
                AND srb.last_sent_at IN (' . $mapQuery . ')'
        );

        return $sql;
    }

    static protected function findOneQuery ($id)
    {
        return static::findAllQuery()->where(self::getTableIdentifier() . ' = "' . pSQL($id) . '"');
    }

    static public function getById ($id)
    {
        $class = get_called_class();
        $result = Db::getInstance()->getRow(static::findOneQuery($id));

        if (! $result) {
            $exceptionName = ucfirst($class::getObjectTypeForMapping()) . 'Exception';
            throw new $exceptionName('No ' . $class::getObjectTypeForMapping() . ' found with id ' . $id, 1);
        }

        return new $class($result);
    }

    static public function getCountOfQuery ($sql)
    {
        $sql = self::addCountToQuery($sql);
        return Db::getInstance()->getRow($sql)['count'];
    }

    public function getAttributesNeeded ()
    {
        $attributesToSend = $this->attributesToSend;

        $object = new stdClass();
        foreach ($attributesToSend as $key => $attribute) {
            if (isset($this->$attribute)) {
                $object->$attribute = $this->$attribute;
            }
        }

        if (isset($this->items)) {
            $items = $this->items;

            $itemsNeeded = [];
            foreach ($items as $key => $item) {
                if (get_class($item->product) == 'SRBProduct') {
                    $item->product = $item->product->getAttributesNeeded();
                    $itemsNeeded[] = $item;
                }
            }

            $object->items = $itemsNeeded;
        }

        if (isset($object->customer)) {
            unset($object->customer->id);

            if (isset($object->customer->address)) {
                unset($object->customer->address->id);
            }
        }

        if (isset($this->product)) {
            if (isset($object->product_id)) {
                unset($object->product);
            } else {
                if (get_class($item->product) == 'SRBProduct') {
                    $object->product = $this->product->getAttributesNeeded();
                }
            }
        }

        if (isset($this->order)) {
            if (isset($object->order_id)) {
                unset($object->order);
            } else {
                $object->order = $this->order->getAttributesNeeded();
            }
        }

        if (isset($this->brand)) {
            if (isset($object->brand_id)) {
                unset($object->brand);
            } else {
                if (get_class($item->brand) == 'SRBBrand') {
                    $object->brand = $this->brand->getAttributesNeeded();
                }
            }
        }

        return $object;
    }

    public function toJson ()
    {
        $object = new stdClass();
        $object = $this->getAttributesNeeded();

        return json_encode($object);
    }
}
