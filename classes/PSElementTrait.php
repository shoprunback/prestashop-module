<?php

use \Shoprunback\ElementManager;

trait PSElementTrait
{
    static protected function convertPSArrayToElements ($PSArray)
    {
        $class = get_called_class();
        $elements = [];
        foreach ($PSArray as $PSItem) {
            try {
                $elements[] =  new $class($PSItem);
            } catch (SRBException $e) {

            }
        }

        return $elements;
    }

    static public function getTableIdentifier ()
    {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    public function getDBId ()
    {
        return $this->ps[static::getIdColumnName()];
    }

    static public function getComponentsToFindAllWithMappingQuery ($onlySyncItems = false)
    {
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $joinType = $onlySyncItems ? 'innerJoin' : 'leftJoin';
        $mapQuery = ElementMapper::findOnlyLastSentByTypeQuery($type);

        $sql = static::findAllQuery();
        $sql->{$joinType}(
            ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX,
            'srb',
            'srb.id_item = ' . static::getTableName() . '.' . $identifier . '
                AND srb.type = "' . $type . '"
                AND srb.last_sent_at IN (' . $mapQuery . ')'
        );

        return $sql;
    }

    protected function findNotSyncQuery ()
    {
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $mapQuery = ElementMapper::findOnlyIdItemByTypeQuery($type);

        return static::findAllQuery()->where(static::getTableName() . '.' . static::getIdColumnName() . ' NOT IN (' . $mapQuery . ')');
    }

    static protected function findAllWithMappingQuery ($onlySyncItems = false, $limit = 0, $offset = 0)
    {
        $identifier = static::getIdColumnName();

        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncItems);
        $sql->select('srb.*');
        $sql->groupBy(static::getTableName() . '.' . $identifier);
        $sql->orderBy('srb.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function getAllWithMapping ($onlySyncItems = false, $limit = 0, $offset = 0)
    {
        $class = get_called_class();
        $items = self::convertPSArrayToElements(Db::getInstance()->executeS($class::findAllWithMappingQuery($onlySyncItems, $limit, $offset)));

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

    static protected function findCountAllWithMappingQuery ($onlySyncItems = false)
    {
        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncItems);
        $sql = self::addCountToQuery($sql);

        return $sql;
    }

    static protected function addCountToQuery ($sql)
    {
        return $sql->select('COUNT(DISTINCT ' . static::getTableName() . '.' . static::getIdColumnName() . ') as count');
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

    static protected function addLimitToQuery ($sql, $limit = 0, $offset = 0)
    {
        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }

        return $sql;
    }

    public function isMapped()
    {
        if (!$this->getMapId()) return false;

        return true;
    }

    public function getMapId()
    {
        return ElementMapper::getMappingIdIfExists($this->getDBId(), static::getObjectTypeForMapping());
    }

    public function getName ()
    {
        $name = static::getDisplayNameAttribute();
        return $this->{$name};
    }

    static public function getAll ($limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findAllQuery($limit, $offset)));
    }

    static public function getCountAll ()
    {
        $class = get_called_class();
        return self::getCountOfQuery($class::findAllQuery());
    }

    static public function getAllNotSync ()
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findNotSyncQuery()));
    }

    public function mapApiCall ($itemSrbId)
    {
        $itemType = static::getObjectTypeForMapping();
        $identifier = static::getIdColumnName();
        $itemId = isset($this->$identifier) ? $this->$identifier : $this->getDBId();

        SRBLogger::addLog('Saving map for ' . $itemType . ' with ID ' . $itemId, SRBLogger::INFO, $itemType);
        $data = [
            'id_item' => $itemId,
            'id_item_srb' => $itemSrbId,
            'type' => $itemType,
            'last_sent_at' => date('Y-m-d H:i:s'),
        ];
        $map = new ElementMapper($data);
        $map->save();
    }

    static public function syncAll ($newOnly = false) {
        $items = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($items as $item) {
            $responses[] = $item->sync();
        }

        return $responses;
    }
}