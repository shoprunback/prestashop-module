document.addEventListener("DOMContentLoaded", function(event) {
  var menu = document.getElementsByClassName('menu')[0];
  var menuModule = document.getElementsByClassName('main-menu')[0];
  var url = 'controller=AdminShoprunback';
  if (!menu) {
    menu = menuModule;
  }
  for (var i = 0; i < menu.childNodes.length; i++) {
    var node = menu.childNodes[i];
    for (var j = 0; j < node.childNodes.length; j++) {
      var a = node.childNodes[j];
      if (a.href && a.href.indexOf(url) != -1) {
        a.childNodes[1].innerHTML = 'sync';
        return;
      }
    }
  }
});