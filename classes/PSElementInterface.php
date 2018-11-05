<?php

include_once _PS_MODULE_DIR_ . '/shoprunback/lib/shoprunback-php/init.php';
include_once 'PSInterface.php';

interface PSElementInterface extends PSInterface
{
    public static function getDisplayNameAttribute();
    public static function getObjectTypeForMapping();
    public static function getPathForAPICall();
    public static function findAllQuery($limit = 0, $offset = 0);
}
