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

use Shoprunback\Elements\Address as LibAddress;

class SRBAddress extends LibAddress implements PSInterface
{
    use PSElementTrait;

    public function __construct($address)
    {
        $this->id = $address['id_address'];
        $this->line1 = $address['address1'];
        $this->line2 = $address['address2'];
        $this->zipcode = $address['postcode'];
        $this->country_code = $address['iso_code'];
        $this->city = $address['city'];
        $this->state = $address['stateName'];
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return 'address';
    }

    public static function getTableName()
    {
        return 'a';
    }

    public static function getIdColumnName()
    {
        return 'id_address';
    }

    public static function getIdentifier()
    {
        return 'id';
    }

    public static function getPreIdentifier()
    {
        return 'id';
    }

    // Own functions
    public static function getByCustomerId($customerId)
    {
        return self::convertPSArrayToElements(Db::getInstance()->executeS(self::findByCustomerIdQuery($customerId)));
    }

    public static function getByOrderId($orderId)
    {
        return self::convertPSArrayToElements(Db::getInstance()->getRow(self::findByOrderIdQuery($orderId)));
    }

    public static function createFromOrder($psOrder)
    {
        return self::getNotSyncById($psOrder['id_address_delivery'], false);
    }

    protected static function findByCustomerIdQuery($customerId)
    {
        return self::findAllQuery()->where('id_customer = ' . pSQL($customerId));
    }

    protected static function findByOrderIdQuery($orderId)
    {
        return self::findAllQuery()->innerJoin('orders', pSQL(SRBOrder::getTableName()), pSQL(SRBOrder::getTableName()) . '.id_address_delivery = ' . pSQL(self::getTableName()) . '.id_address')->where(pSQL(SRBOrder::getTableName()) . '.id_order = ' . pSQL($orderId));
    }

    public static function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = static::getBaseQuery();
        $sql->select(self::getTableName() . '.*, s.name as stateName, co.*');
        $sql->innerJoin('country', 'co', self::getTableName() . '.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }
}
