$(document).ready(function() {
  function getSRBObjectName (string) {
    var objectName = string.charAt(0).toUpperCase() + string.slice(1).toLowerCase(); // Capitalize to have for example "Products"
    objectName = objectName.slice(0, -1); // Removes the "s" to have for example "Product"
    return 'SRB' + objectName;
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
        // console.log('success');
        // console.log(response);
      },
      error: function (response) {
        window.location.reload();
        // console.log('error');
        // console.log(response);
      }
    });
  }

  $('#srb-content .post-all').on('click', function(e) {
    e.preventDefault();

    var data = {
      'action': 'syncAll',
      'className': getSRBObjectName($(this).data('type')),
      'params': false
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
});
