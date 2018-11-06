<?php
/**
 * 2007-2018 ShopRunBack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ShopRunBack
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the ShopRunBack module to newer
 * versions in the future.
 *
 * @author ShopRunBack <contact@shoprunback.com>
 * @copyright 2007-2018 ShopRunBack
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of ShopRunBack
 **/

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
