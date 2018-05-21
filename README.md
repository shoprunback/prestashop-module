# Module ShopRunBack for Prestashop

We use the PHP norm PSR-2

To install Prestashop:
https://www.prestashop.com/fr/telecharger

You need a local server to launch Prestashop
- Linux : https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu
- Mac : https://www.mamp.info/en/
- Windows : http://www.wampserver.com/


## Install the module on Prestashop

Once your Prestashop is ready, go to Modules & Services, search for "ShopRunBack", and install it

To configure it, go to the configuration page. You can go there by 2 ways:
- Go to Modules & Services -> Installed modules, search for "ShopRunBack", and click on "Configure"
- Click on the ShopRunBack tab on the left menu, and then go to "Configuration"


If you don't have a ShopRunBack account, create one by clicking on the link at the bottom of the form

Once your account is created, save your API Token on Prestashop.


## After the setup

- You can synchronize with ShopRunBack your products, orders and brands in the ShopRunBack tab.
- Every time you add / update / delete a product, brand or order in the back-office, it is automatically synchronized.
- Every time a client makes an order, it is automatically synchronized.
- When the client wants to return an order, a return is created and he gets redirected to ShopRunBack's return form.
