<?php
if (! defined('_PS_VERSION_')) {
    die('No direct script access');
}

class ShopRunBackWebhookModuleFrontController extends ModuleFrontController
{
    public function initContent ()
    {
        parent::initContent();

        echo $this->executeWebhook();
        exit;
    }

    private function executeWebhook ()
    {
        SRBLogger::addLog('WEBHOOK CALLED');

        $webhook = file_get_contents("php://input");
        $webhook = json_decode($webhook);

        $type = isset($webhook->event) ? explode('.', $webhook->event)[0] : '';
        $id = isset($webhook->data->id) ? $webhook->data->id : '';

        if (! $type || ! $id) {
            SRBLogger::addLog('WEBHOOK FAILED: What is missing? [type: ' . $type . ' ; id: ' . $id . ']');
            return self::returnHeaderHTTP(200);
        }

        if ($type == 'shipback') {
            SRBLogger::addLog('WEBHOOK IS SHIPBACK', $type, $id);
            try {
                $item = SRBShipback::getById($id);
                $state = isset($webhook->data->state) ? $webhook->data->state : '';
                $mode = isset($webhook->data->mode) ? $webhook->data->mode : '';

                if (! $state && ! $mode) {
                    SRBLogger::addLog('WEBHOOK SHIPBACK FAILED: What is missing? [state: ' . $state . '; mode: ' . $mode . ']', $type, $id);
                    return self::returnHeaderHTTP(200);
                }

                $item->state = $state ? $state : $this->state;
                $item->mode = $mode ? $mode : $this->mode;
                $item->save();
            } catch (ShipbackException $e) {
                SRBLogger::addLog('WEBHOOK SHIPBACK FAILED: ' . $e, $type, $id);
            }
        } else {
            SRBLogger::addLog('WEBHOOK TYPE UNKNOWN: ' . $type, $type, $id);
            return self::returnHeaderHTTP(200);
        }

        SRBLogger::addLog('WEBHOOK WORKED', $type, $id);
        return self::returnHeaderHTTP(200);
    }

    static private function returnHeaderHTTP ($httpCode)
    {
        switch ($httpCode) {
            case 403:
                return header('HTTP/1.0 403 Forbidden');
                break;
            case 404:
                return header('HTTP/1.0 404 Not Found');
                break;
            case 200:
            default:
                return header('HTTP/1.0 200 OK');
                break;
        }
    }
}
