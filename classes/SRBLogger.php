<?php
class SRBLogger
{
    const INFO = 0;
    const WARNING = 1;
    const ERROR = 2;
    const FATAL = 3;
    const UNKNOWN = 4;

    static public function addLog($message, $severity = self::INFO, $objectType = null, $objectId = 0)
    {
        if (substr($message, 0, 13) !== '[ShopRunBack]') {
            $message = '[ShopRunBack] ' . $message;
        }

        Logger::addLog($message, $severity, null, $objectType, $objectId, true, null);
    }

    static public function getTableName()
    {
        'l';
    }

    static public function getLogs()
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from('log', self::getTableName());
        'SELECT a.* , CONCAT(LEFT(e.firstname, 1), '. ', e.lastname) employee FROM `ps_log` a LEFT JOIN ps_employee e ON (a.id_employee = e.id_employee) WHERE 1 ORDER BY a.id_log DESC LIMIT 0, 50';
        return;
    }
}
