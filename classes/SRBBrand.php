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

    static public function getIdColumnName()
    {
        return 'id_manufacturer';
    }

    static public function getIdentifier()
    {
        return 'reference';
    }

    static public function getPreIdentifier()
    {
        return 'name';
    }

    static public function getDisplayNameAttribute()
    {
        return 'name';
    }

    static public function getObjectTypeForMapping()
    {
        return 'brand';
    }

    static public function getPathForAPICall()
    {
        return 'brands';
    }

    public function generateIdentifier()
    {
        return str_replace(' ', '-', $this->{self::getPreIdentifier()});
    }

    static public function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('manufacturer', self::getTableName());
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }
}