<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Top-Up Status</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container text-center py-5">
    <!-- Status Icon -->
    <svg id="statusIcon" xmlns="http://www.w3.org/2000/svg" width="120" height="120" fill="none"
         viewBox="0 0 24 24" stroke="gray" stroke-width="2" class="mb-4">
      <circle cx="12" cy="12" r="10" stroke="gray" stroke-width="2" fill="none"/>
    </svg>

    <h1 id="statusTitle" class="fw-bold">Checking payment status...</h1>
    <p class="lead mt-3" id="statusText">Please wait while we confirm your top-up.</p>
    <a href="/dashboard" class="btn btn-secondary mt-4">Back to Dashboard</a>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const params = new URLSearchParams(window.location.search);
    const billCode = params.get('billcode');

    if (billCode) {
      const interval = setInterval(() => {
        $.getJSON(`/api/topup/status/${billCode}`, function(res) {
          if (res.status === 'completed') {
            $('#statusIcon').html(`<path stroke="green" d="M7 13l3 3 7-7" />`);
            $('#statusTitle').text('Top-Up Successful!');
            $('#statusText').text(`RM ${parseFloat(res.amount).toFixed(2)} added to your wallet.`);
            clearInterval(interval);
          } else if (res.status === 'failed') {
            $('#statusIcon').html(`<path stroke="red" d="M6 6l12 12M6 18L18 6" />`);
            $('#statusTitle').text('Top-Up Failed');
            $('#statusText').text('Your payment could not be completed. Please try again.');
            clearInterval(interval);
          } else {
            $('#statusTitle').text('Payment Pending...');
            $('#statusText').text('Waiting for payment confirmation…');
          }
        }).fail(() => {
          $('#statusTitle').text('Error');
          $('#statusText').text('Unable to check payment status. Please try again later.');
          clearInterval(interval);
        });
      }, 3000);
    } else {
      $('#statusTitle').text('No Transaction Found');
      $('#statusText').text('No bill code provided in URL.');
    }
  </script>
</body>
</html>