<?php

include_once 'Synchronizer.php';

class SRBBrand extends Synchronizer {
    public $id;
    public $name;
    public $reference;

    public function __construct ($manufacturer) {
        $identifier = 'id';
        if (isset($manufacturer->id_manufacturer)) {
            $identifier = 'id_manufacturer';
        } elseif (isset($manufacturer->reference)) {
            $identifier = 'reference';
        }

        $this->name = $manufacturer->name;
        $this->reference = $manufacturer->{$identifier};
    }
}
