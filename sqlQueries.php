<?php

function checkIfIndexExists() {
    return 'SELECT count(*)
        FROM information_schema.statistics
        WHERE TABLE_NAME = "' . pSQL(ElementMapper::getMapperTableName()) . '"
        AND INDEX_NAME = "' . pSQL(ElementMapper::MAPPER_INDEX_NAME) . '"
    ';
}

function createIndexQuery() {
    return 'ALTER TABLE `' . pSQL(ElementMapper::getMapperTableName()) . '` ADD INDEX ' . pSQL(ElementMapper::MAPPER_INDEX_NAME) . ' (' . pSQL(ElementMapper::MAPPER_INDEX_COLUMNS) . ')';
}

function createTableQuery() {
    return 'CREATE TABLE IF NOT EXISTS ' . pSQL(ElementMapper::getMapperTableName()) . '(
        `id_srb_map` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `id_item_srb` VARCHAR(40) NOT NULL,
        `id_item` INT(11) NOT NULL,
        `type` VARCHAR(40) NOT NULL,
        `last_sent_at` DATETIME NOT NULL
    )';
}

function createNotificationTableQuery() {
    return 'CREATE TABLE IF NOT EXISTS ' . pSQL(SRBNotification::getNotificationFullTableName()) . '(
        `id_srb_notification` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `message` VARCHAR(1000) NOT NULL,
        `severity` VARCHAR(20) NOT NULL,
        `object_type` VARCHAR(40),
        `object_id` INT(11),
        `read` BOOLEAN DEFAULT false,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL
    )';
}

function createReturnTableQuery() {
    return 'CREATE TABLE IF NOT EXISTS ' . pSQL(SRBShipback::getShipbackTableName()) . '(
        `id_srb_shipback` VARCHAR(40) NOT NULL PRIMARY KEY,
        `id_order` INT(11) UNIQUE NOT NULL,
        `state` VARCHAR(40) NOT NULL,
        `mode` VARCHAR(40) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `public_url` VARCHAR(255) NOT NULL
    )';
}

function dropTableQuery() {
    return 'DROP TABLE ' . pSQL(ElementMapper::getMapperTableName());
}

function dropNotificationTableQuery() {
    return 'DROP TABLE ' . pSQL(SRBNotification::getNotificationFullTableName());
}

function dropReturnTableQuery() {
    return 'DROP TABLE ' . pSQL(SRBShipback::getShipbackTableName());
}

function enableReturns() {
    return 'UPDATE ' . _DB_PREFIX_ . 'configuration SET value = 1 WHERE name = "PS_ORDER_RETURN"';
}
