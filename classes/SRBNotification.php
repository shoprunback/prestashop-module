<?php
// Use this class to record important infos to show to the user
// Especially the ones you can register but cannot display directly
// Ex: When the user deletes a product already ordered, it is deleted on PS but cannot be deleted on SRB, so we register the error
class SRBNotification
{
    const NOTIFICATION_TABLE_NAME_NO_PREFIX = 'shoprunback_notification';

    public function __construct($dbData = [])
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

    static public function getNotificationFullTableName()
    {
        return _DB_PREFIX_ . self::NOTIFICATION_TABLE_NAME_NO_PREFIX;
    }

    static public function getTableName()
    {
        return 'srbn';
    }

    static public function getIdColumnName()
    {
        return 'id_srb_notification';
    }

    static public function getTableIdentifier()
    {
        return self::getTableName() . '.' . self::getIdColumnName();
    }

    static private function generateNotificationsFromArrayOfResult($dbNotifications)
    {
        $notifications = [];
        foreach ($dbNotifications as $dbNotification) {
            $notifications[] = new self($dbNotification);
        }

        return $notifications;
    }

    static public function all($limit = 10, $offset = 0)
    {
        return self::generateNotificationsFromArrayOfResult(Db::getInstance()->executeS(Db::getInstance()->executeS(self::findAllQuery($limit, $offset))));
    }

    static public function allUnread($limit = 10, $offset = 0)
    {
        $sql = self::findAllQuery($limit, $offset);
        $sql->where(self::getTableName() . '.read = 0');

        return self::generateNotificationsFromArrayOfResult(Db::getInstance()->executeS($sql));
    }

    static public function get($id)
    {
        $sql = self::findQuery();
        $sql->where(self::getTableIdentifier() . ' = ' . $id);

        return new self(Db::getInstance()->executeS($sql));
    }

    static private function findQuery()
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*');
        $sql->from(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, self::getTableName());

        return $sql;
    }

    static private function findAllQuery($limit = 10, $offset = 0)
    {
        $sql = self::findQuery();
        $sql->limit($limit, $offset);
        $sql->orderBy(self::getTableIdentifier() . ' DESC');

        return $sql;
    }

    public function save()
    {
        if (isset($this->id_srb_notification) && !is_null($this->id_srb_notification)) {
            return $this->update();
        }

        return $this->create();
    }

    private function create()
    {
        $currentDate = date('Y-m-d H:i:s');

        return Db::getInstance()->insert(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, [
            'message'       => pSQL($this->message),
            'severity'      => $this->severity,
            'object_type'   => $this->objectType ? pSQL($this->objectType) : null,
            'object_id'     => $this->objectId ? $this->objectId : 0,
            'created_at'    => $currentDate,
            'updated_at'    => $currentDate
        ]);
    }

    private function update()
    {
        return Db::getInstance()->update(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, [
            'message'       => pSQL($this->message),
            'severity'      => $this->severity,
            'object_type'   => $this->objectType ? pSQL($this->objectType) : null,
            'object_id'     => $this->objectId ? $this->objectId : 0,
            'read'          => $this->read,
            'updated_at'    => date('Y-m-d H:i:s')
        ], self::getIdColumnName() . ' = ' . $this->id_srb_notification);
    }

    public function delete()
    {
        return Db::getInstance()->delete(self::NOTIFICATION_TABLE_NAME_NO_PREFIX, self::getTableIdentifier() . ' = ' . $this->id_srb_notification);
    }

    public static function markAsReadById($id)
    {
        return Db::getInstance()->update(self::NOTIFICATION_TABLE_NAME_NO_PREFIX , [
            'read' => true
        ], self::getIdColumnName() . ' = ' . $id);
    }
}