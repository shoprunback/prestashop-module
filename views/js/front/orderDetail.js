// Unlike PS 1.7, you must not wait for the DOM to be loaded for PS 1.6
// because the template is loaded dynamically, so the DOM is already loaded
var redirectUrl = '';

$('#create-return').on('click', function () {
  $.ajax({
    url: createReturnLink,
    method: 'POST',
    dataType: 'json',
    success: function (urls) {
      if (typeof urls === 'object') {
        // Success
        redirectUrl = urls.redirectUrl;
        $('.external-link').attr('href', urls.shipbackPublicUrl);
        $('.cancel').attr('href', redirectUrl);
        $('#modal').css('display', 'flex');
      } else {
        // Failure
        window.location.href = urls;
      }
    }
  });
});

$('.external-link').on('click', function () {
  window.location.href = redirectUrl;
});