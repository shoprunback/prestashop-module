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
        Logger::addLog('[ShopRunBack] WEBHOOK CALLED', 0, null, '', 0, true);

        $webhook = file_get_contents("php://input");
        $webhook = json_decode($webhook);

        $type = isset($webhook->event) ? explode('.', $webhook->event)[0] : '';
        $id = isset($webhook->data->id) ? $webhook->data->id : '';

        if (! $type || ! $id) {
            Logger::addLog('[ShopRunBack] WEBHOOK FAILED: What is missing? [type: ' . $type . ' ; id: ' . $id . ']', 2, null, '', 0, true);
            return header('HTTP/1.1 200 OK');
        }

        $item;
        switch ($type) {
            case 'shipback':
                Logger::addLog('[ShopRunBack] WEBHOOK IS SHIPBACK', 0, null, $type, $id, true);
                $item = SRBReturn::getById($id);
                $state = isset($webhook->data->state) ? $webhook->data->state : '';
                $mode = isset($webhook->data->mode) ? $webhook->data->mode : '';

                if (! $state && ! $mode) {
                    Logger::addLog('[ShopRunBack] WEBHOOK SHIPBACK FAILED: What is missing? [state: ' . $state . '; mode: ' . $mode . ']', 2, null, $type, $id, true);
                    return header('HTTP/1.1 200 OK');
                }

                $item->state = $state ? $state : $this->state;
                $item->mode = $mode ? $mode : $this->mode;
                break;
            default:
                return header('HTTP/1.1 200 OK');
                break;
        }

        $item->save();

        Logger::addLog('[ShopRunBack] WEBHOOK WORKED', 0, null, $type, $id, true);
        return header('HTTP/1.1 200 OK');
    }
}
