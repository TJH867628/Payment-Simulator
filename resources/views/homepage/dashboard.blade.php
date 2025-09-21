<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>eWallet Dashboard</title>

<!-- CSRF token for Laravel requests -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: linear-gradient(135deg, #ff4d4d, #ffcc00);
        min-height: 100vh;
    }
    .navbar {
        background-color: #b30000;
    }
    .btn-primary {
        background-color: #ff4d4d;
        border: none;
    }
    .btn-primary:hover {
        background-color: #e60000;
    }
    .card {
        border-radius: 15px;
    }
</style>
</head>
<body>
<nav class="navbar navbar-dark px-3">
  <span class="navbar-brand">eWallet</span>
  <button class="btn btn-warning btn-sm" onclick="logout()">Logout</button>
</nav>

<div class="container py-4">
  <div class="row g-4 d-flex align-items-stretch">
    <!-- Balance -->
    <div class="col-md-4">
      <div class="card text-center p-3 shadow h-100">
        <h4 class="text-danger">Balance</h4>
        <h2 id="balance">RM 0.00</h2>
      </div>
    </div>

    <!-- Top Up -->
    <div class="col-md-4">
      <div class="card p-3 shadow h-100">
        <h5 class="text-danger">Top Up</h5>
        <input type="number" id="topupAmount" class="form-control mb-2" placeholder="Amount">
        <button id="topUpBtn" class="btn btn-primary w-100">Top Up</button>
      </div>
    </div>

    <!-- Transfer (unchanged demo) -->
    <div class="col-md-4">
      <div class="card p-3 shadow h-100">
        <h5 class="text-danger">Transfer</h5>
        <ul class="nav nav-pills mb-3" id="transferTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="phone-tab" data-bs-toggle="pill"
                    data-bs-target="#phoneTransfer" type="button">Phone</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="qr-tab" data-bs-toggle="pill"
                    data-bs-target="#qrTransfer" type="button">QR</button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="phoneTransfer">
            <input type="tel" id="transferPhoneNum" class="form-control mb-2"
                  placeholder="Recipient Phone Number">
            <input type="number" id="transferAmountPhone" class="form-control mb-2"
                  placeholder="Amount">
            <button class="btn btn-primary w-100" onclick="transferPhone()">Transfer</button>
          </div>
          <div class="tab-pane fade" id="qrTransfer">
            <div class="d-grid gap-2 mb-3">
              <button class="btn btn-warning" onclick="scanQR()">📷 Scan QR Code</button>
              <button class="btn btn-secondary" onclick="chooseFromGallery()">🖼️ Choose from Gallery</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- History -->
<div class="card p-3 shadow mt-4">
  <h5 class="text-danger mb-3">Transaction History</h5>
  <ul id="history" class="list-group"></ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
// ---------- Logout ----------
function logout(){
  location.href='/';
}

// ---------- Transfer Helpers (demo only) ----------
function transferPhone(){
  const phone = document.getElementById('transferPhoneNum').value;
  const amt   = parseFloat(document.getElementById('transferAmountPhone').value);
  if(!phone || !amt) return alert("Invalid transfer");
  alert(`(Demo) Transfer RM ${amt} to ${phone}`);
}
function scanQR(){ alert("✅ QR scanned (demo only)"); }
function chooseFromGallery(){ alert("✅ QR decoded (demo only)"); }

// ---------- Wallet / Top-Up / Transactions ----------
document.addEventListener('DOMContentLoaded', () => {
  // 1. Get logged-in user from sessionStorage (set in login.js)
  const user = JSON.parse(sessionStorage.getItem('user'));
  if (!user) {
    alert('Please log in again.');
    window.location.href = '/';
    return;
  }

  // 2. Fetch wallet info
  fetch(`/api/wallet/${user.id}`, {
    method: 'GET',
    credentials: 'include'
  })
  .then(r => { if(!r.ok) throw new Error('Wallet fetch failed'); return r.json(); })
  .then(data => {
    window.currentWalletId = data.wallet.id;  // save wallet id
    document.getElementById('balance').textContent =
      "RM " + parseFloat(data.wallet.balance).toFixed(2);
    loadTransactions(window.currentWalletId);
  })
  .catch(err => {
    console.error(err);
    alert('Could not fetch wallet info.');
  });

  // 3. Bind Top-Up button
  document.getElementById('topUpBtn').addEventListener('click', topUp);
});

function topUp(){
  const amt = parseFloat(document.getElementById('topupAmount').value);
  if(!amt || amt <= 0) return alert('Enter a valid amount');

  $.ajax({
    url: `/api/wallet/${window.currentWalletId}/topup`,
    type: 'POST',
    data: JSON.stringify({ amount: amt, description: 'Wallet top-up' }),
    contentType: 'application/json',
    dataType: 'json',
    xhrFields: { withCredentials: true },
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    success: function(res){
      if(res.bill && res.bill[0] && res.bill[0].BillCode){
        const billCode = res.bill[0].BillCode;
        // Redirect to ToyyibPay payment page
        window.location.href = `https://dev.toyyibpay.com/${billCode}`;
      } else {
        alert('Unable to create bill.');
      }
    },
    error: function(xhr){
      alert('Top-up error: ' + xhr.status);
    }
  });
}

function loadTransactions(walletId) {
  $.ajax({
    url: `/api/transactions/${walletId}`,
    type: 'GET',
    dataType: 'json',
    xhrFields: { withCredentials: true },
    success: function(res) {
      const list = document.getElementById('history');

      if (res.status === "Found" && Array.isArray(res.transactions)) {
        list.innerHTML = res.transactions.map(t =>
          `<li class="list-group-item">
             ${t.type} RM ${parseFloat(t.amount).toFixed(2)}
             – ${t.status} – ${new Date(t.created_at).toLocaleString()}
           </li>`
        ).join('');
      }
      else if (res.status === "NotFound") {
        // Just one element—no need to map
        list.innerHTML = `<li class="list-group-item text-center">
                            <strong>No transaction found</strong>
                          </li>`;
      }
    },
    error: function(err) {
      console.error(err);
      alert('Could not load transactions');
    }
  });
}

</script>
</body>
</html>
