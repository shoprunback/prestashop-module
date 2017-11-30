<?php
require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');

require_once 'shoprunback.php';

$shoprunback = new ShopRunBack();
$action = $_POST['action'];
$parameters = '';

if (isset($_POST['params'])) {
    $parameters = $_POST['params'];
    $result = $shoprunback->{$action}($parameters);
} else {
    $result = $shoprunback->{$action}();
}

if (! is_string($result)) {
    $result = json_encode($result);
}

echo $result;
die;
