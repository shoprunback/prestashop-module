<?php

include_once 'SRBObject.php';
include_once 'SRBBrand.php';

class SRBProduct extends SRBObject {
    public $id;
    public $label;
    public $reference;
    public $weight_grams;
    public $width_mm;
    public $height_mm;
    public $length_mm;
    public $brand_id;
    public $brand;

    public function __construct ($psProduct) {
        $manufacturer = new Manufacturer((int)$psProduct['id_manufacturer'], (int)Configuration::get('PS_LANG_DEFAULT'));

        // if (! $manufacturer) {
        //     raise exception
        // }

        $this->label = $this->extractName($psProduct['name']);
        $this->reference = $this->extractReference($psProduct);
        $this->weight_grams = $psProduct['weight'] * 1000;
        $this->width_mm = $psProduct['width'];
        $this->height_mm = $psProduct['height'];
        $this->length_mm = $psProduct['depth'];
        $this->brand = new SRBBrand($manufacturer);
        $this->brand_id = $this->brand->reference;
    }

    static public function getTableName () {
        return 'p';
    }

    static public function getIdColumnName () {
        return 'id_product';
    }

    // SQL object extractors

    static private function extractName ($psProductArrayName) {
        return is_array($psProductArrayName) ? $psProductArrayName[1] : $psProductArrayName;
    }

    static private function extractReference ($psProductArrayName) {
        return isset($psProductArrayName['id_product']) ? $psProductArrayName['id_product'] : $psProductArrayName['id'];
    }

    static public function getById ($id) {
        return new SRBProduct(Db::getInstance()->executeS(self::findOneQuery($id))[0]);
    }

    static public function getAll () {
        $productsFromDB = Db::getInstance()->executeS(self::findAllQuery());

        $products = [];
        foreach ($productsFromDB as $productFromDB) {
            $products[] = self::__construct($productFromDB);
        }

        return $products;
    }

    // private (class) methods

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select('p.*, pl.*');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'pl.id_product=p.id_product');

        return $sql;
    }
}
