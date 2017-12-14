<?php

abstract class Synchronizer
{
    const SRB_BASE_URL = 'http://localhost:3000';
    // const SRB_BASE_URL = 'https://dashboard.shoprunback.com';
    const SRB_WEB_URL = 'http://localhost:3002';
    // const SRB_BASE_URL = 'https://web.shoprunback.com';
    const SRB_API_URL = self::SRB_BASE_URL . '/api/v1';
    const API_CALLS_TABLE_NAME_NO_PREFIX = 'srb_api_calls';
    const API_CALLS_TABLE_NAME = _DB_PREFIX_ . self::API_CALLS_TABLE_NAME_NO_PREFIX;
    const API_CALLS_INDEX_NAME = 'index_type_id_item';
    const API_CALLS_INDEX_COLUMNS = 'type, id_item';
    const RETURN_TABLE_NAME_NO_PREFIX = 'srb_return';
    const RETURN_TABLE_NAME = _DB_PREFIX_ . self::RETURN_TABLE_NAME_NO_PREFIX;
    const RETURN_INDEX_NAME = 'index_srb_return_id';
    const RETURN_INDEX_COLUMNS = 'srb_return_id';

    static public function APIcall ($path, $type, $json = '') {
        $path = str_replace(' ', '%20', $path);
        $url = self::SRB_API_URL . '/' . $path;

        $headers = ['accept: application/json'];
        $headers = ['Content-Type: application/json'];

        if (Configuration::get('srbtoken')) {
            $headers[] = 'Authorization: Token token=' . Configuration::get('srbtoken');
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

                if (! is_string($json)) {
                    $json = json_encode($json);
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
        $itemType = rtrim($itemType, 's'); // Without an "s" at the end (Product)
        $path = $itemType . 's'; // With an "s" (Products)
        $identifier = $item::getIdentifier();

        $postResult = '';
        $getResult = self::APIcall($path . '/' . $item->{$identifier}, 'GET');

        if ($getResult == '') {
            $postResult = self::APIcall($path, 'POST', $item);
        } else {
            if ($path != 'orders') {
                $postResult = self::APIcall($path . '/' . $item->{$identifier}, 'PUT', $item);
            }
        }

        self::insertApiCallLog($item, $itemType);

        return $postResult;
    }

    static public function delete ($item, $itemType) {
        $itemType = rtrim($itemType, 's'); // Without an "s" at the end (Product)
        $path = $itemType . 's'; // With an "s" (Products)
        $identifier = $item::getIdentifier();

        $deleteResult = self::APIcall($path . '/' . $item->{$identifier}, 'DELETE');

        self::insertApiCallLog($item, $itemType);

        return $deleteResult;
    }

    static private function insertApiCallLog ($item, $type) {
        $identifier = $item::getIdentifier();

        $srbSql = Db::getInstance();
        $srbSql->insert(self::API_CALLS_TABLE_NAME_NO_PREFIX, [
            'id_item' => $item->$identifier,
            'type' => $type,
            'last_sent' => date('Y-m-d H:i:s')
        ]);
    }
}
