<?php
class AdminShoprunbackController extends ModuleAdminController
{
    public $token;

    public function __construct() {
        parent::__construct();
        $this->bootstrap = true;
        $this->token = isset($_GET['token']) ? $_GET['token'] : '';

        if ($_GET && isset($_GET['action'])) {
            $function = $_GET['action'];
            $this->{$function}();
        }
    }

    public function initContent() {
        $link = new Link();
        parent::initContent();
        $this->context->smarty->assign(
            'configurationURL',
            $this->module->dirurl . '/index.php?controller=AdminModules&configure=shoprunback&token=' . $this->token
        );

        $this->context->smarty->assign('token', Configuration::get('token'));
        $this->context->smarty->assign('shoprunbackURL', $this->module->url);
        $this->context->smarty->assign('shoprunbackAPIURL', $this->module->apiUrl);
        $this->context->smarty->assign(
            'asyncCall',
            $this->module->dirurl . '/index.php?controller=AdminShoprunback&token=' . $this->token . '&action=asyncCall'
        );
        $this->context->smarty->assign('link', $link);
        $this->context->smarty->assign('srbManager', $this->module->dirurl . '/index.php?controller=AdminShoprunback&token=' . $this->token);

        $conditionsToSend = '';
        $currentPage = (isset($_GET['currentPage'])) ? $_GET['currentPage'] : 1;
        $pages = 1;
        $itemType = (isset($_GET['itemType'])) ? $_GET['itemType'] : 'products';
        $itemsByPage = 20;
        $itemsToShow = [];
        switch ($itemType) {
            case 'returns':
                break;
            case 'products':
                $productSql = new DbQuery();
                $productSql->select('p.id_product as id_product, p.id_product as reference, pl.name, IF(srb.last_sent IS NULL, "-", srb.last_sent) as last_sent');
                $productSql->from('product', 'p');
                $productSql->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product');
                $productSql->leftJoin('srb_api_calls', 'srb', 'srb.id_item = p.id_product
                                                                AND srb.type = "product"
                                                                AND srb.last_sent IN (
                                                                    SELECT MAX(srb.last_sent)
                                                                    FROM ps_srb_api_calls srb
                                                                    WHERE srb.type = "product"
                                                                    GROUP BY srb.id_item
                                                                )');
                $productSql->where('pl.id_lang = ' . Configuration::get('PS_LANG_DEFAULT'));
                $productSql->orderBy('srb.last_sent DESC');
                $products = Db::getInstance()->executeS($productSql);

                $countProducts = count($products);
                $pages = ceil($countProducts / $itemsByPage);
                $currentPage = ($currentPage <= $pages) ? $currentPage : 1;

                for ($i = ($currentPage - 1) * $itemsByPage; $i < $currentPage * $itemsByPage; $i++) {
                    if (isset($products[$i])) {
                        $products[$i]['id'] = $products[$i]['id_product'];
                        $itemsToShow[] = $products[$i];
                    }
                }

                $conditionsToSend = 'ShopRunBack needs all your products to have a name, a reference, a width, an height, a depth, a weight and a brand';
                break;
            case 'orders':
                $orderSql = new DbQuery();
                $orderSql->select('o.id_order, o.reference, IF(srb.last_sent IS NULL, "-", srb.last_sent) as last_sent');
                $orderSql->from('orders', 'o');
                $orderSql->leftJoin('srb_api_calls', 'srb', 'srb.id_item = o.id_order
                                            AND srb.type = "order"
                                            AND srb.last_sent IN (
                                                SELECT MAX(srb.last_sent)
                                                FROM ps_srb_api_calls srb
                                                WHERE srb.type = "order"
                                                GROUP BY srb.id_item
                                            )');
                $orderSql->orderBy('srb.last_sent DESC');
                $orders = Db::getInstance()->executeS($orderSql);

                $countOrders = count($orders);
                $pages = ceil($countOrders / $itemsByPage);
                $currentPage = ($currentPage <= $pages) ? $currentPage : 1;

                for ($i = ($currentPage - 1) * $itemsByPage; $i < $currentPage * $itemsByPage; $i++) {
                    if (isset($orders[$i])) {
                        $orders[$i]['id'] = $orders[$i]['id_order'];
                        $orders[$i]['name'] = $orders[$i]['reference'];
                        $itemsToShow[] = $orders[$i];
                    }
                }

                $conditionsToSend = 'ShopRunBack needs all your orders to have ...';
                break;
            case 'brands':
                $brandSql = new DbQuery();
                $brandSql->select('m.id_manufacturer as id_manufacturer, m.id_manufacturer as reference, m.name, IF(srb.last_sent IS NULL, "-", srb.last_sent) as last_sent');
                $brandSql->from('manufacturer', 'm');
                $brandSql->leftJoin('srb_api_calls', 'srb', 'srb.id_item = m.id_manufacturer
                                                                AND srb.type = "manufacturer"
                                                                AND srb.last_sent IN (
                                                                    SELECT MAX(srb.last_sent)
                                                                    FROM ps_srb_api_calls srb
                                                                    WHERE srb.type = "manufacturer"
                                                                    GROUP BY srb.id_item
                                                                )');
                $brandSql->orderBy('srb.last_sent DESC');
                $brands = Db::getInstance()->executeS($brandSql);

                $countBrands = count($brands);
                $pages = ceil($countBrands / $itemsByPage);

                for ($i = ($currentPage - 1) * $itemsByPage; $i < $currentPage * $itemsByPage; $i++) {
                    if (isset($brands[$i])) {
                        $brands[$i]['id'] = $brands[$i]['id_manufacturer'];
                        $itemsToShow[] = $brands[$i];
                    }
                }

                $conditionsToSend = 'ShopRunBack needs all your brands to have ...';
                break;
        }

        $this->context->smarty->assign('conditionsToSend', $this->trans($conditionsToSend));
        $this->context->smarty->assign('currentPage', $currentPage);
        $this->context->smarty->assign('pages', $pages);
        $this->context->smarty->assign('itemType', $itemType);
        $this->context->smarty->assign('items', $itemsToShow);

        $this->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/products.js');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/override.css');
        $this->addCSS(_PS_MODULE_DIR_ . $this->module->name . '/views/css/srbManager.css');
        $this->addCSS('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

        $this->setTemplate('../../../../modules/' . $this->module->name . '/views/templates/admin/srbManager.tpl');
    }

    public function asyncCall () {
        require_once($this->module->SRBModulePath . '/asyncCall.php');
    }

    public function test () {
        $product = SRBProduct::getById(1);
        echo '<pre>';print_r($product);echo '</pre>';
        die;
        // $result = $this->module->postProduct($product);

        // $product = new stdClass();
        // $product->id_product = 15;
        // $product->name = 'New';
        // $product->reference = 'newew';
        // $product->id_manufacturer = 1;
        // $product->height = 1;
        // $product->width = 1;
        // $product->depth = 1;
        // $product->weight = 1;
        // $result = $this->module->postProduct($product);

        // $result = $this->module->postBrand(2);

        // $_POST['action'] = 'postAllOrders';
        // $_POST['params'] = true;
        // $result = $this->asyncCall();

        // $sql = new DbQuery();
        // $sql->select('o.*, c.*, a.*, s.*, co.*, ca.*');
        // $sql->from('orders', 'o');
        // $sql->innerJoin('customer', 'c', 'c.id_customer = o.id_customer');
        // $sql->innerJoin('address', 'a', 'c.id_customer = a.id_customer');
        // $sql->innerJoin('state', 's', 's.id_state = a.id_state');
        // $sql->innerJoin('country', 'co', 's.id_country = co.id_country');
        // $sql->innerJoin('cart', 'ca', 'o.id_cart = ca.id_cart');
        // $sql->where('o.id_order = 3');
        // $order = $this->module->formalizer->arrayToObject(Db::getInstance()->executeS($sql)[0]);
        // $result = $this->module->postOrder($order);

        // $result = $this->module->postAllProducts();

        // $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "srb_api_calls`(
        //     `id_srb_api_calls` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        //     `id_item` INT(11) NOT NULL,
        //     `type` VARCHAR(256) NOT NULL,
        //     `last_sent` DATETIME NOT NULL
        // )";
        // $sql = "SHOW COLUMNS FROM `" . _DB_PREFIX_ . "srb_api_calls`";
        // $sqls[] = "CREATE INDEX IF NOT EXISTS index_type_id_item ON TABLE `" . _DB_PREFIX_ . "srb_api_calls` (type, id_item)";
        // echo $sql;

        // $result = Db::getInstance()->Execute($sql);

        echo '<pre>';print_r($result);echo '</pre>';
        // var_dump($result);
        die;
    }
}
