<?php

interface PSInterface
{
    public static function getTableWithoutPrefix();
    public static function getTableName();
    public static function getIdColumnName();
    public static function getIdentifier();
    public static function getPreIdentifier(); // The attribute used to create the Identifier
}
