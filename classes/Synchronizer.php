<?php

abstract class Synchronizer
{
    const SRB_DASHBOARD_URL = _PS_MODE_DEV_ ? 'http://localhost:3000' : (SANDBOX_MODE ? 'https://sandbox.dashboard.shoprunback.com' : 'https://dashboard.shoprunback.com');
    const SRB_WEB_URL = _PS_MODE_DEV_ ? 'http://localhost:3002': (SANDBOX_MODE ? 'https://sandbox.web.shoprunback.com' : 'https://web.shoprunback.com');
    const SRB_API_URL = self::SRB_DASHBOARD_URL . '/api/v1';

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
        $reference = $item->{$identifier};

        $map = SRBMap::getByIdItemAndIdType($item->getDBId(), $itemType);
        if ($map) {
            $reference = $map->id_item_srb;
        }

        $postResult = '';
        $getResult = self::APIcall($path . '/' . $reference, 'GET');

        if ($getResult == '') {
            $postResult = self::APIcall($path, 'POST', $item);
        } else {
            if ($path != 'orders') {
                $postResult = self::APIcall($path . '/' . $reference, 'PUT', $item);
            } else {
                $item->id_item_srb = json_decode($getResult)->id;
                self::logApiCall($item, $itemType);
            }
        }

        if ($postResult) {
            $postResultDecoded = json_decode($postResult);

            if (isset($postResultDecoded->{$itemType}->errors)) {
                Logger::addLog('[ShopRunBack] ' . ucfirst($itemType) . ' ' . $item->{$identifier} . ' couldn\'t be synchronized! ' . $postResultDecoded->{$itemType}->errors[0], 1, null, $itemType, $item->{$identifier}, true);
            } else {
                Logger::addLog('[ShopRunBack] ' . ucfirst($itemType) . ' ' . $item->{$identifier} . ' synchronized', 0, null, $itemType, $item->{$identifier}, true);
                $item->id_item_srb = $postResultDecoded->id;
                self::logApiCall($item, $itemType);
            }
        }

        return $postResult;
    }

    static private function logApiCall ($item, $itemType) {
        $identifier = $item::getIdColumnName();
        $itemId = isset($item->$identifier) ? $item->$identifier : $item->ps[$identifier];

        $srbSql = Db::getInstance();

        $data = [
            'id_item' => $itemId,
            'id_item_srb' => $item->id_item_srb,
            'type' => $itemType,
            'last_sent' => date('Y-m-d H:i:s'),
        ];
        $map = new SRBMap($data);
        $map->save();
    }
}
