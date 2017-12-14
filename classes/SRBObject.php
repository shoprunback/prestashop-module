<?php

include_once 'Synchronizer.php';

abstract class SRBObject
{
    public $id;
    public $identifier;
    public $ps;

    abstract static public function getTableName();

    abstract static public function getIdColumnName();

    abstract static public function getIdentifier();

    abstract static public function getDisplayNameAttribute();

    abstract static public function getSRBApiCallType();

    abstract static public function syncAll();

    abstract public function sync();

    abstract static protected function findAllQuery();

    static public function getAll () {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findAllQuery()));
    }

    static public function getAllNotSync () {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findNotSyncQuery()));
    }

    static public function getAllWithSRBApiCallQuery ($onlySyncItems = false) {
        $class = get_called_class();
        $items = self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findWithSRBApiCallQuery($onlySyncItems)));
        foreach ($items as $key => $item) {
            $items[$key]->last_sent = $item->ps['last_sent'];
        }

        return $items;
    }

    public function getName() {
        $name = static::getDisplayNameAttribute();
        return $this->{$name};
    }

    public function getReference() {
        $reference = static::getIdentifier();
        return $this->{$reference};
    }
    protected function convertPSArrayToSRBObjects($PSArray) {
        $class = get_called_class();
        $SRBObjects = [];
        foreach ($PSArray as $PSItem) {
            $SRBObjects[] =  new $class($PSItem);
        }

        return $SRBObjects;
    }

    public function getTableIdentifier () {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    protected function findNotSyncQuery () {
        $identifier = static::getIdColumnName();
        $type = static::getSRBApiCallType();
        return static::findAllQuery()
                        ->where(static::getTableName() . '.' . static::getIdColumnName() . ' NOT IN (
                                                                                                SELECT srb.id_item
                                                                                                FROM ' . Synchronizer::API_CALLS_TABLE_NAME . ' srb
                                                                                                WHERE srb.type = "' . $type . '"
                                                                                                GROUP BY srb.id_item
                                                                                            )'
                        );
    }

    protected function findWithSRBApiCallQuery ($onlySyncItems = false) {
        $identifier = static::getIdColumnName();
        $type = static::getSRBApiCallType();
        $joinType = $onlySyncItems ? 'innerJoin' : 'leftJoin';
        return static::findAllQuery()
                        ->select('srb.*')
                        ->{$joinType}(
                            Synchronizer::API_CALLS_TABLE_NAME_NO_PREFIX,
                            'srb',
                            'srb.id_item = ' . static::getTableName() . '.' . $identifier . '
                                AND srb.type = "' . $type . '"
                                AND srb.last_sent IN (
                                    SELECT MAX(srb.last_sent)
                                    FROM ' . Synchronizer::API_CALLS_TABLE_NAME . ' srb
                                    WHERE srb.type = "' . $type . '"
                                    GROUP BY srb.id_item
                            )'
                        )
                        ->groupBy(static::getTableName() . '.' . $identifier)
                        ->orderBy('srb.last_sent DESC');
    }

    // private (class) method

    static protected function findOneQuery ($id) {
        return static::findAllQuery()->where(self::getTableIdentifier() . ' = "' . $id . '"');
    }

    static public function getById ($id) {
        $class = get_called_class();
        $result = Db::getInstance()->executeS(static::findOneQuery($id));

        if (! $result) {
            return false;
        }

        return new $class($result[0]);
    }
}
