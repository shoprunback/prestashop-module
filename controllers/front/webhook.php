<?php
if (! defined('_PS_VERSION_')) {
    die('No direct script access');
}

class ShopRunBackWebhookModuleFrontController extends ModuleFrontController
{
    public function initContent () {
        parent::initContent();

        echo $this->executeWebhook();
        exit;
    }

    private function executeWebhook () {
        SRBLogger::addLog('WEBHOOK CALLED');

        $webhook = file_get_contents("php://input");
        $webhook = json_decode($webhook);

        $type = isset($webhook->event) ? explode('.', $webhook->event)[0] : '';
        $id = isset($webhook->data->id) ? $webhook->data->id : '';

        if (! $type || ! $id) {
            SRBLogger::addLog('WEBHOOK FAILED: What is missing? [type: ' . $type . ' ; id: ' . $id . ']', 2);
            return header('HTTP/1.1 200 OK');
        }

        $item;
        switch ($type) {
            case 'shipback':
                SRBLogger::addLog('WEBHOOK IS SHIPBACK', 0, null, $type, $id);
                try {
                    $item = SRBReturn::getById($id);
                    $state = isset($webhook->data->state) ? $webhook->data->state : '';
                    $mode = isset($webhook->data->mode) ? $webhook->data->mode : '';

                    if (! $state && ! $mode) {
                        SRBLogger::addLog('WEBHOOK SHIPBACK FAILED: What is missing? [state: ' . $state . '; mode: ' . $mode . ']', 2, null, $type, $id);
                        return header('HTTP/1.1 200 OK');
                    }

                    $item->state = $state ? $state : $this->state;
                    $item->mode = $mode ? $mode : $this->mode;
                } catch (Exception $e) {
                    SRBLogger::addLog('WEBHOOK SHIPBACK FAILED: ' . $e, 3, null, 'order', $orderId);
                }

                break;
            default:
                return header('HTTP/1.1 200 OK');
                break;
        }

        $item->save();

        SRBLogger::addLog('WEBHOOK WORKED', 0, null, $type, $id);
        return header('HTTP/1.1 200 OK');
    }
}
