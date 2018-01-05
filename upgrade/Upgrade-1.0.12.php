<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_12 ($module) {
    if (! Db::getInstance()->execute('ALTER TABLE ps_shoprunback_mapper DROP COLUMN `test`')) {
        return false;
    }

    return true;
}
