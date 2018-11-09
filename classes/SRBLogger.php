<?php
/**
 * 2007-2018 ShopRunBack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ShopRunBack
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the ShopRunBack module to newer
 * versions in the future.
 *
 * @author ShopRunBack <contact@shoprunback.com>
 * @copyright 2007-2018 ShopRunBack
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of ShopRunBack
 **/

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
