<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>eWallet Dashboard</title>
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
        <button class="btn btn-primary w-100" onclick="topUp()">Top Up</button>
      </div>
    </div>

    <!-- Transfer -->
    <div class="col-md-4">
      <div class="card p-3 shadow h-100">
        <h5 class="text-danger">Transfer</h5>

        <!-- Tabs -->
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
          <!-- Phone Transfer -->
          <div class="tab-pane fade show active" id="phoneTransfer">
            <input type="tel" id="transferPhoneNum" class="form-control mb-2"
                  placeholder="Recipient Phone Number">
            <input type="number" id="transferAmountPhone" class="form-control mb-2"
                  placeholder="Amount">
            <button class="btn btn-primary w-100" onclick="transferPhone()">Transfer</button>
          </div>

          <!-- QR Transfer -->
          <div class="tab-pane fade" id="qrTransfer">
            <div class="d-grid gap-2 mb-3">
              <button class="btn btn-warning" onclick="scanQR()">📷 Scan QR Code</button>
              <button class="btn btn-secondary" onclick="chooseFromGallery()">🖼️ Choose from Gallery</button>
            </div>
            <!-- <input type="number" id="transferAmountQR" class="form-control mb-2"
                  placeholder="Amount"> -->
            <!-- <button class="btn btn-primary w-100" onclick="transferQR()">Transfer</button> -->
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
let balance = parseFloat(localStorage.getItem('balance')) || 0;
let transactions = JSON.parse(localStorage.getItem('transactions') || '[]');

function updateUI(){
  document.getElementById('balance').innerText = "RM " + balance.toFixed(2);
  const list = document.getElementById('history');
  list.innerHTML = transactions.map(t => `<li class="list-group-item">${t}</li>`).join('');
}

function topUp(){
  const amt = parseFloat(document.getElementById('topupAmount').value);
  if(amt > 0){
    balance += amt;
    transactions.unshift(`Top Up RM ${amt.toFixed(2)}`);
    save();
  }
}

function save(){
  localStorage.setItem('balance', balance);
  localStorage.setItem('transactions', JSON.stringify(transactions));
  updateUI();
}

function updateUI(){
  document.getElementById('balance').innerText = "RM " + balance.toFixed(2);
  const list = document.getElementById('history');
  list.innerHTML = transactions.map(t => `<li class="list-group-item">${t}</li>`).join('');
}

// ---- CORE LOGIC (unchanged)
function transfer(recipient, amt){
  if(recipient && amt>0 && amt<=balance){
    balance -= amt;
    transactions.unshift(`Transfer RM ${amt.toFixed(2)} to ${recipient}`);
    save();
  } else alert("Invalid transfer");
}

// ---- WRAPPERS
function transferPhone(){
  const phone = document.getElementById('transferPhoneNum').value;
  const amt   = parseFloat(document.getElementById('transferAmountPhone').value);
  transfer(phone, amt);
}

function transferQR(){
  const amt = parseFloat(document.getElementById('transferAmountQR').value);
  // qrResult should contain the decoded phone/email/account from QR
  if(!window.qrResult){
    alert("Please scan or select a QR first!");
    return;
  }
  transfer(window.qrResult, amt);
}

// ---- Mock QR functions
function scanQR(){
  // Here you would use html5-qrcode to scan
  // window.qrResult = "QR-Recipient-123"; // mock result
  alert("✅ QR scanned: " + window.qrResult);
}

function chooseFromGallery(){
  // Use file input + jsQR
  window.qrResult = "Gallery-QR-456"; // mock result
  alert("✅ QR decoded from gallery: " + window.qrResult);
}

updateUI();

function save(){
  localStorage.setItem('balance', balance);
  localStorage.setItem('transactions', JSON.stringify(transactions));
  updateUI();
}

function logout(){
  location.href='/';
}

updateUI();
</script>
</body>
</html>
