<?php

use \Shoprunback\ElementManager;

trait PSElementTrait
{
    static protected function convertPSArrayToElements($PSArray, $withNestedElements = true)
    {
        $class = get_called_class();
        $elements = [];
        foreach ($PSArray as $PSItem) {
            try {
                $elements[] =  new $class($PSItem, $withNestedElements);
            } catch (SRBException $e) {

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
        $identifier = static::getIdColumnName();
        $type = static::getObjectTypeForMapping();
        $joinType = $onlySyncElements ? 'innerJoin' : 'leftJoin';
        $mapQuery = ElementMapper::findOnlyLastSentByTypeQuery($type);

        $sql = static::findAllQuery();
        $sql->select(ElementMapper::getTableName() . '.id_item_srb');
        $sql->{$joinType}(
            ElementMapper::MAPPER_TABLE_NAME_NO_PREFIX,
            ElementMapper::getTableName(),
            ElementMapper::getTableName() . '.id_item = ' . static::getTableName() . '.' . $identifier . '
                AND ' . ElementMapper::getTableName() . '.type = "' . $type . '"
                AND ' . ElementMapper::getTableName() . '.last_sent_at IN (' . $mapQuery . ')'
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
        $identifier = static::getIdColumnName();

        $sql = self::getComponentsToFindAllWithMappingQuery($onlySyncElements);
        $sql->select(ElementMapper::getTableName() . '.*');
        $sql->groupBy(static::getTableName() . '.' . $identifier);
        $sql->orderBy(ElementMapper::getTableName() . '.last_sent_at DESC');
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    static public function getAllWithMappingResult($onlySyncElements = false, $limit = 0, $offset = 0)
    {
        $class = get_called_class();
        return Db::getInstance()->executeS($class::findAllWithMappingQuery($onlySyncElements, $limit, $offset));
    }

    static public function fillElementsWithMapping(&$elements)
    {
        foreach ($elements as $key => $element) {
            $elements[$key]->id_item_srb = $element->ps['id_item_srb'];
            $elements[$key]->last_sent_at = $element->ps['last_sent_at'];
        }
    }

    static public function getAllWithMapping($onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        $elements = self::convertPSArrayToElements(static::getAllWithMappingResult($onlySyncElements, $limit, $offset), $withNestedElements);
        self::fillElementsWithMapping($elements);
        return $elements;
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
        } else {
            $element->syncNestedElements();
        }

        return $element;
    }

    public function sync()
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        $this->syncNestedElements();

        // To manage product duplication and brands with same name
        if (static::canHaveDuplicates()) {
            $this->checkDuplicates();
        }

        try {
            $result = $this->save();
            $this->mapApiCall();
            return $result;
        } catch (\Shoprunback\Error $e) {
            SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        }
    }

    static public function syncAll($newOnly = false)
    {
        $elements = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [];
        foreach ($elements as $element) {
            $responses[] = $element->sync();
        }

        return $responses;
    }

    static public function getManyByPreIdentifier($preIdentifier)
    {
        $sql = static::findAllQuery();
        $sql->where(static::getTableName() . '.' . static::getPreIdentifier() . ' = "' . pSQL($preIdentifier) . '"');
        $sql->orderBy(static::getTableIdentifier() . ' ASC');

        return self::convertPSArrayToElements(Db::getInstance()->executeS($sql));
    }

    // $updates must be an associative array, the key being the column in the DB and the value the associated value
    public function updateLocally($updates = [])
    {
        if (empty($updates)) return true;

        $result = Db::getInstance()->update(
            static::getTableWithoutPrefix(),
            $updates,
            static::getIdColumnName() . ' = "' . $this->getDBId() . '"'
        );

        // Security if the reference field has been updated
        $this->resetIdentifier();

        return $result;
    }

    public static function canHaveDuplicates()
    {
        return in_array(static::getObjectTypeForMapping(), ['product', 'brand']);
    }

    public function checkDuplicates()
    {
        if (!static::canHaveDuplicates()) return false;

        $itemsByReference = static::getManyByPreIdentifier($this->{static::getPreIdentifier()});
        $countItemsByReference = count($itemsByReference);

        if ($countItemsByReference > 1) {
            global $classTranslations;

            for ($i = 1; $i < $countItemsByReference; $i++) {
                $notification = new SRBNotification();
                $notification->severity = SRBLogger::FATAL;
                $notification->objectType = static::getObjectTypeForMapping();
                $notification->objectId = $itemsByReference[$i]->getDBId();

                switch (static::getObjectTypeForMapping()) {
                    case 'product':
                        $itemsByReference[$i]->reference = $itemsByReference[$i]->reference . '_' . $itemsByReference[$i]->getDBId();
                        $itemsByReference[$i]->updateLocally(['reference' => $itemsByReference[$i]->getReference()]);

                        $notification->message = $classTranslations['productDuplicationNotification'] . ' ' . $itemsByReference[$i]->label;
                        break;
                    case 'brand':
                        $itemsByReference[$i]->name = $itemsByReference[$i]->name . '_' . $itemsByReference[$i]->getDBId();
                        $itemsByReference[$i]->updateLocally(['name' => $itemsByReference[$i]->name]);

                        $notification->message = $classTranslations['brandDuplicationNotification'] . ' ' . $itemsByReference[$i]->name;
                        break;
                }

                $notification->save();
            }

            // We do a second loop to be sure all the duplicates have their own reference
            // This way, we avoid recursive calls with the sync()
            for ($i = 1; $i < $countItemsByReference; $i++) {
                if ($itemsByReference[$i]->getDBId() != $this->getDBId()) {
                    try {
                        $itemsByReference[$i]->sync();
                    } catch (\Shoprunback\Error $e) {
                        return $e;
                    }
                }
            }
        }
    }
}