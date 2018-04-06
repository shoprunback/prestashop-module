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
        $('#srb-manager').html('Sending... You can go on another page while the process goes on.');
      },
      success: function (response) {
        window.location.reload();
      },
      error: function (response) {
        window.location.reload();
      }
    });
  }

  $('#srb-content .post-all').on('click', function(e) {
    e.preventDefault();

    var data = {
      'action': 'syncAll',
      'className': getSRBObjectName($(this).data('type')),
      'params': ''
    };

    ajaxAsyncCall(data);
  });

  $('#srb-content .post-new').on('click', function(e) {
    e.preventDefault();

    var data = {
      'action': 'syncAll',
      'className': getSRBObjectName($(this).data('type')),
      'params': true
    };

    ajaxAsyncCall(data);
  });

  $('#srb-content .sync-item').on('click', function (e) {
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
});
