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

use \Shoprunback\ElementManager;

trait PSElementTrait
{
    protected static function convertPSArrayToElements($PSArray, $withNestedElements = true, $retrieveMapping = false)
    {
        $class = get_called_class();
        $elements = array();
        foreach ($PSArray as $PSItem) {
            try {
                $elements[] =  new $class($PSItem, $withNestedElements);
            } catch (SRBException $e) {
            }
        }

        return static::addMappingAttributesToElements($elements, $retrieveMapping);
    }

    public static function addMappingAttributesToElements($elements, $retrieveMapping = false)
    {
        if ($retrieveMapping) {
            return static::addMappingsToElements($elements);
        }

        foreach ($elements as $key => $element) {
            $elements[$key]->addMappingAttributesFromPS();
        }

        return $elements;
    }

    public function addMappingAttributesFromPS()
    {
        $this->id_item_srb = isset($this->ps['id_item_srb']) ? $this->ps['id_item_srb'] : null;
        $this->last_sent_at = isset($this->ps['last_sent_at']) ? $this->ps['last_sent_at'] : null;
        $this->id_srb_shipback = isset($this->ps['id_srb_shipback']) ? $this->ps['id_srb_shipback'] : null;
        $this->state = isset($this->ps['state']) ? $this->ps['state'] : null;
        $this->delivery = isset($this->ps['delivery']) ? $this->ps['delivery'] : null;
    }

    public function addMappingAttributesFromMapping($mapping)
    {
        $this->id_item_srb = $mapping->id_item_srb;
        $this->last_sent_at = $mapping->last_sent_at;
    }

    public static function addMappingsToElements($elements)
    {
        $ids = array();
        foreach ($elements as $key => $element) {
            $ids[] = $element->getDBId();
        }

        $mappings = ElementMapper::getMappingsForIdsAndType($ids, static::getObjectTypeForMapping());

        foreach ($mappings as $mapping) {
            foreach ($elements as $key => $element) {
                if ($element->getDBId() != $mapping->id_item) {
                    continue;
                }

                $elements[$key]->addMappingAttributesFromMapping($mapping);
                break;
            }
        }

        return $elements;
    }

    public static function getTableIdentifier()
    {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    public function getDBId()
    {
        return $this->ps[static::getIdColumnName()];
    }

    public static function getComponentsToFindAllWithMappingQuery($onlySyncElements = false)
    {
        $joinType = $onlySyncElements ? 'innerJoin' : 'leftJoin';

        $sql = static::findAllQuery();
        ElementMapper::addSelectAllToQuery($sql);
        $sql->{$joinType}(
            ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX,
            pSQL(ElementMapper::getTableName()),
            pSQL(ElementMapper::getTableName()) . '.id_item = ' . pSQL(static::getTableIdentifier()) . '
                AND ' . pSQL(ElementMapper::getTableName()) . '.type = "' . pSQL(static::getObjectTypeForMapping()) . '"'
        );

        return $sql;
    }

    protected function findNotSyncQuery()
    {
        $type = static::getObjectTypeForMapping();
        $mapQuery = ElementMapper::findOnlyIdItemByTypeQuery($type);

        return static::findAllQuery()->where(static::getTableIdentifier() . ' NOT IN (' . $mapQuery . ')');
    }

    protected static function findAllWithMappingQuery($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncElements);
        $sql->groupBy(pSQL(static::getTableIdentifier()));
        $sql->orderBy(pSQL(ElementMapper::getTableName()) . '.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public static function getAllWithMappingResult($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return Db::getInstance()->executeS($class::findAllWithMappingQuery($onlySyncElements, $limit, $offset));
    }

    public static function getAllWithMapping($onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        return self::convertPSArrayToElements(static::getAllWithMappingResult($onlySyncElements, $limit, $offset), $withNestedElements);
    }

    public static function getCountAllWithMapping($onlySyncElements = false)
    {
        $class = get_called_class();
        return self::getCountOfQuery($class::findCountAllWithMappingQuery($onlySyncElements));
    }

    protected static function findCountAllWithMappingQuery($onlySyncElements = false)
    {
        return self::addCountToQuery(self::getComponentsToFindAllWithMappingQuery($onlySyncElements));
    }

    protected static function addCountToQuery($sql)
    {
        return $sql->select('COUNT(DISTINCT ' . pSQL(static::getTableIdentifier()) . ') as count');
    }

    protected static function findOneQuery($id)
    {
        return static::addWhereId(static::getComponentsToFindAllWithMappingQuery(true), $id);
    }

    protected static function findOneNotSyncQuery($id)
    {
        return static::addWhereId(static::findAllQuery(), $id);
    }

    public static function findAllByMappingDateQuery($onlySyncElements = false, $limit = 0, $offset = 0, $orderBy = 'DESC')
    {
        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncElements);
        $sql->orderBy(ElementMapper::getTableName() . '.last_sent_at ' . $orderBy);
        $sql = self::addLimitToQuery($sql, $limit, $offset);
        return $sql;
    }

    protected static function addWhereId($sql, $id)
    {
        $sql->where(pSQL(self::getTableIdentifier()) . ' = "' . pSQL($id) . '"');
        return $sql;
    }

    public static function checkResultOfGetById($result, $id)
    {
        if (!$result) {
            $class = get_called_class();
            $exceptionName = ucfirst($class::getObjectTypeForMapping()) . 'Exception';
            throw new $exceptionName('No ' . $class::getObjectTypeForMapping() . ' found with id ' . $id, 1);
        }
    }

    public static function extractNewElementFromGetByIdResult($result, $id, $withNestedElements)
    {
        static::checkResultOfGetById($result, $id);
        return static::createNewFromGetByIdQuery($result, $withNestedElements);
    }

    public static function getById($id, $withNestedElements = true)
    {
        return static::extractNewElementFromGetByIdResult(Db::getInstance()->getRow(static::findOneQuery($id)), $id, $withNestedElements);
    }

    public static function getNotSyncById($id, $withNestedElements = true)
    {
        return static::extractNewElementFromGetByIdResult(Db::getInstance()->getRow(static::findOneNotSyncQuery($id)), $id, $withNestedElements);
    }

    public static function createNewFromGetByIdQuery($result, $withNestedElements)
    {
        $class = get_called_class();
        return new $class($result, $withNestedElements);
    }

    public static function getCountOfQuery($sql)
    {
        return Db::getInstance()->getRow(self::addCountToQuery($sql))['count'];
    }

    protected static function addLimitToQuery($sql, $limit = 0, $offset = 0)
    {
        if ($limit > 0) {
            $sql->limit((int) $limit, (int) $offset);
        }

        return $sql;
    }

    public function isMapped()
    {
        if (!$this->getMapId()) {
            return false;
        }

        return true;
    }

    public function getMapId()
    {
        return isset($this->id) ? $this->id : ElementMapper::getMappingIdIfExists($this->getDBId(), static::getObjectTypeForMapping());
    }

    public function getName()
    {
        $name = static::getDisplayNameAttribute();
        return $this->{$name};
    }

    public static function getAll($limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findAllQuery($limit, $offset)));
    }

    public static function getAllBySyncDate($limit = 0, $offset = 0, $orderBy = 'DESC')
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findAllByMappingDateQuery(false, $limit, $offset, $orderBy)));
    }

    public static function getCountAll()
    {
        return static::getCountOfQuery(static::getBaseQuery());
    }

    public static function getAllNotSync()
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findNotSyncQuery()));
    }

    public static function getBaseQuery()
    {
        $sql = new DbQuery();
        $sql->from(pSQL(static::getTableWithoutPrefix()), pSQL(static::getTableName()));
        return $sql;
    }

    public static function joinCustomer(&$sql)
    {
        $sql->innerJoin(SRBCustomer::getTableWithoutPrefix(), SRBCustomer::getTableName(), SRBOrder::getTableName() . '.' . SRBCustomer::getIdColumnName() . ' = ' . SRBCustomer::getTableIdentifier());
    }

    public static function addLikeCustomerToQuery(&$sql, $customer)
    {
        $sql->where(
            SRBCustomer::getTableName() . '.firstname LIKE "%' . pSQL($customer) . '%" OR ' .
            SRBCustomer::getTableName() . '.lastname LIKE "%' . pSQL($customer) . '%" OR
            CONCAT(' . SRBCustomer::getTableName() . '.firstname, " ", ' . SRBCustomer::getTableName() . '.lastname) LIKE "%' . pSQL($customer) . '%"'
        );
    }

    public static function addLikeOrderNumberToQuery(&$sql, $orderNumber)
    {
        $sql->where(SRBOrder::getTableName() . '.reference LIKE "%' . $orderNumber . '%"');
    }

    public static function getByMapper($idItemSRB)
    {
        return static::getById(ElementMapper::getByIdItemSRBAndItemType($idItemSRB, static::getObjectTypeForMapping())->id_item);
    }

    public function mapApiCall()
    {
        $identifier = static::getIdColumnName();
        $itemId = isset($this->$identifier) ? $this->$identifier : $this->getDBId();

        SRBLogger::addLog('Saving map for ' . static::getObjectTypeForMapping() . ' with ID ' . $itemId, SRBLogger::INFO, static::getObjectTypeForMapping());
        $data = array(
            'id_item' => $itemId,
            'id_item_srb' => $this->getMapId(),
            'type' => static::getObjectTypeForMapping(),
            'last_sent_at' => date('Y-m-d H:i:s'),
        );
        $map = new ElementMapper($data);
        $map->save();
    }

    public function syncNestedElements()
    {
        foreach ($this->getAllNestedElements() as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $this->$key[$k] = self::syncNestedElement($v);
                }
            } else {
                $this->$key = self::syncNestedElement($value);
            }
        }
    }

    public static function syncNestedElement($element)
    {
        if ($element instanceof PSElementInterface) {
            $element->sync();
        } elseif (method_exists($element, 'syncNestedElements')) {
            $element->syncNestedElements();
        }

        return $element;
    }

    public function sync($syncDuplicates = true)
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        // Add condition for Brands (they don't have nested elements)
        if (method_exists($this, 'syncNestedElements')) {
            $this->syncNestedElements();
        }

        // To manage product duplication and brands with same name
        if (static::canHaveDuplicates()) {
            try {
                return $this->syncDuplicates($syncDuplicates);
            } catch (\Shoprunback\Error $e) {
                SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
            }
        }

        try {
            return $this->doSync();
        } catch (\Shoprunback\Error $e) {
            SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        }
    }

    protected function doSync()
    {
        try {
            $result = $this->save();
            $this->mapApiCall();
            return $result;
        } catch (\Shoprunback\Error $e) {
            SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        }
    }

    public static function getManyByPreIdentifier($preIdentifier)
    {
        $sql = static::findAllQuery();
        $sql->where(static::getTableName() . '.' . static::getPreIdentifier() . ' = "' . pSQL($preIdentifier) . '"');
        $sql->orderBy(static::getTableIdentifier() . ' ASC');

        return self::convertPSArrayToElements(Db::getInstance()->executeS($sql));
    }

    public static function canHaveDuplicates()
    {
        return in_array(static::getObjectTypeForMapping(), array('product', 'brand'));
    }

    public function syncDuplicates($syncDuplicates = true)
    {
        $duplicates = $this->getDuplicates();
        $countDuplicates = count($duplicates);

        if ($countDuplicates === 1) {
            return $this->doSync();
        }

        if (!$syncDuplicates) {
            $this->reference = $this->getDBId();
            SRBLogger::addLog('The ' . self::getObjectTypeForMapping() . ' "' . $this->getName() . '" has its reference/name shared with others, so it has been replaced by its ID on ShopRunBack\'s database.', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
            return $this->doSync();
        }

        $result = array();

        for ($i = 0; $i < $countDuplicates; $i++) {
            $duplicates[$i]->reference = $duplicates[$i]->getDBId();
            SRBLogger::addLog('The ' . self::getObjectTypeForMapping() . ' "' . $duplicates[$i]->getName() . '" has its reference/name shared with others, so it has been replaced by its ID on ShopRunBack\'s database.', SRBLogger::INFO, self::getObjectTypeForMapping(), $duplicates[$i]->getDBId());

            try {
                $result[] = $duplicates[$i]->doSync();
            } catch (\Shoprunback\Error $e) {
                SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
            }
        }

        return $result;
    }

    public function getOriginalPreIdentifier()
    {
        return $this->ps[static::getPreIdentifier()];
    }

    public function getDuplicates()
    {
        if (!static::canHaveDuplicates()) {
            return false;
        }

        // For products, we check if the reference in the PS DB is null, and if it is, we check the label
        if (static::getObjectTypeForMapping() === 'product' && $this->getOriginalPreIdentifier() == '') {
            return SRBProduct::getManyByName($this->label);
        }

        return static::getManyByPreIdentifier($this->getOriginalPreIdentifier());
    }

    public static function syncAll()
    {
        $elements = static::getAllBySyncDate(0, 0, 'ASC');

        foreach ($elements as $key => $element) {
            $element->sync(false);
        }
    }
}
