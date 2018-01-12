<?php
class SRBLogger
{
    static public function addLog ($message, $objectType = null, $objectId = 0)
    {
        if (substr($message, 0, 13) !== '[ShopRunBack]') {
            $message = '[ShopRunBack] ' . $message;
        }

        Logger::addLog($message, 0, null, $objectType, $objectId, true, null);
    }
}
