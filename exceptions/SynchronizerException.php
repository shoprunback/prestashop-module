<?php

include_once 'SRBException.php';

class SynchronizerException extends SRBException
{
    public function __construct($message, $errorCode = 0, Exception $previous = null) {
        parent::__construct($message, $errorCode, $previous);
    }

    public function __toString() {
        SRBLogger::addLog($this->message, 'synchronizer');

        return $this->prefix . ' ' . __CLASS__ . ': [' . $this->errorCode . ']: ' . $this->message . '\n';
    }
}
