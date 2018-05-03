// Unlike PS 1.6, you must wait for the DOM to be loaded for PS 1.7
// because the order's details is a whole page, so the template is loaded with the page
document.addEventListener("DOMContentLoaded", function(event) {
  $.getScript(frontJsPath + "orderDetail.js");
});