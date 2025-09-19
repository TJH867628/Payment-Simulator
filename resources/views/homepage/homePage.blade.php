<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment Simulator - Home</title>
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
<body class="d-flex justify-content-center align-items-center">
  <div class="card p-4 shadow w-100" style="max-width:400px;">
    <h2 class="text-center mb-4 text-danger">eWallet</h2>
    <ul class="nav nav-pills mb-3 justify-content-center" id="pills-tab" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#login">Login</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#register">Register</button></li>
    </ul>
    <div class="tab-content">
      <!-- Login -->
      <div class="tab-pane fade show active" id="login">
        <form onsubmit="login(event)">
          <input type="email" id="loginEmail" class="form-control mb-2" placeholder="Email" required>
          <input type="password" id="loginPass" class="form-control mb-3" placeholder="Password" required>
          <button class="btn btn-primary w-100">Login</button>
        </form>
      </div>
      <!-- Register -->
      <div class="tab-pane fade" id="register">
        <form onsubmit="register(event)">
          <input type="email" id="regEmail" class="form-control mb-2" placeholder="Email" required>
          <input type="password" id="regPass" class="form-control mb-3" placeholder="Password" required>
          <button class="btn btn-primary w-100">Register</button>
        </form>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Fake local storage user system
  function register(e){
    e.preventDefault();
    localStorage.setItem('email', document.getElementById('regEmail').value);
    localStorage.setItem('pass', document.getElementById('regPass').value);
    localStorage.setItem('balance', '0');
    localStorage.setItem('transactions', JSON.stringify([]));
    alert("Registered! Please login.");
  }
  function login(e){
    e.preventDefault();
    let email = document.getElementById('loginEmail').value;
    let pass  = document.getElementById('loginPass').value;
    if(email === localStorage.getItem('email') && pass === localStorage.getItem('pass')){
      location.href='/dashboard';
    }else alert("Invalid credentials");
  }
</script>
</body>
</html>
