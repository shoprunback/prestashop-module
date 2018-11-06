<?php
/**
 * 2007-2018 ShopRunBack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ShopRunBack
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the ShopRunBack module to newer
 * versions in the future.
 *
 * @author ShopRunBack <contact@shoprunback.com>
 * @copyright 2007-2018 ShopRunBack
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of ShopRunBack
 **/

if (! defined('_PS_VERSION_')) {
    die('No direct script access');
}

class ShopRunBackWebhookModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        echo $this->executeWebhook();
        exit;
    }

    private function executeWebhook()
    {
        SRBLogger::addLog('WEBHOOK CALLED');

        $webhook = file_get_contents("php://input");
        $webhook = json_decode($webhook);

        if (!isset($webhook->event)) {
            SRBLogger::addLog('WEBHOOK FAILED: TYPE IS MISSING!', SRBLogger::FATAL);
            return self::returnHeaderHTTP(200);
        }

        $event = explode('.', $webhook->event);

        $type = $event[0];
        $id = isset($webhook->data->id) ? $webhook->data->id : '';

        if (!$id) {
            SRBLogger::addLog('WEBHOOK FAILED: ID IS MISSING! type: ' . $type, SRBLogger::FATAL);
            return self::returnHeaderHTTP(200);
        }

        if ($type == 'shipback') {
            SRBLogger::addLog('WEBHOOK IS SHIPBACK. id: ' . $id, SRBLogger::INFO, $type);
            try {
                $shipback = SRBShipback::getById($id);
                $mode = isset($webhook->data->mode) ? $webhook->data->mode : '';
                $state = $event[1];

                if (!$mode) {
                    SRBLogger::addLog('WEBHOOK SHIPBACK FAILED: MODE IS MISSING! state: ' . $state, SRBLogger::FATAL, $type);
                    return self::returnHeaderHTTP(200);
                }

                $shipback->state = $state;
                $shipback->mode = $mode;
                $shipback->updateOnPS();
            } catch (ShipbackException $e) {
                SRBLogger::addLog('WEBHOOK SHIPBACK FAILED: ' . $e, SRBLogger::FATAL, $type);
                return self::returnHeaderHTTP(200);
            }
        } elseif ($type == 'product') {
            // We check if the data is correct between PS and SRB
            try {
                $product = SRBProduct::getByMapper($id);
            } catch (Exception $e) {
                SRBLogger::addLog('Can\'t find product ' . $id . ' in mapping table. Resynchronizing product', SRBLogger::ERROR, $type);

                $product = SRBProduct::getNotSyncById($webhook->data->reference);
                $product->sync();

                return self::returnHeaderHTTP(200);
            }

            $errors = array();

            if ($webhook->data->reference != $product->getDBId()) {
                $errors['reference'] = $webhook->data->reference . ' != ' . $id;
            }

            if ($webhook->data->label != $product->label) {
                $errors['label'] = $webhook->data->label . ' != ' . $product->label;
            }

            if ($webhook->data->ean != $product->ean) {
                $errors['ean'] = $webhook->data->ean . ' != ' . $product->ean;
            }

            if (count($errors) > 0) {
                $errorsString = '';
                foreach ($errors as $key => $error) {
                    $errorsString .= $key . ': ' . $error . ', ';
                }
                $errorsString = trim($errorsString, ', ');

                SRBLogger::addLog('PRODUCT DOESN\'T HAVE CORRECT INFORMATIONS: ' . $errorsString, SRBLogger::ERROR, $type);
                return self::returnHeaderHTTP(200);
            }
        } else {
            SRBLogger::addLog('WEBHOOK TYPE UNKNOWN: ' . $type, SRBLogger::ERROR, $type);
            return self::returnHeaderHTTP(200);
        }

        SRBLogger::addLog('WEBHOOK WORKED', SRBLogger::INFO, $type);
        return self::returnHeaderHTTP(200);
    }

    private static function returnHeaderHTTP($httpCode)
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
