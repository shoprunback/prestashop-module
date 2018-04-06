<?php

include_once 'SRBObject.php';
include_once 'SRBBrand.php';

use Shoprunback\Elements\Product as LibProduct;
use Shoprunback\Error\NotFoundError;
use Shoprunback\Error\RestClientError;

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

    public function createLibElementFromSRBObject()
    {
        $product = false;
        if ($mapId = SRBMap::getMappingIdIfExists($this->getDBId(), self::getObjectTypeForMapping())) {
            try {
                $product = LibProduct::retrieve($mapId);
                $this->addCoverPictureToProduct($product);
                return $product;
            } catch (NotFoundError $e) {

            }
        }

        try {
            $product = LibProduct::retrieve($this->getReference());
            $this->addCoverPictureToProduct($product);
            return $product;
        } catch (NotFoundError $e) {

        }

        $product = new LibProduct();
        $product->label = $this->label;
        $product->reference = $this->reference;
        $product->weight_grams = $this->weight_grams;
        $product->width_mm = $this->width_mm;
        $product->height_mm = $this->height_mm;
        $product->length_mm = $this->length_mm;
        $product->brand = $this->brand;
        $product->brand_id = $this->brand_id;

        $this->addCoverPictureToProduct($product);

        // TODO delete product image
        // SRBLogger::addLog('DELETING IMAGE OF ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        // $product->;

        return $product;
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

    private function addCoverPictureToProduct ($product)
    {
        $coverPicture = $this->getCoverPicture();

        if ($coverPicture) {
            $product->picture_file_url = 'ps-' . $this->label;
            $product->picture_file_base64 = 'data:image/png;base64,' . base64_encode($coverPicture);
        }

        return $product;
    }

    public function sync ($brandChecked = false)
    {
        SRBLogger::addLog('SYNCHRONIZING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        $product = $this->createLibElementFromSRBObject();

        try {
            $result = $product->save();
            $this->mapApiCall($product->id);
            return $result;
        } catch (RestClientError $e) {
            SRBLogger::addLog(json_encode($e), SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        }
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
        $product = Product::retrieve($this->id);
        return $product->remove();
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

    static public function findAllQuery ($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select(self::getTableName() . '.*, pl.*');
        $sql->from('product', self::getTableName());
        $sql->innerJoin('product_lang', 'pl', self::getTableName() . '.id_product = pl.id_product');
        $sql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }
}
