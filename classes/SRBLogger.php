<?php
class SRBLogger
{
    static public function addLog ($message, $severity = 0, $errorCode = null, $objectType = null, $objectId = 0, $allowDuplicate = true, $idEmployee = null) {
        Logger::addLog('[ShopRunBack] ' . $message, $severity, $errorCode, $objectType, $objectId, $allowDuplicate, $idEmployee);
    }
}
