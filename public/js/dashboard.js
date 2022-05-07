new Chart($("#revenueChart"), {
  "type": "line",
  "data": {
      "labels": ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
      "datasets": [{
          "label": revenueLabel,
          "data": revenueData,
          "fill": true,
          "borderColor": graphBorderColor,
          "lineTension": 0.2
      }]
  },
  "options": {
    legend: {
      display: false
    }
  }
});

function clearCache () {
  swal({
    title: 'Confirm clearing the cache',
    text: 'Are you sure you want to clear the cache?',
    type: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, clear it!'
  }).then((result) => {
    if (result.value) {
      $.ajax({ url: "/api/cache", type: 'DELETE', success: function(res) {
  	    if (res.status == true) {
    		  swal({
    			  type: 'success',
    			  title: 'Cache cleared!'
    			});
    	  } else {
          swal({
            type: 'error',
            title: 'Failed to clear the cache',
            text: res
          });
        }
      }})
    }
  });
}
