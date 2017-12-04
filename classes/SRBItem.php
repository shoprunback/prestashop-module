<?php

include_once 'SRBProduct.php';

class SRBItem
{
    public $label;
    public $reference;
    public $price_cents;
    public $currency;
    public $product;

    public function __construct ($psProduct) {
        $this->label = 'string';
        $this->reference = 'string';
        $this->price_cents = $psProduct['price'];
        $this->currency = $psProduct['iso_code'];
        $this->product = new SRBProduct($psProduct);
    }

    // private (class) methods

    static public function createItemsFromOrder ($orderId) {
        $sql = new DbQuery();
        $sql->select('p.*, pl.name, cu.iso_code');
        $sql->from('product', 'p');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product');
        $sql->innerJoin('cart_product', 'cp', 'p.id_product = cp.id_product');
        $sql->innerJoin('cart', 'ca', 'cp.id_cart = ca.id_cart');
        $sql->innerJoin('orders', 'o', 'ca.id_cart = o.id_cart');
        $sql->innerJoin('currency', 'cu', 'cu.id_currency = o.id_currency');
        $sql->where('o.id_order = ' . $orderId);
        $productsFromDB = Db::getInstance()->executeS($sql);

        $items = [];
        foreach ($productsFromDB as $productFromDB) {
            $items[] = new self($productFromDB);
        }

        return $items;
    }
}
