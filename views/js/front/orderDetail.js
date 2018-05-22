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
  $.ajax({
    url: createReturnLink,
    method: 'POST',
    dataType: 'json',
    success: function (shipbackPublicUrl) {
      window.location.href = shipbackPublicUrl;
    },
    error: function (xhr) {
      window.location.href = xhr.responseText;
    }
  });
}