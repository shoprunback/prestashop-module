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

class SRBNotification
{
    const NOTIFICATION_TABLE_NAME_NO_PREFIX = 'shoprunback_notification';

    public function __construct($dbData = array())
    {
        if (is_array($dbData) && !empty($dbData)) {
            $this->id_srb_notification = $dbData['id_srb_notification'] ? $dbData['id_srb_notification'] : 0;
            $this->message = $dbData['message'];
            $this->severity = $dbData['severity'];
            $this->object_type = $dbData['object_type'] ? $dbData['object_type'] : null;
            $this->object_id = $dbData['object_id'] ? $dbData['object_id'] : 0;
            $this->read = $dbData['read'];
            $this->created_at = $dbData['created_at'];
            $this->updated_at = $dbData['updated_at'];
        }
    }

    public static function getNotificationFullTableName()
    {
        return _DB_PREFIX_ . self::NOTIFICATION_TABLE_NAME_NO_PREFIX;
    }

    public static function getTableName()
    {
        return 'srbn';
    }

    public static function getIdColumnName()
    {
        return 'id_srb_notification';
    }

    public static function getTableIdentifier()
    {
        return self::getTableName() . '.' . self::getIdColumnName();
    }

    private static function generateNotificationsFromArrayOfResult($dbNotifications)
    {
        $notifications = array();
        foreach ($dbNotifications as $dbNotification) {
            $notifications[] = new self($dbNotification);
        }

        return $notifications;
    }

    public static function all($limit = 10, $offset = 0)
    {
        return self::generateNotificationsFromArrayOfResult(Db::getInstance()->executeS(Db::getInstance()->executeS(self::findAllQuery($limit, $offset))));
    }

    public static function allUnread($limit = 10, $offset = 0)
    {
        $sql = self::findAllQuery((int) $limit, (int) $offset);
        $sql->where(pSQL(self::getTableName()) . '.read = 0');

        return self::generateNotificationsFromArrayOfResult(Db::getInstance()->executeS($sql));
    }

    public static function get($id)
    {
        $sql = self::findQuery();
        $sql->where(pSQL(self::getTableIdentifier()) . ' = ' . pSQL($id)); // @TODO : check if we need (int) or pSQL()

        return new self(Db::getInstance()->executeS($sql));
    }

    private static function findQuery()
    {
        $sql = new DbQuery();
        $sql->select(pSQL(self::getTableName()) . '.*');
        $sql->from(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, pSQL(self::getTableName()));

        return $sql;
    }

    private static function findAllQuery($limit = 10, $offset = 0)
    {
        $sql = self::findQuery();
        $sql->limit((int) $limit, (int) $offset);
        $sql->orderBy(pSQL(self::getTableIdentifier()) . ' DESC');

        return $sql;
    }

    public function save()
    {
        SRBLogger::addLog(
            $this->message,
            SRBLogger::FATAL,
            isset($this->objectType) ? pSQL($this->objectType) : null,
            isset($this->objectId) ? $this->objectId : 0
        );

        if (isset($this->id_srb_notification) && !is_null($this->id_srb_notification)) {
            return $this->update();
        }

        return $this->create();
    }

    private function create()
    {
        $currentDate = date('Y-m-d H:i:s');

        return Db::getInstance()->insert(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, array(
            'message'       => pSQL($this->message),
            'severity'      => pSQL($this->severity),
            'object_type'   => isset($this->objectType) ? pSQL($this->objectType) : null,
            'object_id'     => isset($this->objectId) ? pSQL($this->objectId) : 0,
            'created_at'    => pSQL($currentDate),
            'updated_at'    => pSQL($currentDate)
        ));
    }

    private function update()
    {
        return Db::getInstance()->update(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, array(
            'message'       => pSQL($this->message),
            'severity'      => pSQL($this->severity),
            'object_type'   => isset($this->objectType) ? pSQL($this->objectType) : null,
            'object_id'     => isset($this->objectId) ? pSQL($this->objectId): 0,
            'read'          => pSQL($this->read),
            'updated_at'    => date('Y-m-d H:i:s')
        ), pSQL(self::getIdColumnName()) . ' = ' . pSQL($this->id_srb_notification));
    }

    public function delete()
    {
        return Db::getInstance()->delete(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, pSQL(self::getTableIdentifier()) . ' = ' . pSQL($this->id_srb_notification));
    }

    public static function markAsReadById($id)
    {
        return Db::getInstance()->update(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, array(
            'read' => true
        ), pSQL(self::getIdColumnName()) . ' = ' . pSQL($id));
    }

    public static function truncateTable()
    {
        Db::getInstance()->execute('TRUNCATE TABLE ' . pSQL(self::getNotificationFullTableName()));
    }
}
