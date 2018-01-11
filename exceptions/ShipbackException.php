<?php

include_once 'SRBException.php';

class ShipbackException extends SRBException
{
    public function __construct($message, $errorCode = 0, Exception $previous = null) {
        parent::__construct($message, $errorCode, $previous);
    }

    public function __toString() {
        SRBLogger::addLog($this->message, 1, $this->errorCode, 'shipback');

        return $this->prefix . ' ' . __CLASS__ . ': [' . $this->errorCode . ']: ' . $this->message . '\n';
    }
}
