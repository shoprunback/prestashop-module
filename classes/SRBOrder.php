<?php

include_once 'Synchronizer.php';

class SRBOrder extends Synchronizer {
    public $ordered_at;
    public $customer;
    public $order_number;
    public $items;

    public function __construct ($psOrder) {
        $identifier = 'id';

        if (isset($psOrder->id_order)) {
            $identifier = 'id_order';
        }

        $this->order_number = $psOrder->{$identifier};
        $this->ordered_at = $psOrder->date_add;
        $this->customer = $this->formalizeCustomerForAPI($psOrder);

        $sql = new DbQuery();
        $sql->select('p.id_product, p.price, p.id_manufacturer, p.weight, p.width, p.height, p.depth, pl.name, cu.iso_code');
        $sql->from('orders', 'o');
        $sql->innerJoin('currency', 'cu', 'cu.id_currency = o.id_currency');
        $sql->innerJoin('cart', 'ca', 'o.id_cart = ca.id_cart');
        $sql->innerJoin('cart_product', 'cp', 'ca.id_cart = cp.id_cart');
        $sql->innerJoin('product', 'p', 'p.id_product = cp.id_product');
        $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product');
        $sql->where('o.id_order = ' . $psOrder->order_number);
        $sql->where('p.id_manufacturer > 0');
        $products = Db::getInstance()->executeS($sql);

        $items = [];
        foreach ($products as $product) {
            $productObject = $this->arrayToObject($product);

            $item = new stdClass();
            $item->label = 'string';
            $item->reference = 'string';
            $item->price_cents = $productObject->price;
            $item->currency = $productObject->iso_code;
            $item->product = new Product($productObject);

            $items[] = $item;
        }

        $this->items = $items;
    }
}
