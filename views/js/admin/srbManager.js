$(document).ready(function() {
  function getSRBObjectName (string) {
    return 'SRB' + string.charAt(0).toUpperCase() + string.slice(1).toLowerCase(); // Capitalize to have for example "SRBProduct"
  }

  function ajaxAsyncCall(data) {
    $.ajax({
      url: asyncCall,
      method: 'POST',
      data: data,
      dataType: 'json',
      beforeSend: function () {
        $('#srb-manager').html(loadingMessage);
      },
      success: function (response) {
        window.location.reload();
      },
      error: function (response) {
        window.location.reload();
      }
    });
  }

  $('#srb-content .sync-element').on('click', function (e) {
    e.preventDefault();

    var data = {
      'action': 'sync',
      'className': getSRBObjectName($(this).data('type')),
      'params': parseInt($(this).parent().parent().data('id'))
    };

    ajaxAsyncCall(data);
  });

  $('#srb-content td[data-link][data-link!=""]').on('click', function (e) {
    window.open($(this).data('link'), '_blank');
  });

  $('.post-all').on('click', function(e) {
    e.preventDefault();

    var data = {
      'action': 'syncAll',
      'className': getSRBObjectName($(this).data('type')),
      'params': ''
    };

    ajaxAsyncCall(data);
  });
});
