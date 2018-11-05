<?php
class SRBLogger
{
    const INFO = 0;
    const WARNING = 1;
    const ERROR = 2;
    const FATAL = 3;
    const UNKNOWN = 4;

    public static function getPSLogTableName()
    {
        return 'l';
    }

    public static function addLog($message, $severity = self::INFO, $objectType = null, $objectId = 0)
    {
        if (substr($message, 0, 13) !== '[ShopRunBack]') {
            $message = '[ShopRunBack] ' . $message;
        }

        Logger::addLog($message, $severity, null, $objectType, $objectId, true, null);
    }

    public static function getLogs($limit = 100, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(pSQL(self::getPSLogTableName()) . '.*, e.firstname, e.lastname, e.email');
        $sql->from('log', pSQL(self::getPSLogTableName()));
        $sql->innerJoin('employee', 'e', pSQL(self::getPSLogTableName()) . '.id_employee = e.id_employee');
        $sql->where(pSQL(self::getPSLogTableName()) . '.message LIKE "[ShopRunBack] %"');
        $sql->limit($limit, $offset);
        $sql->orderBy(pSQL(self::getPSLogTableName()) . '.id_log DESC');

        return Db::getInstance()->executeS($sql);
    }
}
