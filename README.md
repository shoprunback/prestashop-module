# Module ShopRunBack for Prestashop

This repository hosts the code for the ShopRunBack module on Prestashop. To use it, follow the instructions below.

## If you are an e-merchant

Visit the [documentation](https://shoprunback.github.io/documentation/prestashop.html) and follow the installation instructions.


## If you are a developer

### Setup

If you want to test the module locally, you will need to setup a local server in order to install Prestashop.

To setup a local server on your machine :
- Linux : https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu
- Mac : https://www.mamp.info/en/
- Windows : http://www.wampserver.com/

If not, just [download](https://www.prestashop.com/fr/telecharger) Prestashop and install it on your server.


### Installation

Once your Prestashop is ready, go to Modules & Services, and then upload the archived module (available [here](https://github.com/shoprunback/prestashop-module/releases/latest)).

In order to use the module, you need an account on the ShopRunBack dashboard (you can sign up [here](https://dashboard.shoprunback.com/)).

### Configuration

To configure it, go to the configuration page. You can go there by 2 ways:
- Go to Modules & Services -> Installed modules, search for "ShopRunBack", and click on "Configure"
- Click on the ShopRunBack tab on the left menu, and then go to "Configuration"

### How to contribute ?

In order to contribute to this module, please follow these steps in this order :
- Use the command 'git clone [repository_url] shoprunback' in the modules directory of your PrestaShop installation
- Run 'composer install'
- Then if you are working with Linux, run 'sudo chmod -R 777 shoprunback'

Please make sure to follow the [PrestaShop Guidelines](https://devdocs.prestashop.com/1.7/modules/) 