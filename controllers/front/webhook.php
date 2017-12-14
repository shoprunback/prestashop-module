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
        $webhook = file_get_contents("php://input");
        $webhook = json_decode($webhook);

        $type = isset($webhook->event) ? explode('.', $webhook->event)[0] : '';
        $id = isset($webhook->data->id) ? $webhook->data->id : '';

        if (! $type || ! $id) {
            return header('HTTP/1.1 200 OK');
        }

        $item;
        switch ($type) {
            case 'shipback':
                $item = SRBReturn::getById($id);
                $state = isset($webhook->data->state) ? $webhook->data->state : '';
                $mode = isset($webhook->data->mode) ? $webhook->data->mode : '';

                if (! $state && ! $mode) {
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

        return header('HTTP/1.1 200 OK');
    }
}
