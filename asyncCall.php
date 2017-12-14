<?php
require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');

require_once 'shoprunback.php';
include_once 'classes/Synchronizer.php';

$class = $_POST['className'] ? $_POST['className'] : 'ShopRunBack';

$action = $_POST['action'];
$result = '';

if (isset($_POST['params'])) {
    switch ($action) {
        case 'sync':
            $item = $class::getById($_POST['params']);
            $result = $item->sync();
            break;
        case 'syncAll':
            $result = $class::syncAll($_POST['params']);
            break;
        default:
            echo false;
            die;
    }
} else {
    $result = $class->{$action}();
}

if (! is_string($result)) {
    $result = json_encode($result);
}

echo $result;
die;
