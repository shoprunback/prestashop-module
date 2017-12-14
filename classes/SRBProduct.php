<?php

include_once 'SRBObject.php';
include_once 'SRBBrand.php';

class SRBProduct extends SRBObject
{
    public $label;
    public $reference;
    public $weight_grams;
    public $width_mm;
    public $height_mm;
    public $length_mm;
    public $brand_id;
    public $brand;

    public function __construct ($psProduct) {
        $this->ps = $psProduct;
        $this->label = $this->extractName($psProduct['name']);
        $this->reference = $this->extractReference($psProduct);
        $this->weight_grams = $psProduct['weight'] * 1000;
        $this->width_mm = $psProduct['width'];
        $this->height_mm = $psProduct['height'];
        $this->length_mm = $psProduct['depth'];
        $this->brand = SRBBrand::getById($psProduct['id_manufacturer']);
        $this->brand_id = $this->brand->reference;
    }

    static public function getSRBApiCallType () {
        return 'product';
    }

    static public function getIdentifier () {
        return 'reference';
    }

    static public function getDisplayNameAttribute () {
        return 'label';
    }

    static public function getTableName () {
        return 'p';
    }

    static public function getIdColumnName () {
        return 'id_product';
    }

    static public function getOrderProducts ($orderId) {
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS(self::findOrderProductsQuery($orderId)));
    }

    // SQL object extractors

    static private function extractName ($psProductArrayName) {
        return is_array($psProductArrayName) ? $psProductArrayName[1] : $psProductArrayName;
    }

    static private function extractReference ($psProductArrayName) {
        return isset($psProductArrayName['id_product']) ? $psProductArrayName['id_product'] : $psProductArrayName['id'];
    }

    static public function syncAll ($newOnly = false) {
        $products = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [
            'brand' => [],
            'product' => []
        ];
        $brands = [];
        foreach ($products as $product) {
            if (! isset($brands[$product->brand_id])) {
                $brands[$product->brand_id] = $product->brand;
            }
        }

        foreach ($brands as $brand) {
            $responses['brand'][] = $brand->sync();
        }

        foreach ($products as $product) {
            $responses['product'][] = $product->sync(true);
        }

        return $responses;
    }

    public function sync ($brandChecked = false) {
        if (! $brandChecked) {
            $postBrandResult = $this->brand->sync();
        }

        $productCover = Product::getCover($this->ps['id_product']);
        $image = new Image($productCover['id_image']);
        $imagePath = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
        $fileContent = file_get_contents($imagePath);

        $this->picture_file_url = 'string';
        $this->picture_file_base64 = 'data:image/png;base64,' . base64_encode($fileContent);

        return Synchronizer::sync($this, self::getSRBApiCallType());
    }

    // private (class) methods

    static protected function findOrderProductsQuery ($orderId) {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, pl.name, cu.iso_code');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('product_lang', 'pl', self::getTableName() . '.id_product = pl.id_product');
        $sql->innerJoin('cart_product', 'cp', self::getTableName() . '.id_product = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', 'o', 'ca.id_cart = o.id_cart');
        $sql->innerJoin('currency', 'cu', 'cu.id_currency = o.id_currency');
        $sql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
        $sql->where('o.id_order = ' . $orderId);

        return $sql;
    }

    static protected function findAllQuery () {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, pl.*');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('product_lang', 'pl', self::getTableName() . '.id_product = pl.id_product');
        $sql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));

        return $sql;
    }
}
