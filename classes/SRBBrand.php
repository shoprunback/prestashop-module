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

    static public function getMapType () {
        return 'brand';
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

    static public function syncAll ($newOnly = false) {
        $brands = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($brands as $brand) {
            $responses[] = $brand->sync();
        }

        return $responses;
    }

    public function sync () {
        return Synchronizer::sync($this, self::getMapType());
    }

    // private (class) methods

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('manufacturer', self::getTableName());

        return $sql;
    }
}
