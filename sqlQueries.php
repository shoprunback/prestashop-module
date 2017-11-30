<?php

function createIndexQuery () {
    return 'ALTER TABLE `' . ShopRunBack::API_CALLS_TABLE_NAME . '` ADD INDEX ' . ShopRunBack::API_CALLS_INDEX_NAME . ' (' . ShopRunBack::API_CALLS_INDEX_COLUMNS . ')';
}

function createTableQuery () {
    return 'CREATE TABLE IF NOT EXISTS ' . ShopRunBack::API_CALLS_TABLE_NAME . '(
        `id_srb_api_calls` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `id_item` INT(11) NOT NULL,
        `type` VARCHAR(256) NOT NULL,
        `last_sent` DATETIME NOT NULL
    )';
}

function dropIndexQuery () {
    return 'ALTER TABLE ' . ShopRunBack::API_CALLS_TABLE_NAME . ' DROP INDEX ' . ShopRunBack::API_CALLS_INDEX_NAME;
}

function dropTableQuery () {
    return 'DROP TABLE ' . ShopRunBack::API_CALLS_TABLE_NAME;
}
