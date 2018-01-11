<?php

abstract class Synchronizer
{
    const SRB_DASHBOARD_URL = DASHBOARD_URL;
    const SRB_API_URL = self::SRB_DASHBOARD_URL . '/api/v1';

    static public function APIcall ($path, $type, $json = '')
    {
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

    static private function referenceMapping ($itemId, $itemType)
    {
        $map = SRBMap::getByIdItemAndIdType($itemId, $itemType);

        if ($map) {
            return $map->id_item_srb;
        }

        return false;
    }

    static public function sync ($item, $itemType, $path)
    {
        if (! Configuration::get('srbtoken')) {
            throw new ConfigurationException('No API token');
        }

        $identifier = $item::getIdentifier();
        $reference = $item->{$identifier};

        // Checks if we already have synchronized this item. If yes, we use the SRB ID, else we use the PS reference
        if ($item->getDBId() && ! ($item->{$identifier} == 0 && $itemType == 'shipback')) {
            $mapId = self::referenceMapping($item->getDBId(), $itemType);
            $reference = $mapId ? $mapId : $item->{$identifier};
        }

        // If we have a reference to use, we check if we have the item in the SRB DB (we check if we have a reference for the shipback case)
        $getResult = '';
        if ($reference) {
            $getResult = self::APIcall($path . '/' . $reference, 'GET');
        }

        // If we have a get result, we do a PUT, else we do a POST
        $postResult = '';
        if ($getResult == '') {
            $postResult = self::APIcall($path, 'POST', $item);
        } else {
            // Orders cannot be modified
            if ($path != 'orders') {
                $postResult = self::APIcall($path . '/' . $reference, 'PUT', $item);
            } else {
                // We still save the last sync call for orders (in case the user has installed the module, sync.ed some orders, uninstalled and reinstalled the module)
                $item->id_item_srb = json_decode($getResult)->id;
                self::mapApiCall($item, $itemType);
            }
        }

        // We check if we did a POST or a PUT (because of order case)
        if ($postResult) {
            try {
                $postResultDecoded = json_decode($postResult);
                $class = get_class($item);

                if (! $postResultDecoded) {
                    throw new SynchronizerException('Can\'t decode postresult: ' . $postResult, 4);
                }

                // If the POST resulted in an error or not
                if (isset($postResultDecoded->{$itemType}->errors)) {
                    SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" couldn\'t be synchronized! ' . $postResultDecoded->{$itemType}->errors[0], $itemType, $item->ps[$class::getIdColumnName()]);
                } elseif (isset($postResultDecoded->id)) {
                    SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" synchronized', $itemType, $item->ps[$class::getIdColumnName()]);
                    $item->id_item_srb = $postResultDecoded->id;
                    self::mapApiCall($item, $itemType);
                } else {
                    SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" couldn\'t be synchronized because of an unknown error!', $itemType, $item->ps[$class::getIdColumnName()]);
                }
            } catch (SynchronizerException $e) {
                SRBLogger::addLog($e, 3, null, $itemType, $item->ps[$class::getIdColumnName()]);
            }
        }

        return $postResult;
    }

    static public function delete ($item, $itemType, $path)
    {
        $identifier = $item::getIdentifier();
        $reference = self::referenceMapping($item->getDBId(), $itemType) ? self::referenceMapping($item->getDBId(), $itemType) : $item->{$identifier};

        $deleteResult = self::APIcall($path . '/' . $reference, 'DELETE');

        $class = get_class($item);
        $deleteResultDecoded = json_decode($deleteResult);
        if (isset($deleteResultDecoded->errors)) {
            SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" couldn\'t be deleted! ' . $deleteResultDecoded->errors[0], $itemType, $item->ps[$class::getIdColumnName()]);
            throw new SynchronizerException(ucfirst($itemType) . ' "' . $item->{$identifier} . '" couldn\'t be deleted!' . $deleteResultDecoded->errors[0]);
        }

        SRBLogger::addLog(ucfirst($itemType) . ' "' . $item->{$identifier} . '" has been deleted. ' . $deleteResult, $itemType, $item->ps[$class::getIdColumnName()]);

        return $deleteResult;
    }

    static private function mapApiCall ($item, $itemType)
    {
        $identifier = $item::getIdColumnName();
        $itemId = isset($item->$identifier) ? $item->$identifier : $item->ps[$identifier];

        $srbSql = Db::getInstance();

        SRBLogger::addLog($itemType . ': ' . $itemId . ' was the id', $itemType);
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
