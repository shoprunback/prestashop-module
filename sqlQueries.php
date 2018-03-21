<?php

function checkIfIndexExists () {
    return 'SELECT count(*)
        FROM information_schema.statistics
        WHERE TABLE_NAME = "' . SRBMap::getMapperTableName() . '"
        AND INDEX_NAME = "' . SRBMap::MAPPER_INDEX_NAME . '"
    ';
}

function createIndexQuery () {
    return 'ALTER TABLE `' . SRBMap::getMapperTableName() . '` ADD INDEX ' . SRBMap::MAPPER_INDEX_NAME . ' (' . SRBMap::MAPPER_INDEX_COLUMNS . ')';
}

function createTableQuery () {
    return 'CREATE TABLE IF NOT EXISTS ' . SRBMap::getMapperTableName() . '(
        `id_srb_map` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `id_item_srb` VARCHAR(40) NOT NULL,
        `id_item` INT(11) NOT NULL,
        `type` VARCHAR(40) NOT NULL,
        `last_sent_at` DATETIME NOT NULL
    )';
}

function createReturnTableQuery () {
    return 'CREATE TABLE IF NOT EXISTS ' . SRBShipback::getShipbackTableName() . '(
        `id_srb_shipback` VARCHAR(40) NOT NULL PRIMARY KEY,
        `id_order` INT(11) UNIQUE NOT NULL,
        `state` VARCHAR(40) NOT NULL,
        `mode` VARCHAR(40) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `public_url` VARCHAR(255) NOT NULL
    )';
}

function dropTableQuery () {
    return 'DROP TABLE ' . SRBMap::getMapperTableName();
}

function dropReturnTableQuery () {
    return 'DROP TABLE ' . SRBShipback::getShipbackTableName();
}

function enableReturns () {
    return 'UPDATE ps_configuration SET value = 1 WHERE name = "PS_ORDER_RETURN"';
}
