<?php

use Shoprunback\Elements\Brand as LibBrand;
use Shoprunback\Error\NotFoundError;
use Shoprunback\Error\RestClientError;

class NewBrand extends LibBrand implements PSElementInterface
{
    use PSElementTrait;

    public function __construct($manufacturer)
    {
        $this->ps = $manufacturer;
        $this->name = $manufacturer['name'];
        $this->reference = str_replace(' ', '-', $manufacturer['name']);

        if ($srbId = $this->getMapId()) {
            parent::__construct($srbId);
            $this->copyValues($this);
        } else {
            parent::__construct();
        }
    }

    public static function getTableName()
    {
        return 'm';
    }

    static public function getIdColumnName ()
    {
        return 'id_manufacturer';
    }

    static public function getIdentifier ()
    {
        return 'reference';
    }

    static public function getDisplayNameAttribute ()
    {
        return 'name';
    }

    static public function getObjectTypeForMapping ()
    {
        return 'brand';
    }

    static public function getPathForAPICall ()
    {
        return 'brands';
    }

    static public function findAllQuery ($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('manufacturer', self::getTableName());
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public function sync ()
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        try {
            $result = $this->save();
            $this->mapApiCall($this->id);
            return $result;
        } catch (RestClientError $e) {
            SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        }
    }
}