<?php
// This file must be called by an AJAX function to be asynchrous
require_once(_PS_MODULE_DIR_ . '../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . '../init.php');

require_once 'shoprunback.php';
include_once 'classes/SRBLogger.php';

$class = Tools::getIsset('className') ? Tools::getValue('className') : 'ShopRunBack';

$actionSRB = Tools::getValue('actionSRB');
$result = '';

if (Tools::getIsset('params')) {
    if ($actionSRB == 'sync') {
        SRBLogger::addLog('AsyncCall sync ' . $class . ' ' . Tools::getValue('params'), SRBLogger::INFO);
        try {
            $element = $class::getNotSyncById(Tools::getValue('params'));
            $result = $element->sync(false);
        } catch (SRBException $e) {
            SRBLogger::addLog($e, SRBLogger::FATAL, $class);
        }
    } elseif ($actionSRB == 'syncAll') {
        $result = $class::syncAll();
    } else {
        throw new SRBException('AsyncCall unknown action ' . $actionSRB . '. Param: ' . Tools::getValue('params'), 3);
    }
} else {
    SRBLogger::addLog('AsyncCall params is missing. Action: ' . $actionSRB . '. Class: ' . $class, SRBLogger::ERROR);
    throw new SRBException('AsyncCall params is missing. Action: ' . $actionSRB);
}

if (! is_string($result)) {
    $result = json_encode($result);
}

echo $result;
die;
