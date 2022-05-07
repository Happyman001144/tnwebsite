window.toggleLoadingMode = function(element) {
  var loadingText = '<i class="fas fa-circle-notch fa-spin"></i>';
  if (element.html() !== loadingText) {
    element.data('original-text', element.html());
    element.html(loadingText);
    element.addClass("disabled");
  } else {
    element.html(element.data('original-text'));
    element.removeClass("disabled");
  }
}

$('body').on('click', '#ajax-content .pagination a', function(e) {
  toggleLoadingMode($(this));
});

import NProgress from 'nprogress';
NProgress.configure({
  showSpinner: false,
  speed: 600
});
$(document).ajaxStart(function() {
  NProgress.start();
});
$(document).ajaxStop(function() {
  NProgress.done();
});

function updateBodyHeight() {
  var navHeight = $('.navbar').height();
  $('body').css({ marginTop: navHeight + 16 + 'px', minHeight: Math.max(document.documentElement.clientHeight, window.innerHeight || 0) - navHeight-16 });
}
updateBodyHeight();
$(function() { updateBodyHeight(); });
$(window).on('resize', function() { updateBodyHeight(); });
