<?php
// This file must be called by an AJAX function to be asynchrous
require_once(_PS_MODULE_DIR_ . '../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . '../init.php');

require_once 'shoprunback.php';
include_once 'classes/Synchronizer.php';
include_once 'classes/SRBLogger.php';

$class = $_POST['className'] ? $_POST['className'] : 'ShopRunBack';

$action = $_POST['action'];
$result = '';

if (isset($_POST['params'])) {
    switch ($action) {
        case 'sync':
            SRBLogger::addLog('AsyncCall sync ' . $class . ' ' . $_POST['params']);
            try {
                $item = $class::getById($_POST['params']);
                $result = $item->sync();
            } catch (Exception $e) {
                SRBLogger::addLog($e, 2, null, $class);
            }
            break;
        case 'syncAll':
            SRBLogger::addLog('AsyncCall syncAll ' . $class);
            $result = $class::syncAll($_POST['params']);
            break;
        default:
            SRBLogger::addLog('AsyncCall unknown action ' . $action . '. Param: ' . $_POST['params'], 3, null, $class);
            throw new Exception('AsyncCall unknown action ' . $action . '. Param: ' . $_POST['params']);
    }
} else {
    SRBLogger::addLog('AsyncCall params is missing. Action: ' . $action, 3, null, $class);
    throw new Exception('AsyncCall params is missing. Action: ' . $action);
}

if (! is_string($result)) {
    $result = json_encode($result);
}

echo $result;
die;
