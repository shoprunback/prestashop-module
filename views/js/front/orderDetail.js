function createShopRunBackReturn(createReturnLink) {
  $.ajax({
    url: createReturnLink,
    method: 'POST',
    dataType: 'json',
    success: function (urls) {
      $('.external-link').attr('href', urls.shipbackPublicUrl);
      $('.cancel').attr('href', urls.redirectUrl);
      $('#modal').css('display', 'flex');

      $('.external-link').on('click', function () {
        window.location.href = urls.redirectUrl;
      });
    },
    error: function (xhr) {
      window.location.href = xhr.responseText;
    }
  });
}