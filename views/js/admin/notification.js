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