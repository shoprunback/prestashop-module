/**
 * 2007-2018 ShopRunBack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ShopRunBack
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the ShopRunBack module to newer
 * versions in the future.
 *
 * @author ShopRunBack <contact@shoprunback.com>
 * @copyright 2007-2018 ShopRunBack
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of ShopRunBack
 **/

document.addEventListener("DOMContentLoaded", function(event) {
  var menu = document.getElementsByClassName('menu')[0];
  if (!menu) {
    menu = document.getElementsByClassName('main-menu')[0];
  }
  for (var i = 0; i < menu.childNodes.length; i++) {
    var node = menu.childNodes[i];
    for (var j = 0; j < node.childNodes.length; j++) {
      var a = node.childNodes[j];
      if (a.href && a.href.indexOf('controller=AdminShoprunback') != -1) {
        a.childNodes[1].innerHTML = 'sync';
        return;
      }
    }
  }
});