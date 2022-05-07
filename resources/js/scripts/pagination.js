$(function() {
  $('body').on('click', '#ajax-content .pagination a', function(e) {
    e.preventDefault();

    $.ajax({
      url: $(this).attr('href'), cache: false
    }).done(function (data) {
      let replacement = $(data).find('#ajax-content');
      $('#ajax-content').html(replacement.html());
      runPostPagination();
    }).fail(function () {
      window.location.replace($(this).attr('href'))
    });

    window.history.pushState('', '', $(this).attr('href'));
  });
});

function runPostPagination () {
  if (typeof postPagination !== 'undefined' && $.isFunction(postPagination)) {
    postPagination();
  }
  $('[data-toggle="tooltip"]').tooltip({ boundary: 'window' });
}

runPostPagination();
