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
        $this->label = $this->extractNameFromPSArray($psProduct['name']);
        $this->reference = $psProduct['reference'];
        $this->weight_grams = $psProduct['weight'] * 1000;
        $this->width_mm = $psProduct['width'];
        $this->height_mm = $psProduct['height'];
        $this->length_mm = $psProduct['depth'];
        $this->brand = SRBBrand::getById($psProduct['id_manufacturer']);
        $this->brand_id = $this->brand->reference;
    }

    static public function getObjectTypeForMapping () {
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

    static private function extractNameFromPSArray ($psProductArrayName) {
        return is_array($psProductArrayName) ? $psProductArrayName[1] : $psProductArrayName;
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

    public function getCoverPicture () {
        $productCover = Product::getCoverPicture($this->ps['id_product']);
        $image = new Image($productCover['id_image']);
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . _THEME_PROD_DIR_ . $image->getExistingImgPath() . '.jpg';

        if (file_exists($imagePath)) {
            return file_get_contents($imagePath);
        }

        return false;
    }

    private function addCoverPictureToSync () {
        $coverPicture = $this->getCoverPicture();

        if ($coverPicture) {
            $this->picture_file_url = 'ps-' . $this->label;
            $this->picture_file_base64 = 'data:image/png;base64,' . base64_encode($coverPicture);
        }

        return $coverPicture;
    }

    public function sync ($brandChecked = false) {
        if (! $brandChecked) {
            $postBrandResult = $this->brand->sync();
        }

        $this->addCoverPictureToSync();

        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->{self::getIdentifier()} . '"', 0, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
        return Synchronizer::sync($this, self::getObjectTypeForMapping());
    }

    public function deleteWithCheck () {
        if ($this->canBeDeleted()) {
            if ($this->syncDelete()) {
                SRBLogger::addLog('Product "' . $this->{self::getIdentifier()} . '" deleted', 0, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
                return true;
            } else {
                SRBLogger::addLog('An error occured, product "' . $this->{self::getIdentifier()} . '" couldn\'t be deleted', 3, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
                return false;
            }
        }

        SRBLogger::addLog('Product "' . $this->{self::getIdentifier()} . '" couldn\'t be deleted because it has already been ordered', 1, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
        return false;
    }

    // Check if product has NEVER been ordered
    public function canBeDeleted () {
        $sql = new DbQuery();
        $sql->select('COUNT(o.id_order)');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('cart_product', 'cp', self::getTableName() . '.id_product = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', 'o', 'ca.id_cart = o.id_cart');
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . $this->ps[self::getIdColumnName()]);

        $result = Db::getInstance()->getValue($sql);

        return ($result == 0);
    }

    public function syncDelete () {
        SRBLogger::addLog('DELETING ' . self::getObjectTypeForMapping() . ' "' . $this->{self::getIdentifier()} . '"', 0, null, self::getObjectTypeForMapping(), $this->ps[self::getIdColumnName()]);
        return Synchronizer::delete($this, self::getObjectTypeForMapping());
    }

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
        $sql->where('o.id_order = ' . pSQL($orderId));

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
