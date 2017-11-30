<?php

class Formalizer {
    public function arrayToObject ($array) {
        $object = new stdClass();

        foreach ($array as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    // public function formalizeBrandForAPI ($manufacturer) {
    //     $identifier = 'id';
    //     if (isset($manufacturer->id_manufacturer)) {
    //         $identifier = 'id_manufacturer';
    //     } elseif (isset($manufacturer->reference)) {
    //         $identifier = 'reference';
    //     }

    //     $manufacturerFormalized = new stdClass();
    //     $manufacturerFormalized->name = $manufacturer->name;
    //     $manufacturerFormalized->reference = $manufacturer->{$identifier};

    //     return $manufacturerFormalized;
    // }

    // public function formalizeProductForAPI ($product, $languageId = 1) {
    //     var_dump($product);echo '<br><br><br>';
    //     $manufacturer = new Manufacturer((int)$product->id_manufacturer, $languageId);

    //     if (! $manufacturer) {
    //         return 'Your product needs to have a brand to be sent to ShopRunBack';
    //     }

    //     $manufacturerObject = $this->arrayToObject($manufacturer);
    //     $manufacturerFormalized = $this->formalizeBrandForAPI($manufacturerObject);

    //     $productFormalized = new stdClass();
    //     if (isset($product->name) && is_array($product->name)) {
    //         $productFormalized->label = $product->name[1];
    //     } else {
    //         $productFormalized->label = $product->name;
    //     }
    //     if (isset($product->id_product)) {
    //         $productFormalized->reference = $product->id_product;
    //     } else {
    //         $productFormalized->reference = $product->id;
    //     }
    //     $productFormalized->weight_grams = $product->weight*1000;
    //     $productFormalized->width_mm = $product->width;
    //     $productFormalized->height_mm = $product->height;
    //     $productFormalized->length_mm = $product->depth;
    //     $productFormalized->brand_id = $manufacturerFormalized->reference;
    //     $productFormalized->brand = $manufacturerFormalized;

    //     return $productFormalized;
    // }

    // public function formalizeCustomerForAPI ($customer) {
    //     $address = new stdClass();
    //     $address->line1 = $customer->address1;
    //     $address->line2 = $customer->address2;
    //     $address->zipcode = $customer->postcode;
    //     $address->country_code = $customer->iso_code;
    //     $address->city = $customer->city;
    //     $address->state = $customer->name;

    //     $customerFormalized = new stdClass();
    //     $customerFormalized->first_name = $customer->firstname;
    //     $customerFormalized->last_name = $customer->lastname;
    //     $customerFormalized->email = $customer->email;
    //     $customerFormalized->phone = $customer->phone;
    //     $customerFormalized->address = $address;

    //     return $customerFormalized;
    // }

    // public function formalizeOrderForAPI ($order) {
    //     $orderFormalized = new stdClass();
    //     $orderFormalized->ordered_at = $order->date_add;
    //     $orderFormalized->customer = $this->formalizeCustomerForAPI($order);

    //     if (isset($order->id_order)) {
    //         $orderFormalized->order_number = $order->id_order;
    //     } else {
    //         $orderFormalized->order_number = $order->id;
    //     }

    //     $sql = new DbQuery();
    //     $sql->select('p.id_product, p.price, p.id_manufacturer, p.weight, p.width, p.height, p.depth, pl.name, cu.iso_code');
    //     $sql->from('orders', 'o');
    //     $sql->innerJoin('currency', 'cu', 'cu.id_currency = o.id_currency');
    //     $sql->innerJoin('cart', 'ca', 'o.id_cart = ca.id_cart');
    //     $sql->innerJoin('cart_product', 'cp', 'ca.id_cart = cp.id_cart');
    //     $sql->innerJoin('product', 'p', 'p.id_product = cp.id_product');
    //     $sql->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product');
    //     $sql->where('o.id_order = ' . $orderFormalized->order_number);
    //     $sql->where('p.id_manufacturer > 0');
    //     $products = Db::getInstance()->executeS($sql);

    //     $items = [];
    //     foreach ($products as $product) {
    //         $productObject = $this->arrayToObject($product);

    //         $item = new stdClass();
    //         $item->label = 'string';
    //         $item->reference = 'string';
    //         $item->price_cents = $productObject->price;
    //         $item->currency = $productObject->iso_code;
    //         $item->product_id = $productObject->id_product;
    //         $item->product = $this->formalizeProductForAPI($productObject);

    //         $items[] = $item;
    //     }

    //     if ($items === []) {
    //         return 'No item in order';
    //     }

    //     $orderFormalized->items = $items;

    //     return $orderFormalized;
    // }
}
