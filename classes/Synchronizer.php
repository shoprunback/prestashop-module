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

    static private function referenceMapping ($itemId, $itemType) {
        $map = SRBMap::getByIdItemAndIdType($itemId, $itemType);

        if ($map) {
            return $map->id_item_srb;
        }

        return false;
    }

    static public function sync ($item, $itemType) {
        $itemType = rtrim($itemType, 's'); // Without an "s" at the end (Product)
        $path = $itemType . 's'; // With an "s" (Products)
        $identifier = $item::getIdentifier();
        $reference = self::referenceMapping($item->getDBId(), $itemType) ? self::referenceMapping($item->getDBId(), $itemType) : $item->{$identifier};

        $postResult = '';
        $getResult = self::APIcall($path . '/' . $reference, 'GET');

        if ($getResult == '') {
            $postResult = self::APIcall($path, 'POST', $item);
        } else {
            if ($path != 'orders') {
                $postResult = self::APIcall($path . '/' . $reference, 'PUT', $item);
            } else {
                $item->id_item_srb = json_decode($getResult)->id;
                self::mapApiCall($item, $itemType);
            }
        }

        if ($postResult) {
            $postResultDecoded = json_decode($postResult);
            $class = get_class($item);

            if (isset($postResultDecoded->{$itemType}->errors)) {
                SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" couldn\'t be synchronized! ' . $postResultDecoded->{$itemType}->errors[0], 1, null, $itemType, $item->ps[$class::getIdColumnName()]);
            } else {
                SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" synchronized', 0, null, $itemType, $item->ps[$class::getIdColumnName()]);
                $item->id_item_srb = $postResultDecoded->id;
                self::mapApiCall($item, $itemType);
            }
        }

        return $postResult;
    }

    static public function delete ($item, $itemType) {
        $itemType = rtrim($itemType, 's'); // Without an "s" at the end (Product)
        $path = $itemType . 's'; // With an "s" (Products)
        $identifier = $item::getIdentifier();
        $reference = self::referenceMapping($item->getDBId(), $itemType) ? self::referenceMapping($item->getDBId(), $itemType) : $item->{$identifier};

        $deleteResult = self::APIcall($path . '/' . $reference, 'DELETE');

        $class = get_class($item);
        $deleteResultDecoded = json_decode($deleteResult);
        if (isset($deleteResultDecoded->errors)) {
            SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" couldn\'t be deleted! ' . $deleteResultDecoded->errors[0], 3, null, $itemType, $item->ps[$class::getIdColumnName()]);
            return false;
        }

        SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" has been deleted. ' . $deleteResult, 0, null, $itemType, $item->ps[$class::getIdColumnName()]);

        return $deleteResult;
    }

    static private function mapApiCall ($item, $itemType) {
        $identifier = $item::getIdColumnName();
        $itemId = isset($item->$identifier) ? $item->$identifier : $item->ps[$identifier];

        $srbSql = Db::getInstance();

        $data = [
            'id_item' => $itemId,
            'id_item_srb' => $item->id_item_srb,
            'type' => $itemType,
            'last_sent_at' => date('Y-m-d H:i:s'),
        ];
        $map = new SRBMap($data);
        $map->save();
    }
}
