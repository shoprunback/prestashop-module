<?php

use Shoprunback\Elements\Item as LibItem;

class SRBItem extends LibItem
{
    use PSElementTrait;

    public function __construct($psProduct)
    {
        $this->price_cents = (int) $psProduct['price'] * 100;
        $this->currency = $psProduct['iso_code'];
        $this->product = new SRBProduct($psProduct);
        $this->label = $this->product->label;
        $this->reference = $this->product->getReference();
        $combinations = $this->product->getPreciseCombinationInOrder($psProduct['id_order'], $psProduct['id_product_attribute']);

        if ($combinations) {
            foreach ($combinations as $key => $combination) {
                $this->label .= ' - ' . $combination['attribute_name'];
            }
            $this->barcode = $combinations[0]['attribute_ean13'];
        } else {
            $this->barcode = $psProduct['ean13'];
        }
    }

    static public function createItemsFromOrderId($orderId)
    {
        $sql = self::findProductsForItems();
        $sql->where(pSQL(SRBOrder::getTableIdentifier()) . ' = "' . pSQL($orderId) . '"');
        return self::generateItemsWithProducts(Db::getInstance()->executeS($sql));
    }

    static public function createItemsFromOrderDetail($orderDetailId)
    {
        $sql = self::findProductsForItems();
        $sql->innerJoin('order_detail', 'od', 'od.id_order = ' . pSQL(SRBOrder::getTableName()) . '.id_order');
        $sql->where('od.id_order_detail = ' . pSQL($orderDetailId));
        return self::generateItemsWithProducts(Db::getInstance()->executeS($sql));
    }

    static private function findProductsForItems()
    {
        $sql = SRBProduct::getBaseQuery();
        SRBProduct::joinLang($sql);
        $sql->select(SRBProduct::getTableName() . '.*, pl.name, cu.iso_code, cp.quantity, ca.id_cart, o.id_order, cp.id_product_attribute');
        $sql->innerJoin('cart_product', 'cp', SRBProduct::getTableName() . '.id_product = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', SRBOrder::getTableName(), 'ca.id_cart = ' . SRBOrder::getTableName() . '.id_cart');
        $sql->innerJoin('currency', 'cu', 'cu.id_currency = ' . SRBOrder::getTableName() . '.id_currency');

        return $sql;
    }

    // To get the combination of the product in the cart
    static public function joinCombinationByCartProducts(&$sql)
    {
        $sql->leftJoin('product_attribute', 'pa', 'pa.id_product_attribute = cp.id_product_attribute');
        SRBProduct::joinCombinationByProductAttribute($sql);
    }

    static private function generateItemsWithProducts($products)
    {
        $items = array();
        foreach ($products as $product) {
            $item = new self($product);
            $quantity = $product['quantity'];
            for ($i = 1; $i <= $quantity; $i++) {
                $items[] = $item;
            }
        }

        return $items;
    }
}