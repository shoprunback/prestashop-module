<?php

include_once 'Synchronizer.php';

abstract class SRBObject
{
    public $id;
    public $identifier;

    abstract static public function getTableName();

    abstract static public function getIdColumnName();

    abstract static protected function findAllQuery();

    static public function getAll() {
        $class = get_called_class();
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS($class::findAllQuery()));
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

    public function findWithSRBApiCallQuery () {
        $identifier = self::getIdentifier();
        $type = self::getSRBApiCallType();
        var_dump('srb.id_item = p.' . $identifier . '
                    AND srb.type = "' . $type . '"
                    AND srb.last_sent IN (
                        SELECT MAX(srb.last_sent)
                        FROM ps_srb_api_calls srb
                        WHERE srb.type = "' . $type . '"
                        GROUP BY srb.id_item
                    )');die;
        $class = get_called_class();
        static::findAllQuery()->innerJoin('srb_api_call', 'srb', 'srb.id_item = p.' . $this->identifier . '
                                                                    AND srb.type = "' . $type . '"
                                                                    AND srb.last_sent IN (
                                                                        SELECT MAX(srb.last_sent)
                                                                        FROM ps_srb_api_calls srb
                                                                        WHERE srb.type = "product"
                                                                        GROUP BY srb.id_item
                                                                    )');
        return;
    }

    // private (class) method

    static public function findOneQuery ($id) {
        return static::findAllQuery()->where(self::getTableIdentifier() . ' = ' . $id);
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
