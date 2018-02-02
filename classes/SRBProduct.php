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

    public function __construct ($psProduct)
    {
        $this->ps = $psProduct;
        $this->label = $this->extractNameFromPSArray($psProduct['name']);
        $reference = $psProduct['reference'] != '' ? $psProduct['reference'] : $this->label;
        $this->reference = str_replace(' ', '-', $reference);
        $this->weight_grams = intval($psProduct['weight'] * 1000);
        $this->width_mm = intval($psProduct['width'] * 10);
        $this->height_mm = intval($psProduct['height'] * 10);
        $this->length_mm = intval($psProduct['depth'] * 10);

        if ($psProduct['id_manufacturer'] != 0) {
            $this->brand = SRBBrand::getById($psProduct['id_manufacturer']);
            $this->brand_id = $this->brand->reference;
        }

        $this->attributesToSend = ['label', 'reference', 'weight_grams', 'width_mm', 'height_mm', 'length_mm', 'brand', 'brand_id', 'picture_file_url', 'picture_file_base64'];
    }

    static public function getObjectTypeForMapping ()
    {
        return 'product';
    }

    static public function getPathForAPICall ()
    {
        return 'products';
    }

    static public function getIdentifier ()
    {
        return 'reference';
    }

    static public function getDisplayNameAttribute ()
    {
        return 'label';
    }

    static public function getTableName ()
    {
        return 'p';
    }

    static public function getIdColumnName ()
    {
        return 'id_product';
    }

    static public function getOrderProducts ($orderId)
    {
        return self::convertPSArrayToSRBObjects(Db::getInstance()->executeS(self::findOrderProductsQuery($orderId)));
    }

    static private function extractNameFromPSArray ($psProductArrayName)
    {
        return is_array($psProductArrayName) ? $psProductArrayName[1] : $psProductArrayName;
    }

    static public function syncAll ($newOnly = false)
    {
        $products = $newOnly ? self::getAllNotSync() : self::getAll();

        $responses = [
            'brand' => [],
            'product' => []
        ];
        $brands = [];
        foreach ($products as $product) {
            if (isset($product->brand_id) && ! isset($brands[$product->brand_id])) {
                $brands[$product->brand_id] = $product->brand;
            }
        }

        foreach ($brands as $brand) {
            $responses['brand'][] = $brand->sync();
        }

        foreach ($products as $product) {
            try {
                $responses['product'][] = $product->sync(true);
            } catch (ProductException $e) {

            }
        }

        return $responses;
    }

    public function getCoverPicture ()
    {
        $productCover = Product::getCover($this->getDBId());
        $image = new Image($productCover['id_image']);
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . _THEME_PROD_DIR_ . $image->getExistingImgPath() . '.jpg';

        if (file_exists($imagePath)) {
            return file_get_contents($imagePath);
        }

        return false;
    }

    private function addCoverPictureToSync ()
    {
        $coverPicture = $this->getCoverPicture();

        if ($coverPicture) {
            $this->picture_file_url = 'ps-' . $this->label;
            $this->picture_file_base64 = 'data:image/png;base64,' . base64_encode($coverPicture);
        } else {
            $this->syncDeleteProductImage();
        }

        return $coverPicture;
    }

    public function sync ($brandChecked = false)
    {
        if (! isset($this->brand)) {
            SRBLogger::addLog('The product "' . $this->getReference() . '" has no brand attached!', SRBLogger::WARNING);
        } elseif (! $brandChecked) {
            $postBrandResult = $this->brand->sync();
        }

        $this->addCoverPictureToSync();

        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());

        if (isset($this->brand_id) && $this->brand_id != '') {
            $brand = $this->brand;
            unset($this->brand);
        }

        $result = Synchronizer::sync($this);

        if (isset($this->brand_id) && $this->brand_id != '') {
            $this->brand = $brand;
        }

        return $result;
    }

    public function deleteWithCheck ()
    {
        if (! $this->canBeDeleted()) {
            SRBLogger::addLog('Product "' . $this->getReference() . '" couldn\'t be deleted because it has already been ordered', SRBLogger::WARNING, self::getObjectTypeForMapping(), $this->getDBId());
            return false;
        }

        if ($this->syncDelete() != '') {
            SRBLogger::addLog('An error occured, product "' . $this->getReference() . '" couldn\'t be deleted', SRBLogger::FATAL, self::getObjectTypeForMapping(), $this->getDBId());
            return false;
        }

        SRBLogger::addLog('Product "' . $this->getReference() . '" deleted', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        return true;
    }

    // Check if product has NEVER been ordered
    public function canBeDeleted ()
    {
        $sql = new DbQuery();
        $sql->select('COUNT(' . SRBOrder::getTableName() . '.id_order)');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('cart_product', 'cp', self::getTableName() . '.' . self::getIdColumnName() . ' = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', SRBOrder::getTableName(), 'ca.id_cart = ' . SRBOrder::getTableName() . '.id_cart');
        $sql->where(self::getTableName() . '.' . self::getIdColumnName() . ' = ' . $this->getDBId());

        return (Db::getInstance()->getValue($sql) == 0);
    }

    public function syncDelete ()
    {
        SRBLogger::addLog('DELETING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        return Synchronizer::delete($this);
    }

    static protected function findOrderProductsQuery ($orderId)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, pl.name, cu.iso_code');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('product_lang', 'pl', self::getTableName() . '.id_product = pl.id_product');
        $sql->innerJoin('cart_product', 'cp', self::getTableName() . '.id_product = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', SRBOrder::getTableName(), 'ca.id_cart = ' . SRBOrder::getTableName() . '.id_cart');
        $sql->innerJoin('currency', 'cu', 'cu.id_currency = ' . SRBOrder::getTableName() . '.id_currency');
        $sql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
        $sql->where(SRBOrder::getTableName() . '.id_order = ' . pSQL($orderId));

        return $sql;
    }

    static protected function findAllQuery ($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, pl.*');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('product_lang', 'pl', self::getTableName() . '.id_product = pl.id_product');
        $sql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    public function syncDeleteProductImage ()
    {
        SRBLogger::addLog('DELETING IMAGE OF ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        return Synchronizer::APIcall(self::getPathForAPICall() . '/' . $this->getReference() . '/image', 'DELETE');
    }
}
