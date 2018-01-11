<?php

include_once 'SRBObject.php';

class SRBBrand extends SRBObject
{
    public $name;
    public $reference;

    public function __construct ($manufacturer) {
        $this->ps = $manufacturer;
        $this->name = $manufacturer['name'];
        $this->reference = $manufacturer['name'];
    }

    static public function getObjectTypeForMapping () {
        return 'brand';
    }

    static public function getPathForAPICall () {
        return 'manufacturer';
    }

    static public function getIdentifier () {
        return 'reference';
    }

    static public function getDisplayNameAttribute () {
        return 'name';
    }

    static public function getTableName () {
        return 'm';
    }

    static public function getIdColumnName () {
        return 'id_manufacturer';
    }

    public function sync () {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->{self::getIdentifier()} . '"', 0, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
        return Synchronizer::sync($this, self::getObjectTypeForMapping(), self::getPathForAPICall());
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('manufacturer', self::getTableName());

        return $sql;
    }
}
