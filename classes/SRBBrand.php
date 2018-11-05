<?php

use Shoprunback\Elements\Brand as LibBrand;

class SRBBrand extends LibBrand implements PSElementInterface
{
    use PSElementTrait;

    public function __construct($manufacturer)
    {
        $this->ps = $manufacturer;
        $this->name = $manufacturer['name'];
        $this->reference = $manufacturer['id_manufacturer'];

        if ($srbId = $this->getMapId()) {
            parent::__construct($srbId);
        } else {
            parent::__construct();
        }
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return 'manufacturer';
    }

    public static function getTableName()
    {
        return 'm';
    }

    public static function getIdColumnName()
    {
        return 'id_manufacturer';
    }

    public static function getIdentifier()
    {
        return 'reference';
    }

    public static function getPreIdentifier()
    {
        return 'name';
    }

    public static function getDisplayNameAttribute()
    {
        return 'name';
    }

    public static function getObjectTypeForMapping()
    {
        return 'brand';
    }

    public static function getPathForAPICall()
    {
        return 'brands';
    }

    public function generateIdentifier()
    {
        return str_replace(' ', '-', $this->{self::getPreIdentifier()});
    }

    public static function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = static::getBaseQuery();
        $sql->select(self::getTableName() . '.*');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }
}
