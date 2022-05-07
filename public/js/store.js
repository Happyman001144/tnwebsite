$('#creditsInput').on('change keyup', function() {$('#creditsDollarValue').html(creditsToDollars($('#creditsInput').val()));});

function creditsToDollars (amount) {
  return Math.round(amount*10)/1000;
}

function purchaseCredits (paypalURL) {
  if ($('#creditsInput').val() >= minimum_purchase) {
    $('#creditsFormQuantity').val($('#creditsInput').val()); // pass user input value to form
    $('#creditsPurchaseForm').submit();
    swal({
      title: 'Please wait',
      html: 'You are being redirected to the checkout page.',
      onOpen: () => {
        swal.showLoading()
      }
    });
  } else {
    swal({
      type: 'error',
      title: 'Amount too low',
      text: 'Due to transaction fees you need to purchase at least '+minimum_purchase+' credits per transaction. Thank you for understanding.'
    });
  }
}
