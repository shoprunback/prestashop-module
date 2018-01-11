<?php

include_once 'SRBException.php';

class ProductException extends SRBException
{
    public function __construct($message, $errorCode = 0, Exception $previous = null)
    {
        parent::__construct($message, $errorCode, $previous);
    }

    public function __toString()
    {
        SRBLogger::addLog($this->message, 'product');

        return $this->prefix . ' ' . __CLASS__ . ': [' . $this->errorCode . ']: ' . $this->message . '\n';
    }
}
