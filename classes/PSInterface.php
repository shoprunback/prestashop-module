<?php

interface PSInterface
{
    static function getTableWithoutPrefix();
    static function getTableName();
    static function getIdColumnName();
    static function getIdentifier();
    static function getPreIdentifier(); // The attribute used to create the Identifier
}