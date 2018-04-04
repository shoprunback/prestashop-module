<?php

include_once 'SRBObject.php';

use Shoprunback\Elements\Brand;
use Shoprunback\Error\NotFoundError;
use Shoprunback\Error\RestClientError;

class SRBBrand extends SRBObject
{
    public $name;
    public $reference;

    public function __construct ($manufacturer)
    {
        $this->ps = $manufacturer;
        $this->name = $manufacturer['name'];
        $this->reference = str_replace(' ', '-', $manufacturer['name']);

        $this->attributesToSend = ['name', 'reference'];
    }

    static public function getObjectTypeForMapping ()
    {
        return 'brand';
    }

    static public function getPathForAPICall ()
    {
        return 'brands';
    }

    static public function getIdentifier ()
    {
        return 'reference';
    }

    static public function getDisplayNameAttribute ()
    {
        return 'name';
    }

    static public function getTableName ()
    {
        return 'm';
    }

    static public function getIdColumnName ()
    {
        return 'id_manufacturer';
    }

    public function createLibElementFromSRBObject()
    {
        $brand = false;
        if ($mapId = SRBMap::getMappingIdIfExists($this->getDBId(), self::getObjectTypeForMapping())) {
            try {
                $brand = Brand::retrieve($mapId);
                return $brand;
            } catch (NotFoundError $e) {

            }
        }

        try {
            $brand = Brand::retrieve($this->getIdentifier());
            return $brand;
        } catch (NotFoundError $e) {

        }

        $brand = new Brand();
        $brand->name = $this->name;
        $brand->reference = $this->reference;

        if (isset($this->id)) {
            $brand->id = $this->id;
        }

        return $brand;
    }

    public function sync ()
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        $brand = $this->createLibElementFromSRBObject();
        try {
            $brand->save();
            $this->mapApiCall($brand->id);
        } catch (RestClientError $e) {
            SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        }
    }

    static public function findAllQuery ($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('manufacturer', self::getTableName());
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }
}
