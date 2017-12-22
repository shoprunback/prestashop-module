<?php
require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');

require_once 'shoprunback.php';
include_once 'classes/Synchronizer.php';

$class = $_POST['className'] ? $_POST['className'] : 'ShopRunBack';

$action = $_POST['action'];
$result = '';

Logger::addLog('[ShopRunBack] asynccall: ' . json_encode($_POST), 0, null, $class, 0, true);
if (isset($_POST['params'])) {
    Logger::addLog('[ShopRunBack] asynccallparam: ' . $_POST['params'], 0, null, $class, 0, true);
    Logger::addLog('[ShopRunBack] asynccallaction: ' . $_POST['action'], 0, null, $class, 0, true);
    switch ($action) {
        case 'sync':
            Logger::addLog('[ShopRunBack] asynccallsync', 0, null, $class, 0, true);
            $item = $class::getById($_POST['params']);
            $result = $item->sync();
            break;
        case 'syncAll':
            Logger::addLog('[ShopRunBack] asynccallsyncall', 0, null, $class, 0, true);
            $result = $class::syncAll($_POST['params']);
            break;
        default:
            echo false;
            die;
    }
} else {
    Logger::addLog('[ShopRunBack] asynccallnoparam', 0, null, $class, 0, true);
    $result = $class->{$action}();
}

if (! is_string($result)) {
    $result = json_encode($result);
}

echo $result;
die;
