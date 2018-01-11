<?php
class SRBLogger
{
    static public function addLog ($message, $severity = 0, $errorCode = null, $objectType = null, $objectId = 0, $allowDuplicate = true, $idEmployee = null) {
        if (substr($message, 0, 13) !== '[ShopRunBack]') {
            $message = '[ShopRunBack] ' . $message;
        }

        Logger::addLog($message, $severity, $errorCode, $objectType, $objectId, $allowDuplicate, $idEmployee);
    }
}
