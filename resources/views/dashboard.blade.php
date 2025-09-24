<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>eWallet Dashboard – We1Pay</title>

<!-- CSRF token for Laravel requests -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Favicon using inline SVG -->
<link rel="icon" href='data:image/svg+xml;utf8,
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
  <rect width="100" height="100" rx="18" fill="%23b30000"/>
  <text x="15" y="65" font-size="55" font-family="Arial" font-weight="700" fill="white">W1</text>
</svg>' type="image/svg+xml">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>

<style>
    body {
        background: linear-gradient(135deg, #ff4d4d, #ffcc00);
        min-height: 100vh;
    }
    .navbar {
        background-color: #b30000;
    }
    .navbar-brand svg {
        height: 34px;
        width: auto;
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
    /* --- Transaction History Style --- */
    .transaction-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
    }
    .transaction-item:last-child { border-bottom: none; }
    .transaction-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .transaction-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ffe6e6;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 18px;
        color: #b30000;
        flex-shrink: 0;
    }
    .transaction-details { display: flex; flex-direction: column; }
    .transaction-title { font-weight: 600; font-size: 0.95rem; }
    .transaction-date { font-size: 0.8rem; color: #666; }
    .transaction-amount { font-weight: 600; font-size: 1rem; }
    .transaction-amount.negative { color: #d9534f; }
    .transaction-amount.positive { color: #28a745; }
</style>
</head>
<body>
<nav class="navbar navbar-dark px-3">
  <a class="navbar-brand d-flex align-items-center" href="/dashboard">
    <!-- Inline We1Pay logo -->
    <svg xmlns="http://www.w3.org/2000/svg" width="160" height="34" viewBox="0 0 300 60" aria-label="We1Pay">
      <g transform="translate(70, 40)" font-family="Arial,Helvetica,sans-serif" font-weight="700">
        <text x="0" y="0" font-size="28" fill="#ffffff">We1</text>
        <text x="68" y="0" font-size="28" fill="#ffcc00">Pay</text>
      </g>
    </svg>
  </a>
  <button class="btn btn-warning btn-sm" onclick="logout()">Logout</button>
</nav>

<div class="container py-4">
  <!-- --- Your existing dashboard cards and tabs remain unchanged --- -->
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

    <!-- Transfer -->
    <div class="col-md-4">
      <div class="card p-3 shadow h-100">
        <h5 class="text-danger">Transfer</h5>
        <ul class="nav nav-pills mb-3" id="transferTab" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" id="phone-tab" data-bs-toggle="pill"
                    data-bs-target="#phoneTransfer" type="button">Phone</button>
          </li>
          <li class="nav-item">
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
          <!-- Password modal stays the same -->
          <div class="modal fade" id="passwordModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Confirm Transfer</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p>Please enter your password to confirm this transfer.</p>
                  <input type="password" id="transferPassword" class="form-control" placeholder="Password">
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" onclick="confirmTransfer()">Confirm</button>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="qrTransfer">
            <div class="d-grid gap-3 mb-3">
              <button class="btn btn-warning" onclick="scanQR()">📷 Scan QR Code</button>
              <input type="file" id="qrFileInput" accept="image/*" hidden>
              <button class="btn btn-secondary" onclick="chooseFromGallery()">🖼️ Choose from Gallery</button>
              <button class="btn btn-success" onclick="receive()">⬇️ Receive</button>
            </div>
          </div>

          <!-- Receive QR Modal -->
          <div class="modal fade" id="receiveModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content text-center p-3">
                <div class="modal-header border-0">
                  <h5 class="modal-title w-100">Your Receive QR Code</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p class="mb-3">
                    Scan this QR code to transfer to <strong><span id="receiveUserName"></span></strong>
                  </p>
                  <div id="receiveQrContainer" class="d-flex justify-content-center"></div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Transaction History -->
<div class="card shadow mt-4">
  <div class="card-header bg-white border-0">
    <h5 class="text-danger mb-0">Transaction History</h5>
  </div>
  <div id="history" class="list-group list-group-flush p-0"></div>
</div>

<!-- Camera modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Scan QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="reader" style="width:100%"></div>
      </div>
    </div>
  </div>
</div>

<!-- Enter Amount Modal -->
<div class="modal fade" id="qrAmountModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Enter Transfer Amount</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="qrTransferText" class="mb-3"></p>
        <input type="number" class="form-control mb-3" id="qrTransferAmount"
               placeholder="Amount (RM)">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="qrAmountConfirmBtn">Continue</button>
      </div>
    </div>
  </div>
</div>

<input type="file" accept="image/*" id="qrFileInput" style="display:none">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script>
// --- Logout ---
function logout(){ location.href='/'; }

// --- Transfer, QR, Wallet, Top-Up, and Transaction JS remain unchanged ---
// Called when user clicks "Transfer" (already defined)
function transferPhone() {
  const phone = document.getElementById('transferPhoneNum').value.trim();
  const amt   = parseFloat(document.getElementById('transferAmountPhone').value);

  if (!phone || isNaN(amt) || amt <= 0) {
    return alert("Enter a valid amount.");
  }

  // Store details temporarily and show the password modal
  window.pendingTransfer = { phone, amt };
  new bootstrap.Modal(document.getElementById('passwordModal')).show();
}

function confirmTransfer() {
  const pwdField = document.getElementById('transferPassword');
  const password = pwdField.value.trim();
  if (!password) return alert('Please enter your password.');

  const user = JSON.parse(sessionStorage.getItem('user'));
  if (!user || !user.id || !user.phone_number) {
    alert('User not registered. Please log in again.');
    window.location.href = '/';
    return;
  }

  const { phone: toPhone, amt } = window.pendingTransfer;

  // ✅ Prevent sending to yourself
  if (toPhone === user.phone_number) {
    alert("You can't transfer to your own phone number.");
    return;
  }

  // ✅ **NEW CHECK**: make sure there’s enough balance
  const currentBalanceText = document.getElementById('balance').textContent;
  // currentBalanceText looks like "RM 123.45" → strip non-digits except dot
  const currentBalance = parseFloat(currentBalanceText.replace(/[^\d.]/g, ''));
  if (isNaN(currentBalance) || amt > currentBalance) {
    alert('Insufficient balance.');
    return;
  }

  // Step 1: Verify password
  $.ajax({
    url: '/api/verifyPassword',
    type: 'POST',
    dataType: 'json',
    contentType: 'application/json',
    data: JSON.stringify({
      id: user.id,
      password: password
    }),
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    xhrFields: { withCredentials: true },

    success: function (res) {
      if (!res.valid) {
        alert('Incorrect password.');
        return;
      }

      // Step 2: Perform the transfer
      $.ajax({
        url: '/api/transfer',
        type: 'POST',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
          from_phone: user.phone_number,
          to_phone: toPhone,
          amount: amt,
        }),
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        xhrFields: { withCredentials: true },

        success: function (res2) {
          alert(res2.message || 'Transfer successful');
          window.location.href = '/dashboard';
          return;

          if (typeof res2.from_wallet_balance !== 'undefined') {
            document.getElementById('balance').textContent =
              'RM ' + parseFloat(res2.from_wallet_balance).toFixed(2);
          }

          if (window.currentWalletId) {
            loadTransactions(window.currentWalletId);
          }

          // Close modal and reset
          bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
          pwdField.value = '';
          window.pendingTransfer = null;
        },
        error: xhr2 => {
          let msg = 'Transfer failed';
          if (xhr2.responseJSON?.message) msg = xhr2.responseJSON.message;
          alert(msg);
        }
      });
    },

    error: xhr => {
      let msg = 'Password verification failed';
      if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
      alert(msg);
    }
  });
}

let cameraStream;   // store stream to stop later
let scanInterval;

function scanQR() {
  const modalEl = document.getElementById('qrModal');
  const modal   = new bootstrap.Modal(modalEl);
  modal.show();

  const reader = document.getElementById('reader');
  reader.innerHTML =
    '<video id="qrVideo" autoplay playsinline muted style="width:100%;"></video>';

  const video = document.getElementById('qrVideo');

  navigator.mediaDevices.getUserMedia({
    video: { facingMode: { ideal: "environment" } }
  })
  .then(stream => {
    cameraStream = stream;
    video.srcObject = stream;

    const canvas = document.createElement('canvas');
    const ctx    = canvas.getContext('2d');

    scanInterval = setInterval(() => {
      if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, canvas.width, canvas.height);
        if (code) {
          stopCamera();
          modal.hide();
          handleDecodedQR(code.data);
        }
      }
    }, 300);
  })
  .catch(err => alert("Camera access failed: " + err));

  modalEl.addEventListener('hidden.bs.modal', stopCamera, { once: true });
}

function stopCamera() {
  if (scanInterval) {
    clearInterval(scanInterval);
    scanInterval = null;
  }
  if (cameraStream) {
    cameraStream.getTracks().forEach(t => t.stop());
    cameraStream = null;
  }
  const reader = document.getElementById('reader');
  reader.innerHTML = ''; // clear video element
}

function chooseFromGallery() {
  document.getElementById('qrFileInput').click();
}

document.getElementById('qrFileInput').addEventListener('change', async e => {
  const file = e.target.files[0];
  if (!file) return;

  const img = new Image();
  img.src = URL.createObjectURL(file);

  img.onload = () => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = img.width;
    canvas.height = img.height;
    ctx.drawImage(img, 0, 0, img.width, img.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, canvas.width, canvas.height);

    if (code) {
      handleDecodedQR(code.data);
    } else {
      alert("No QR code found in image.");
    }
  };
});

function handleDecodedQR(text) {
  window.qrTransferPhone = text.trim();

  fetch(`/api/getUserByPhonenumber/${encodeURIComponent(window.qrTransferPhone)}`, {
    credentials: 'include'
  })
  .then(res => {
    if (!res.ok) throw new Error('User lookup failed');
    return res.json();
  })
  .then(data => {
    // read the nested user object
    const username = data.user.name || 'Unknown user';

    console.log('Scanned phone number:', window.qrTransferPhone, 'User:', username);

    document.getElementById('qrTransferText').innerHTML =
      `Transfer to: <strong>${username} (${window.qrTransferPhone})</strong>`;

    document.getElementById('qrTransferAmount').value = '';

    new bootstrap.Modal(document.getElementById('qrAmountModal')).show();
  })
  .catch(err => {
    alert('Could not fetch user details: ' + err.message);
  });
}

document.getElementById('qrAmountConfirmBtn').addEventListener('click', () => {
  const amtField  = document.getElementById('qrTransferAmount');
  const noteField = document.getElementById('qrTransferNote');
  const amt  = parseFloat(amtField.value);
  const note = noteField.value.trim() || 'QR Transfer';

  if (!amt || amt <= 0) {
    alert('Please enter a valid amount.');
    return;
  }

  // Prepare transfer just like manual form
  window.pendingTransfer = {
    phone: window.qrTransferPhone,
    amt:   amt,
  };

  // Hide the amount modal
  bootstrap.Modal.getInstance(document.getElementById('qrAmountModal')).hide();

  // Show the password confirmation modal
  new bootstrap.Modal(document.getElementById('passwordModal')).show();
});

function receive() {
  const user = JSON.parse(sessionStorage.getItem('user'));
  if (!user || !user.name) {
    alert('User info missing. Please log in again.');
    return;
  }

  // Set the name text
  document.getElementById('receiveUserName').textContent = user.name;

  // Clear and regenerate the QR code
  const qrDiv = document.getElementById('receiveQrContainer');
  qrDiv.innerHTML = '';
  new QRCode(qrDiv, {
    text: user.phone_number,
    width: 200,
    height: 200,
    colorDark: "#000",
    colorLight: "#fff",
    correctLevel: QRCode.CorrectLevel.H
  });

  // Show modal
  new bootstrap.Modal(document.getElementById('receiveModal')).show();
}

document.addEventListener('DOMContentLoaded', () => {
  const user = JSON.parse(sessionStorage.getItem('user'));
  if (!user) { alert('Please log in again.'); window.location.href = '/'; return; }
  fetch(`/api/wallet/${user.id}`, { method: 'GET', credentials: 'include' })
    .then(r => { if(!r.ok) throw new Error('Wallet fetch failed'); return r.json(); })
    .then(data => {
      window.currentWalletId = data.wallet.id;
      document.getElementById('balance').textContent = "RM " + parseFloat(data.wallet.balance).toFixed(2);
      loadTransactions(window.currentWalletId);
    })
    .catch(() => alert('Could not fetch wallet info.'));
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
    success: res => {
      if(res.bill && res.bill[0]?.BillCode){
        window.location.href = `https://dev.toyyibpay.com/${res.bill[0].BillCode}`;
      } else { alert('Unable to create bill.'); }
    },
    error: xhr => alert('Top-up error: ' + xhr.status)
  });
}

function loadTransactions(walletId) {
  $.ajax({
    url: `/api/transactions/${walletId}`,
    type: 'GET',
    dataType: 'json',
    xhrFields: { withCredentials: true },
    success: res => {
      const list = document.getElementById('history');

      if (res.status === "Found" && Array.isArray(res.transactions)) {
        const transactions = [...res.transactions].sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at)
        );

        list.innerHTML = transactions.map(t => {
          const descText  = (t.description || '').toLowerCase();

          const isTopUp   = descText.includes('top');
          const isReceive = descText.includes('transfer-in');
          const isSend    = descText.includes('transfer-out');

          const otherUser = t.counterparty_name || t.partner_name || 'Unknown';

          // Human-friendly title
          let title;
          if (isTopUp)        title = 'Top Up';
          else if (isReceive) title = `Receive from ${otherUser}`;
          else if (isSend)    title = `Transfer to ${otherUser}`;
          else                title = t.description || 'Transaction';

          // ✅ Green/+ if Top Up OR title starts with “Receive from”
          const isPositive = isTopUp || title.toLowerCase().startsWith('receive from');
          const amountCls  = isPositive ? 'positive' : 'negative';
          const sign       = isPositive ? '+' : '-';
          const icon       = isPositive ? '💰' : '📤';
          const dateStr    = new Date(t.created_at).toLocaleString();

          return `
            <div class="transaction-item">
              <div class="transaction-left">
                <div class="transaction-icon">${icon}</div>
                <div class="transaction-details">
                  <span class="transaction-title">${title}</span>
                  <span class="transaction-date">${dateStr}</span>
                </div>
              </div>
              <div class="transaction-amount ${amountCls}">
                ${sign} RM ${parseFloat(t.amount).toFixed(2)}
              </div>
            </div>`;
        }).join('');
      } else {
        list.innerHTML =
          `<div class="text-center p-4 text-muted"><strong>No transactions yet</strong></div>`;
      }
    },
    error: () => alert('Could not load transactions')
  });
}
</script>
</body>
</html>