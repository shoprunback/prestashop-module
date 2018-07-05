function toggleModal() {
  if ($('#modal').css('display') === 'block') {
    $('#modal').css('display', 'none');
    $('#request').css('display', 'block');
  } else {
    $('#modal').css('display', 'block');
    $('#request').css('display', 'none');
  }
}

function createShopRunBackReturn(createReturnLink) {
  var message = loadingMessage ? loadingMessage : '<h1>Loading...</h1>';

  $.ajax({
    url: createReturnLink,
    method: 'POST',
    dataType: 'json',
    beforeSend: function () {
      $('#order-detail #modal .content').html(message);
    },
    success: function (shipbackPublicUrl) {
      window.location.href = shipbackPublicUrl;
    },
    error: function (xhr) {
      window.location.href = xhr.responseText;
    }
  });
}