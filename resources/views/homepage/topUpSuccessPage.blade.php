<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Top-Up Successful</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container text-center py-5">
    <!-- Green Tick -->
    <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" fill="none"
         viewBox="0 0 24 24" stroke="green" stroke-width="2" class="mb-4">
      <circle cx="12" cy="12" r="10" stroke="green" stroke-width="2" fill="none"/>
      <path stroke="green" stroke-width="2" d="M7 13l3 3 7-7" />
    </svg>

    <h1 class="text-success fw-bold">Top-Up Successful!</h1>
    <p class="lead mt-3" id="statusText">
      Checking payment confirmation…
    </p>
    <a href="/dashboard" class="btn btn-success mt-4">Back to Dashboard</a>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    const params = new URLSearchParams(window.location.search);
    const txnId  = params.get('txn');

    if (txnId) {
    const interval = setInterval(() => {
        $.getJSON(`/api/topup/${txnId}`, function (res) {
        if (res.status === 'completed') {
            $('#statusText').text(`Payment confirmed! RM ${parseFloat(res.amount).toFixed(2)} added to your wallet.`);
            clearInterval(interval);
        } else if (res.status === 'failed') {
            $('#statusText').text('Payment failed. Please try again.');
            clearInterval(interval);
        }
        }).fail(() => {
        $('#statusText').text('Waiting for payment confirmation…');
        });
    }, 3000);
    } else {
    $('#statusText').text('No transaction ID provided.');
    }
</script>

</body>
</html>
