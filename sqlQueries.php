<?php

function createIndexQuery () {
    return 'ALTER TABLE `' . SRBMap::MAPPER_TABLE_NAME . '` ADD INDEX ' . SRBMap::MAPPER_INDEX_NAME . ' (' . SRBMap::MAPPER_INDEX_COLUMNS . ')';
}

function createTableQuery () {
    return 'CREATE TABLE IF NOT EXISTS ' . SRBMap::MAPPER_TABLE_NAME . '(
        `id_srb_map` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `id_item` INT(11) NOT NULL,
        `id_item_srb` VARCHAR(255) NOT NULL,
        `type` VARCHAR(255) NOT NULL,
        `last_sent_at` DATETIME NOT NULL
    )';
}

function createReturnTableQuery () {
    return 'CREATE TABLE IF NOT EXISTS ' . SRBReturn::RETURN_TABLE_NAME . '(
        `id_srb_return` VARCHAR(255) NOT NULL PRIMARY KEY,
        `id_order` INT(11) UNIQUE NOT NULL,
        `state` VARCHAR(255) NOT NULL,
        `mode` VARCHAR(255) NOT NULL,
        `created_at` DATETIME NOT NULL
    )';
}

function dropTableQuery () {
    return 'DROP TABLE ' . SRBMap::MAPPER_TABLE_NAME;
}

function dropReturnTableQuery () {
    return 'DROP TABLE ' . SRBReturn::RETURN_TABLE_NAME;
}

function enableReturns () {
    return 'UPDATE ps_configuration SET value = 1 WHERE name = "PS_ORDER_RETURN"';
}
