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

use Shoprunback\Elements\Order as LibOrder;

class SRBOrder extends LibOrder implements PSElementInterface
{
    use PSElementTrait {
        findAllByMappingDateQuery as protected trait_findAllByMappingDateQuery;
    }

    public function __construct($psOrder, $withNestedElements = true)
    {
        $this->ps = $psOrder;
        $this->order_number = $this->extractOrderNumberFromPSArray($psOrder);
        $this->ordered_at = Util::convertDateFormatForDB($psOrder['date_add']);
        $this->customer = SRBCustomer::createFromOrder($psOrder);
        if ($withNestedElements) {
            $this->items = SRBItem::createItemsFromOrderId($this->getDBId());
        }

        if ($srbId = $this->getMapId()) {
            parent::__construct($srbId);
        } else {
            parent::__construct();
        }
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return 'orders';
    }

    public static function getTableName()
    {
        return 'o';
    }

    public static function getIdColumnName()
    {
        return 'id_order';
    }

    public static function getIdentifier()
    {
        return 'order_number';
    }

    public static function getPreIdentifier()
    {
        return 'order_number';
    }

    public static function getDisplayNameAttribute()
    {
        return 'order_number';
    }

    public static function getObjectTypeForMapping()
    {
        return 'order';
    }

    public static function getPathForAPICall()
    {
        return 'orders';
    }

    public static function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = static::getBaseQuery();
        $sql = self::addComponentsToQuery($sql);
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public static function getComponentsForShipbacks($sql)
    {
        $sql->select(pSQL(SRBShipback::getTableName()) . '.' . pSQL(SRBShipback::getIdColumnName()) . ', ' . pSQL(SRBShipback::getTableName()) . '.state');
        $sql->leftJoin( // We use leftJoin because orders may not have a return associated
            SRBShipback::SHIPBACK_TABLE_NAME_NO_PREFIX,
            pSQL(SRBShipback::getTableName()),
            pSQL(SRBShipback::getTableName()) . '.id_order = ' . pSQL(self::getTableIdentifier())
        );
        return $sql;
    }

    public static function getAllWithMapping($onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $sql = self::findAllWithMappingQuery($onlySyncElements, $limit, $offset);
        $sql = self::getComponentsForShipbacks($sql);

        return self::convertPSArrayToElements(Db::getInstance()->executeS($sql), $withNestedElements);
    }

    // Own functions
    private static function extractOrderNumberFromPSArray($psOrderArrayName)
    {
        if (isset($psOrderArrayName['reference'])) {
            return $psOrderArrayName['reference'];
        } elseif (isset($psOrderArrayName['id_order'])) {
            return $psOrderArrayName['id_order'];
        } else {
            return $psOrderArrayName['id'];
        }
    }

    public function getProducts()
    {
        $products = array();
        foreach ($this->items as $item) {
            $products[] = $item->product;
        }

        return $products;
    }

    public static function createFromShipback($shipback, $withNestedElements = true)
    {
        return new self($shipback, $withNestedElements);
    }

    public static function addComponentsToQuery($sql)
    {
        $sql->select(pSQL(self::getTableName()) . '.*, c.id_customer, c.firstname, c.lastname, c.email, a.id_address, a.address1, a.address2, a.postcode, a.city, a.phone, s.name as stateName, co.*');
        static::joinCustomer($sql);
        $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        $sql->innerJoin('country', 'co', 'a.id_country = co.id_country');
        $sql->leftJoin('state', 's', 'a.id_state = s.id_state');

        return $sql;
    }

    // Returns the attribute "shipped" of an order
    public function isShipped()
    {
        $sql = static::getBaseQuery();
        $sql = self::getComponentsToFindOrderState($sql);
        $sql->where('oh.id_order = ' . pSQL($this->ps['id_order']));
        $sql->select('os.shipped');

        return Db::getInstance()->getRow($sql)['shipped'];
    }

    // Base query to get the order_state, available by passing through the order_history
    public static function getComponentsToFindOrderState($sql)
    {
        // LeftJoin because the order may have no history
        // id_order_history is the primary key of order_history
        // Each order can have many lines of history, so we catch the MAX id_order_history to have the most recent state of the order
        $sql->leftJoin(
            'order_history',
            'oh',
            'oh.id_order = ' . pSQL(self::getTableIdentifier()) . ' AND oh.id_order_history IN (
                SELECT MAX(oh.id_order_history)
                FROM ' . _DB_PREFIX_ . 'order_history oh
                GROUP BY id_order
            )'
        );
        // To catch a state, we need to go through an history, so we keep the leftJoin
        $sql->leftJoin(
            'order_state',
            'os',
            'os.id_order_state = oh.id_order_state'
        );

        return $sql;
    }

    public static function getCountLikeOrderNumber($orderNumber)
    {
        $sql = static::getBaseQuery();
        static::addLikeOrderNumberToQuery($sql, $orderNumber);
        return self::getCountOfQuery($sql);
    }

    public static function getLikeOrderNumber($orderNumber, $onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $sql = self::findLikeOrderNumberQuery($orderNumber, $limit, $offset, $onlySyncElements); //@TODO : investigate this function
        $sql->groupBy(pSQL(static::getTableIdentifier()));
        return self::convertPSArrayToElements(Db::getInstance()->executeS($sql), $withNestedElements);
    }

    public static function findAllByMappingDateQuery($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $sql = self::trait_findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        $sql = self::getComponentsForShipbacks($sql);
        return $sql;
    }

    public static function findLikeOrderNumberQuery($orderNumber, $limit = 0, $offset = 0, $onlySyncElements = false)
    {
        $sql = self::findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        static::addLikeOrderNumberToQuery($sql, $orderNumber);
        return $sql;
    }

    public static function getLikeCustomer($customer, $onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $sql = self::findLikeCustomerQuery($customer, $limit, $offset, $onlySyncElements); //@TODO : investigate this function
        $sql->groupBy(pSQL(static::getTableIdentifier()));
        return self::convertPSArrayToElements(Db::getInstance()->executeS($sql), $withNestedElements);
    }

    public static function getCountLikeCustomer($customer)
    {
        $sql = static::getBaseQuery();
        static::joinCustomer($sql);
        static::addLikeCustomerToQuery($sql, $customer);
        return self::getCountOfQuery($sql);
    }

    public static function findLikeCustomerQuery($customer, $limit = 0, $offset = 0, $onlySyncElements = false)
    {
        $sql = self::findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        self::addLikeCustomerToQuery($sql, $customer);
        $sql->orderBy('srbm.last_sent_at ASC');

        return $sql;
    }
}
