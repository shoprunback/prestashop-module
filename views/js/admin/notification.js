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

$(document).ready(function() {
  var notificationsListDOMPath = '#srb-content .notifications';
  var notificationsDOMPath = notificationsListDOMPath + ' ul li';

  $(notificationsDOMPath + ' .mark-as-read').on('click', function () {
    var notificationId = $(this).parent().data('id');

    $.ajax({
      url: putNotificationUrl,
      method: 'POST',
      data: {id: notificationId},
      dataType: 'json',
      beforeSend: function () {
        removeNotification(notificationId);
      }
    });
  });

  function removeNotification(id) {
    $(notificationsDOMPath + '[data-id=' + id + ']').remove();

    if ($(notificationsDOMPath).length < 1) {
      $(notificationsListDOMPath).remove();
    }
  }
});