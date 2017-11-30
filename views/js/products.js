$(document).ready(function() {
  function ajaxAsyncCall(data) {
    $.ajax({
      url: asyncCall,
      method: 'POST',
      data: data,
      dataType: 'json',
      beforeSend: function () {
        $('#srb-content .data').html('Sending...');
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
      'action': 'postAll' + $(this).data('type').charAt(0).toUpperCase() + $(this).data('type').slice(1).toLowerCase()
    };

    ajaxAsyncCall(data);
  });

  $('#srb-content .post-new').on('click', function(e) {
    e.preventDefault();

    var data = {
      'action': 'postAllNew' + $(this).data('type').charAt(0).toUpperCase() + $(this).data('type').slice(1).toLowerCase(),
      'params': true
    };

    ajaxAsyncCall(data);
  });

  $('#srb-content .sync-item').on('click', function () {
    var object = $(this).data('type').charAt(0).toUpperCase() + $(this).data('type').slice(1).toLowerCase(); // Capitalize to have for example "Products"
    object = object.slice(0, -1); // Removes the "s" to have for example "Product"

    var data = {
      'action': 'post' + object,
      'params': parseInt($(this).parent().parent().data('id'))
    };

    ajaxAsyncCall(data);
  });
});
