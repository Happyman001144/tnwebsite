function submitForm (form) {
  $.post($(form).attr('action'), $(form).serialize(), function(response){
    if (response.status === 'success') {
      swal({
        type: 'success',
        title: 'Changes saved',
        showConfirmButton: false,
        timer: 1000
      })
    } else {
      swal({
        type: 'error',
        title: 'MySQL error',
        text: response
      })
    }
   });
   return false;
}

function validateTabbedInput() {
  $('input:invalid,select:invalid').each(function () {
    var cardIndex = $(this).closest('form').attr('index');
    var tab = $(this).closest('.tab-pane').attr('id');
    settingsapp.selectTab(cardIndex, tab);
    return false;
  });
}
