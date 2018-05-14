$(document).ready(function() {
  var exportLogsForm = $('#config form')[0];

  exportLogsForm.on('submit', function () {
    $.ajax({
      url: exportLogsForm.attr('action'),
      method: exportLogsForm.attr('method'),
      dataType: 'json',
      success: function (response) {
        console.log(response);
      },
      error: function (response) {
        console.log(response);
      }
    });

    return false;
  });
});