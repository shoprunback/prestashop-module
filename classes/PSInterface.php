<?php

interface PSInterface
{
    static function getTableWithoutPrefix();
    static function getTableName();
    static function getIdColumnName();
    static function getIdentifier();
}