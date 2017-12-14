<?php

include_once 'SRBObject.php';

class SRBBrand extends SRBObject
{
    public $name;
    public $reference;

    public function __construct ($manufacturer) {
        $this->ps = $manufacturer;
        $this->name = $manufacturer['name'];
        $this->reference = $this->extractReference($manufacturer);
    }

    static public function getSRBApiCallType () {
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
        return Synchronizer::sync($this, self::getSRBApiCallType());
    }

    // SQL object extractors

    static private function extractReference ($psManufacturerArrayName) {
        $identifier = 'id';

        if (isset($psManufacturerArrayName['id_manufacturer'])) {
            $identifier = 'id_manufacturer';
        } elseif (isset($psManufacturerArrayName['reference'])) {
            $identifier = 'reference';
        }

        return $psManufacturerArrayName[$identifier];
    }

    // private (class) methods

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('manufacturer', self::getTableName());

        return $sql;
    }
}
