<?php

include_once _PS_MODULE_DIR_ . '/shoprunback/lib/shoprunback-php/init.php';

interface PSElementInterface
{
    static function getTableName();
    static function getIdColumnName();
    static function getIdentifier();
    static function getDisplayNameAttribute();
    static function getObjectTypeForMapping();
    static function getPathForAPICall();
    static function findAllQuery($limit = 0, $offset = 0);
}