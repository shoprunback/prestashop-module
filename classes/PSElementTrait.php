<?php

use \Shoprunback\ElementManager;

trait PSElementTrait
{
    static protected function convertPSArrayToElements($PSArray, $withNestedElements = true, $retrieveMapping = false)
    {
        $class = get_called_class();
        $elements = [];
        foreach ($PSArray as $PSItem) {
            try {
                $elements[] =  new $class($PSItem, $withNestedElements);
            } catch (SRBException $e) {

            }
        }

        return static::addMappingAttributesToElements($elements, $retrieveMapping);
    }

    static public function addMappingAttributesToElements($elements, $retrieveMapping = false)
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

    static public function addMappingsToElements($elements)
    {
        $ids = [];
        foreach ($elements as $key => $element) {
            $ids[] = $element->getDBId();
        }

        $mappings = ElementMapper::getMappingsForIdsAndType($ids, static::getObjectTypeForMapping());

        foreach ($mappings as $mapping) {
            foreach ($elements as $key => $element) {
                if ($element->getDBId() != $mapping->id_item) continue;

                $elements[$key]->addMappingAttributesFromMapping($mapping);
                break;
            }
        }

        return $elements;
    }

    static public function getTableIdentifier()
    {
        return static::getTableName() . '.' . static::getIdColumnName();
    }

    public function getDBId()
    {
        return $this->ps[static::getIdColumnName()];
    }

    static public function getComponentsToFindAllWithMappingQuery($onlySyncElements = false)
    {
        $joinType = $onlySyncElements ? 'innerJoin' : 'leftJoin';

        $sql = static::findAllQuery();
        $sql->select(ElementMapper::getTableName() . '.*');
        $sql->{$joinType}(
            ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX,
            ElementMapper::getTableName(),
            ElementMapper::getTableName() . '.id_item = ' . static::getTableIdentifier() . '
                AND ' . ElementMapper::getTableName() . '.type = "' . static::getObjectTypeForMapping() . '"
                AND ' . ElementMapper::getTableName() . '.last_sent_at IN (' . ElementMapper::findOnlyLastSentByTypeQuery(static::getObjectTypeForMapping()) . ')'
        );

        return $sql;
    }

    protected function findNotSyncQuery()
    {
        $type = static::getObjectTypeForMapping();
        $mapQuery = ElementMapper::findOnlyIdItemByTypeQuery($type);

        return static::findAllQuery()->where(static::getTableIdentifier() . ' NOT IN (' . $mapQuery . ')');
    }

    static protected function findAllWithMappingQuery($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncElements);
        $sql->groupBy(static::getTableIdentifier());
        $sql->orderBy(ElementMapper::getTableName() . '.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function getAllWithMappingResult($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return Db::getInstance()->executeS($class::findAllWithMappingQuery($onlySyncElements, $limit, $offset));
    }

    static public function getAllWithMapping($onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        return self::convertPSArrayToElements(static::getAllWithMappingResult($onlySyncElements, $limit, $offset), $withNestedElements);
    }

    static public function getCountAllWithMapping($onlySyncElements = false)
    {
        $class = get_called_class();
        return self::getCountOfQuery($class::findCountAllWithMappingQuery($onlySyncElements));
    }

    static protected function findCountAllWithMappingQuery($onlySyncElements = false)
    {
        return self::addCountToQuery(self::getComponentsToFindAllWithMappingQuery($onlySyncElements));
    }

    static protected function addCountToQuery($sql)
    {
        return $sql->select('COUNT(DISTINCT ' . static::getTableIdentifier() . ') as count');
    }

    static protected function findOneQuery($id)
    {
        return static::addWhereId(static::getComponentsToFindAllWithMappingQuery(true), $id);
    }

    static protected function findOneNotSyncQuery($id)
    {
        return static::addWhereId(static::findAllQuery(), $id);
    }

    static public function findAllByMappingDateQuery($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncElements);
        $sql->orderBy(ElementMapper::getTableName() . '.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);
        return $sql;
    }

    static protected function addWhereId($sql, $id)
    {
        $sql->where(self::getTableIdentifier() . ' = "' . pSQL($id) . '"');
        return $sql;
    }

    static public function checkResultOfGetById($result, $id)
    {
        if (!$result) {
            $class = get_called_class();
            $exceptionName = ucfirst($class::getObjectTypeForMapping()) . 'Exception';
            throw new $exceptionName('No ' . $class::getObjectTypeForMapping() . ' found with id ' . $id, 1);
        }
    }

    static public function extractNewElementFromGetByIdResult($result, $id, $withNestedElements)
    {
        static::checkResultOfGetById($result, $id);
        return static::createNewFromGetByIdQuery($result, $withNestedElements);
    }

    static public function getById($id, $withNestedElements = true)
    {
        return static::extractNewElementFromGetByIdResult(Db::getInstance()->getRow(static::findOneQuery($id)), $id, $withNestedElements);
    }

    static public function getNotSyncById($id, $withNestedElements = true)
    {
        return static::extractNewElementFromGetByIdResult(Db::getInstance()->getRow(static::findOneNotSyncQuery($id)), $id, $withNestedElements);
    }

    static public function createNewFromGetByIdQuery($result, $withNestedElements)
    {
        $class = get_called_class();
        return new $class($result, $withNestedElements);
    }

    static public function getCountOfQuery($sql)
    {
        return Db::getInstance()->getRow(self::addCountToQuery($sql))['count'];
    }

    static protected function addLimitToQuery($sql, $limit = 0, $offset = 0)
    {
        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }

        return $sql;
    }

    public function isMapped()
    {
        if (!$this->getMapId()) return false;

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

    static public function getAll($limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findAllQuery($limit, $offset)));
    }

    static public function getCountAll()
    {
        $class = get_called_class();
        return self::getCountOfQuery($class::findAllQuery());
    }

    static public function getAllNotSync()
    {
        $class = get_called_class();
        return self::convertPSArrayToElements(Db::getInstance()->executeS($class::findNotSyncQuery()));
    }

    public function mapApiCall()
    {
        $identifier = static::getIdColumnName();
        $itemId = isset($this->$identifier) ? $this->$identifier : $this->getDBId();

        SRBLogger::addLog('Saving map for ' . static::getObjectTypeForMapping() . ' with ID ' . $itemId, SRBLogger::INFO, static::getObjectTypeForMapping());
        $data = [
            'id_item' => $itemId,
            'id_item_srb' => $this->getMapId(),
            'type' => static::getObjectTypeForMapping(),
            'last_sent_at' => date('Y-m-d H:i:s'),
        ];
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

    static public function getManyByPreIdentifier($preIdentifier)
    {
        $sql = static::findAllQuery();
        $sql->where(static::getTableName() . '.' . static::getPreIdentifier() . ' = "' . pSQL($preIdentifier) . '"');
        $sql->orderBy(static::getTableIdentifier() . ' ASC');

        return self::convertPSArrayToElements(Db::getInstance()->executeS($sql));
    }

    public static function canHaveDuplicates()
    {
        return in_array(static::getObjectTypeForMapping(), ['product', 'brand']);
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

        $result = [];

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
        if (!static::canHaveDuplicates()) return false;

        // For products, we check if the reference in the PS DB is null, and if it is, we check the label
        if (static::getObjectTypeForMapping() === 'product' && $this->getOriginalPreIdentifier() == '') {
            return SRBProduct::getManyByName($this->label);
        }

        return static::getManyByPreIdentifier($this->getOriginalPreIdentifier());
    }
}