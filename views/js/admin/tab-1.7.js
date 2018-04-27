document.addEventListener("DOMContentLoaded", function(event) {
  var menu = document.getElementsByClassName('menu')[0];
  if (!menu) {
    menu = document.getElementsByClassName('main-menu')[0];
  }
  for (var i = 0; i < menu.childNodes.length; i++) {
    var node = menu.childNodes[i];
    for (var j = 0; j < node.childNodes.length; j++) {
      var a = node.childNodes[j];
      if (a.href && a.href.indexOf('controller=AdminShoprunback') != -1) {
        a.childNodes[1].innerHTML = 'sync';
        return;
      }
    }
  }
});