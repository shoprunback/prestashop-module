<?php

include_once 'SRBObject.php';

class SRBBrand extends SRBObject
{
    public $name;
    public $reference;

    public function __construct ($manufacturer) {
        $this->identifier = 'reference';
        $this->name = $manufacturer['name'];
        $this->reference = $this->extractReference($manufacturer);
    }

    static public function getSRBApiCallType () {
        return 'manufacturer';
    }

    static public function getIdentifier () {
        return 'reference';
    }

    static public function getTableName () {
        return 'm';
    }

    static public function getIdColumnName () {
        return 'id_manufacturer';
    }

    static public function syncAll () {
        $products = self::getAll();

        $responses = [];
        foreach ($products as $product) {
            $responses[] = Synchronizer::sync($product, 'product');
        }

        return $responses;
    }

    public function sync () {
        return Synchronizer::sync($this, 'brand');
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
        $sql->select('m.*');
        $sql->from('manufacturer', 'm');

        return $sql;
    }
}
