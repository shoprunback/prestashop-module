<?php

include_once 'Synchronizer.php';

abstract class SRBObject {
    public $id;


    // static public function getAll () {
    //     $objectsFromDB = Db::getInstance()->executeS($this->findAllQuery());

    //     $objects = [];
    //     foreach ($objectsFromDB as $objectFromDB) {
    //         $objects[] = $this->__construct($objectFromDB);
    //     }

    //     return $objects;
    // }

    // static public function getTableName() {
    //     throw new Exception(__CLASS__. ':' . __METHOD__ . ': not implemented');
    // }

    // static public function getIdColumnName() {
    //     throw new Exception(__CLASS__. ':' . __METHOD__ . ': not implemented');
    // }

    // static protected function findAllQuery() {
    //     throw new Exception(__CLASS__. ':' . __METHOD__ . ': not implemented');
    // }

    abstract static public function getTableName();

    abstract static public function getIdColumnName();

    abstract static protected function findAllQuery();

    public function getTableIdentifier () {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    // private (class) method

    static public function findOneQuery ($id) {
        return static::findAllQuery()->where(self::getTableIdentifier() . ' = ' . $id);
    }
}
