<?php

interface Resource
{
    static function getTableName();
    static function getIdColumnName();
    static function getIdentifier();
    static function getDisplayNameAttribute();
    static function getObjectTypeForMapping();
    static function getPathForAPICall();
    static function findAllQuery($limit = 0, $offset = 0);
}