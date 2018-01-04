<?php

if (! defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_2 ($module) {
    if (! Db::getInstance()->execute('ALTER TABLE ps_shoprunback_mapper ADD COLUMN `test` INT(11)')) {
        return false;
    }

    return true;
}
