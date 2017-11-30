<?php

include_once 'Formalizer.php';

abstract class Synchronizer {
    public $dirurl;
    public $SRBModulePath;
    public $SRBModuleURL;
    public $url;
    public $apiUrl;

    public function __construct () {
        // Custom parameters
        $this->url = 'http://localhost:3000';
        // $this->url = 'https://dashboard.shoprunback.com';
        $this->apiUrl = $this->url . '/api/v1';
        $this->dirurl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $this->SRBModulePath = _PS_MODULE_DIR_ . $this->name;
        $this->SRBModuleURL = $this->dirurl . '/modules/' . $this->name;
        $this->bootstrap = true;
    }

    public function APIcall ($path, $type, $json = '') {
        $path = str_replace(' ', '%20', $path);
        $url = $this->apiUrl . '/' . $path;

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

    protected function insertApiCallLog ($item, $type) {
        $dbName = str_replace(_DB_PREFIX_, '', ShopRunBack::API_CALLS_TABLE_NAME);
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

    protected function getAll () {
        $manufacturerSql = new DbQuery();
        $manufacturerSql->select('m.*');
        $manufacturerSql->from('manufacturer', 'm');
        $manufacturerSql->where('m.id_manufacturer = ' . $manufacturer);
        $manufacturerFromDB = Db::getInstance()->executeS($manufacturerSql)[0];
        $manufacturer = $this->formalizer->arrayToObject($manufacturerFromDB);
    }

    public function postItem () {

    }
}
