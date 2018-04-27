document.addEventListener("DOMContentLoaded", function(event) {
  if (createReturnLink) {
    $('#create-return').on('click', function () {

    });

    $.ajax({
      url: createReturnLink,
      method: 'GET',
      success: function (urls) {
        alert(urls);
        if (typeof urls === 'array') {
          // Success
          window.open(urls.shipbackPublicUrl, '_blank');
          window.location.href = urls.redirectUrl;
        } else {
          // Failure
          window.location.href = urls;
        }
      }
    })
  }
});