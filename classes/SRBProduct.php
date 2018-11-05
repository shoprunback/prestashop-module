<?php

use Shoprunback\Elements\Product as LibProduct;
use Shoprunback\Elements\Brand as LibBrand;

class SRBProduct extends LibProduct implements PSElementInterface
{
    use PSElementTrait {
        getBaseQuery as protected trait_getBaseQuery;
        sync as protected trait_sync;
    }

    public function __construct($psProduct)
    {
        $this->ps           = $psProduct;
        $this->label        = $this->extractNameFromPSArray($psProduct['name']);
        $this->reference    = $psProduct['id_product'];
        $this->weight_grams = (int) $psProduct['weight'] * 1000;
        $this->width_mm     = (int) $psProduct['width'] * 10;
        $this->height_mm    = (int) $psProduct['height'] * 10;
        $this->length_mm    = (int) $psProduct['depth'] * 10;
        $this->ean          = $psProduct['ean13'];

        $this->addCoverPicture();

        if ($psProduct['id_manufacturer'] != 0) {
            $this->brand = SRBBrand::getNotSyncById($psProduct['id_manufacturer']);
            $this->brand_id = $this->brand->reference;
        }

        if ($srbId = $this->getMapId()) {
            parent::__construct($srbId);
        } else {
            parent::__construct();
        }

        $this->metadata = array(
            'ps_reference' => $psProduct['reference'],
        );
    }

    // Inherited functions
    public static function getTableWithoutPrefix()
    {
        return 'product';
    }

    public static function getTableName()
    {
        return 'p';
    }

    public static function getIdColumnName()
    {
        return 'id_product';
    }

    public static function getIdentifier()
    {
        return 'reference';
    }

    public static function getPreIdentifier()
    {
        return 'reference';
    }

    public static function getDisplayNameAttribute()
    {
        return 'label';
    }

    public static function getObjectTypeForMapping()
    {
        return 'product';
    }

    public static function getPathForAPICall()
    {
        return 'products';
    }

    public function generateIdentifier()
    {
        return str_replace(' ', '-', ($this->ps[self::getPreIdentifier()] != '' ? $this->ps[self::getPreIdentifier()] : $this->label));
    }

    public static function getBaseQuery()
    {
        $sql = self::trait_getBaseQuery();

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $sql->where(self::getTableName() . '.state = 1'); // state=0 if the product is temporary
        }

        return $sql;
    }

    public static function findAllQuery($limit = 0, $offset = 0)
    {
        $sql = static::getBaseQuery();
        $sql->select(self::getTableName() . '.*, pl.*');
        static::joinLang($sql);
        $sql = self::addLimitToQuery($sql, $limit, $offset);

        return $sql;
    }

    // Own functions
    private static function extractNameFromPSArray($psProductArrayName)
    {
        return is_array($psProductArrayName) ? $psProductArrayName[1] : $psProductArrayName;
    }

    public static function getOrderProducts($orderId)
    {
        return self::convertPSArrayToElements(Db::getInstance()->executeS(self::findOrderProductsQuery(pSQL($orderId))));
    }

    public function getCoverPicture()
    {
        $productCover = Product::getCover($this->getDBId());
        $image = new Image($productCover['id_image']);
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . _THEME_PROD_DIR_ . $image->getExistingImgPath() . '.jpg';

        if (file_exists($imagePath)) {
            return array(_PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . '.jpg', file_get_contents($imagePath));
        }

        return false;
    }

    private function addCoverPicture()
    {
        list($imageUrl, $coverPicture) = $this->getCoverPicture();

        if ($imageUrl) {
            $this->picture_file_url = $imageUrl;
        } elseif ($coverPicture) {
            $this->picture_file_base64 = 'data:image/png;base64,' . base64_encode($coverPicture);
        }
    }

    // Check if product has NEVER been ordered
    public static function canBeDeleted($dbId)
    {
        $sql = SRBOrder::getBaseQuery();
        $sql->innerJoin('cart', 'ca', 'ca.id_cart = ' . SRBOrder::getTableName() . '.id_cart');
        $sql->innerJoin('cart_product', 'cp', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin(self::getTableWithoutPrefix(), self::getTableName(), self::getTableIdentifier() . ' = cp.' . self::getIdColumnName());
        $sql->where(self::getTableIdentifier() . ' = ' . $dbId);

        return (SRBOrder::getCountOfQuery($sql) == 0);
    }

    public function deleteWithCheck()
    {
        if (!static::canBeDeleted($this->getDBId())) {
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

    public function syncDelete()
    {
        SRBLogger::addLog('DELETING ' . self::getObjectTypeForMapping() . ' "' . $this->getReference() . '"', SRBLogger::INFO, self::getObjectTypeForMapping(), $this->getDBId());
        $product = Product::retrieve($this->id);
        return $product->remove();
    }

    public static function joinLang(&$sql)
    {
        $sql->innerJoin('product_lang', 'pl', self::getTableName() . '.id_product = pl.id_product');
        $sql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
    }

    protected static function findOrderProductsQuery($orderId)
    {
        $sql = static::getBaseQuery();
        $sql->select(self::getTableName() . '.*, pl.name, cu.iso_code');
        static::joinLang($sql);
        $sql->innerJoin('cart_product', 'cp', self::getTableName() . '.id_product = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', SRBOrder::getTableName(), 'ca.id_cart = ' . SRBOrder::getTableName() . '.id_cart');
        $sql->innerJoin('currency', 'cu', 'cu.id_currency = ' . SRBOrder::getTableName() . '.id_currency');
        $sql->where(SRBOrder::getTableName() . '.id_order = ' . pSQL($orderId));

        return $sql;
    }

    public static function findAllByNameQuery($name)
    {
        $sql = static::findAllQuery();
        $sql->where('pl.name = "' . pSQL($name) . '"');
        return $sql;
    }

    public static function getManyByName($name)
    {
        return self::convertPSArrayToElements(Db::getInstance()->executeS(self::findAllByNameQuery($name)));
    }

    public static function getCountLikeLabel($label)
    {
        $sql = static::getBaseQuery();
        static::joinLang($sql);
        static::addWhereLikeLabelToQuery($sql, $label);
        return self::getCountOfQuery($sql);
    }

    public static function getLikeLabel($label, $onlySyncElements = false, $limit = 0, $offset = 0, $withNestedElements = true)
    {
        return self::convertPSArrayToElements(Db::getInstance()->executeS(self::findLikeLabelQuery($label, $limit, $offset, $onlySyncElements)), $withNestedElements);
    }

    public static function addWhereLikeLabelToQuery(&$sql, $label)
    {
        $sql->where('pl.name LIKE "%' . pSQL($label) . '%"');
    }

    public static function findLikeLabelQuery($label, $limit = 0, $offset = 0, $onlySyncElements = false)
    {
        $sql = self::findAllByMappingDateQuery($onlySyncElements, $limit, $offset);
        static::addWhereLikeLabelToQuery($sql, $label);
        return $sql;
    }

    // To get all the combinations possible of the product
    public static function joinCombinationByProduct(&$sql)
    {
        $sql->leftJoin('product_attribute', 'pa', 'pa.' . self::getIdColumnName() . ' = ' . self::getTableIdentifier());
        static::joinCombinationByProductAttribute($sql);
    }

    public static function joinCombinationByProductAttribute(&$sql)
    {
        $sql->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
        $sql->leftJoin(
            'attribute_lang',
            'al',
            'pac.id_attribute = al.id_attribute AND
            al.id_lang = ' . Configuration::get('PS_LANG_DEFAULT')
        );
    }

    public function sync()
    {
        if (!isset($this->picture_file_url) && !isset($this->picture_file_base64)) {
            try {
                $this->deleteImage();
            } catch (Exception $e) {
                SRBLogger::addLog('Error when deleting product\'s image: ' . json_encode($e), SRBLogger::FATAL, SRBProduct::getObjectTypeForMapping(), $this->getDBId());
            }
        }

        return $this->trait_sync();
    }

    public function getAttributes()
    {
        return self::getAttributesOfProduct($this->getDBId());
    }

    // Returns all attributes the product can have
    public static function getAttributesOfProduct($productId)
    {
        return Db::getInstance()->executeS(self::findAttributesOfProductQuery($productId));
    }

    public static function findAttributesOfProductQuery($productId)
    {
        $sql = self::getBaseQuery();
        self::joinLang($sql);
        self::joinCombinationByProduct($sql);
        $sql->select('al.name as attribute_name, pa.ean13 as attribute_ean13, pac.id_product_attribute');
        $sql->where(pSQL(self::getTableIdentifier()) . ' = ' . pSQL($productId));

        return $sql;
    }

    public function getPreciseCombinationInOrder($orderId, $productAttributeId)
    {
        return self::getPreciseCombinationOfProductInOrder($this->getDBId(), $orderId, $productAttributeId);
    }

    // Get all the attributes of the product commanded in the order in a precise combination
    public static function getPreciseCombinationOfProductInOrder($productId, $orderId, $productAttributeId)
    {
        return Db::getInstance()->executeS(self::findPreciseCombinationOfProductInOrderQuery($productId, $orderId, $productAttributeId));
    }

    public static function findPreciseCombinationOfProductInOrderQuery($productId, $orderId, $productAttributeId)
    {
        $sql = self::findAttributesOfProductQuery($productId);
        $sql->innerJoin('cart_product', 'cp', pSQL(self::getTableIdentifier()) . ' = cp.' . pSQL(self::getIdColumnName()));
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', pSQL(SRBOrder::getTableName()), 'ca.id_cart = ' . pSQL(SRBOrder::getTableName()) . '.id_cart');
        $sql->where(pSQL(SRBOrder::getTableIdentifier()) . ' = ' . pSQL($orderId));
        $sql->where('cp.id_product_attribute = pa.id_product_attribute');
        $sql->where('cp.id_product_attribute = ' . pSQL($productAttributeId));

        return $sql;
    }
}
