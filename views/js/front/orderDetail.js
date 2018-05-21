function toggleModal() {
  if ($('#modal').css('display') === 'flex') {
    $('#modal').css('display', 'none');
  } else {
    $('#modal').css('display', 'flex');
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