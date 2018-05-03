// Unlike PS 1.7, you must not wait for the DOM to be loaded for PS 1.6
// because the template is loaded dynamically, so the DOM is already loaded
$.getScript(frontJsPath + "orderDetail.js");