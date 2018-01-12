<?php

include_once 'Synchronizer.php';
include_once 'SRBMap.php';

abstract class SRBObject
{
    public $id;
    public $identifier;
    public $ps;

    abstract static public function getTableName();

    abstract static public function getIdColumnName();

    abstract static public function getIdentifier();

    abstract static public function getDisplayNameAttribute();

    abstract static public function getObjectTypeForMapping();

    abstract static public function getPathForAPICall();

    static public function syncAll ($newOnly = false) {
        $brands = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($brands as $brand) {
            $responses[] = $brand->sync();
        }

        return $responses;
    }

    abstract public function sync();

    abstract static protected function findAllQuery();

    static public function getAll ()
    {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findAllQuery()));
    }

    static public function getAllNotSync ()
    {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findNotSyncQuery()));
    }

    static public function getAllWithMapping ($onlySyncItems = false)
    {
        $class = get_called_class();
        $items = self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findAllWithMappingQuery($onlySyncItems)));
        foreach ($items as $key => $item) {
            $items[$key]->last_sent_at = $item->ps['last_sent_at'];
        }

        return $items;
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
        SRBLogger::addLog($itemType);

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
            $SRBObjects[] =  new $class($PSItem);
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

    protected function findAllWithMappingQuery ($onlySyncItems = false)
    {
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $joinType = $onlySyncItems ? 'innerJoin' : 'leftJoin';
        $mapQuery = SRBMap::findOnlyLastSentByTypeQuery($type);

        return static::findAllQuery()
                        ->select('srb.*')
                        ->{$joinType}(
                            SRBMap::MAPPER_TABLE_NAME_NO_PREFIX,
                            'srb',
                            'srb.id_item = ' . static::getTableName() . '.' . $identifier . '
                                AND srb.type = "' . $type . '"
                                AND srb.last_sent_at IN (' . $mapQuery . ')'
                        )
                        ->groupBy(static::getTableName() . '.' . $identifier)
                        ->orderBy('srb.last_sent_at DESC');
    }

    static protected function findOneQuery ($id)
    {
        return static::findAllQuery()->where(self::getTableIdentifier() . ' = "' . pSQL($id) . '"');
    }

    static public function getById ($id)
    {
        $class = get_called_class();
        $result = Db::getInstance()->executeS(static::findOneQuery($id));

        if (! $result) {
            $exceptionName = ucfirst($class::getObjectTypeForMapping()) . 'Exception';
            throw new $exceptionName('No ' . $class::getObjectTypeForMapping() . ' found with id ' . $id, 1);
        }

        return new $class($result[0]);
    }
}
