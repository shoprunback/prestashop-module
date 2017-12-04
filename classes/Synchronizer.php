<?php

abstract class Synchronizer
{
    const API_CALLS_TABLE_NAME = _DB_PREFIX_ . 'srb_api_calls';
    const API_CALLS_INDEX_NAME = 'index_type_id_item';
    const API_CALLS_INDEX_COLUMNS = 'type, id_item';
    const SRB_BASE_URL = 'http://localhost:3000';
    // const SRB_BASE_URL = 'https://dashboard.shoprunback.com';
    const SRB_API_URL = self::SRB_BASE_URL . '/api/v1';

    public function APIcall ($path, $type, $json = '') {
        $path = str_replace(' ', '%20', $path);
        $url = self::SRB_API_URL . '/' . $path;

        $headers = ['accept: application/json'];
        $headers = ['Content-Type: application/json'];

        if (Configuration::get('token')) {
            $headers[] = 'Authorization: Token token=' . Configuration::get('token');
        }

        $opts = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        ];

        switch ($type) {
            case 'POST':
            case 'PUT':
                if (! $json) {
                    return false;
                }

                $opts[CURLOPT_POSTFIELDS] = $json;
            case 'DELETE':
                $opts[CURLOPT_CUSTOMREQUEST] = $type;
            case 'GET':
                break;
            default:
                return false;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    static public function sync ($item, $itemType) {
        $itemType = rtrim($itemType, 's');
        $path = $itemType . 's';
        $identifier = $item::getIdentifier();

        $postResult = '';
        $getResult = self::APIcall($path . '/' . $item->{$identifier}, 'GET');
        if ($getResult != '' && $path != 'orders') {
            $postResult = self::APIcall($path . '/' . $item->{$identifier}, 'PUT', json_encode($item));
        } else {
            $postResult = self::APIcall($path, 'POST', json_encode($item));
        }

        self::insertApiCallLog($item, $itemType);

        return $postResult;
    }

    static private function insertApiCallLog ($item, $type) {
        $dbName = str_replace(_DB_PREFIX_, '', self::API_CALLS_TABLE_NAME);
        $idItem = 'id_' . $type;
        if (! isset($item->$idItem)) {
            if (isset($item->reference)) {
                $idItem = 'reference';
            } else {
                $idItem = 'id';
            }
        }

        $srbSql = Db::getInstance();
        $srbSql->insert($dbName, [
            'id_item' => $item->$idItem,
            'type' => $type,
            'last_sent' => date('Y-m-d H:i:s')
        ]);
    }
}
