<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment Simulator - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body { background: linear-gradient(135deg,#ff4d4d,#ffcc00); min-height: 100vh; }
    .navbar { background-color: #b30000; }
    .btn-primary { background-color: #ff4d4d; border: none; }
    .btn-primary:hover { background-color: #e60000; }
    .card { border-radius: 15px; }
  </style>
</head>
<body class="d-flex justify-content-center align-items-center">

  <div class="card p-4 shadow w-100" style="max-width:400px;">
    <h2 class="text-center mb-4 text-danger">eWallet</h2>

    <ul class="nav nav-pills mb-3 justify-content-center" id="pills-tab" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#login">Login</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#register">Register</button>
      </li>
    </ul>

    <div class="tab-content">
      <!-- ===== Login ===== -->
      <div class="tab-pane fade show active" id="login">
        <form onsubmit="login(event)">
          <input type="text" id="loginIdentifier" class="form-control mb-2"
                 placeholder="Email or Phone Number" required>
          <input type="password" id="loginPass" class="form-control mb-3"
                 placeholder="Password" required>
          <button class="btn btn-primary w-100">Login</button>
        </form>
      </div>

      <!-- ===== Register ===== -->
      <div class="tab-pane fade" id="register">
        <form onsubmit="register(event)">
          <input type="text"  id="regName"  class="form-control mb-2" placeholder="Full Name" required>
          <input type="email" id="regEmail" class="form-control mb-2" placeholder="Email" required>
          <input type="text"  id="regPhone" class="form-control mb-2" placeholder="Phone Number" required>
          <input type="password" id="regPass"  class="form-control mb-2" placeholder="Password" required>
          <input type="password" id="regConfirm" class="form-control mb-3" placeholder="Confirm Password" required>
          <button class="btn btn-primary w-100">Register</button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <script>
    $.ajaxSetup({
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function register(e){
      e.preventDefault();

      const name  = $('#regName').val().trim();
      const email = $('#regEmail').val().trim();
      const phone_number = $('#regPhone').val().trim();
      const pass  = $('#regPass').val();
      const confirm = $('#regConfirm').val();

      if(pass.length < 8) {
        alert('Password must be at least 8 characters long.');
        $('#regPass').focus();
        return;
      }

      if (pass !== confirm) {
        alert('Passwords do not match.');
        $('#regConfirm').focus();
        return;
      }

      $.ajax({
        url: 'api/register',
        type: 'POST',
        data: JSON.stringify({ name, email, phone_number, password: pass }),
        contentType: 'application/json',
        success: function(res){
          if(res.success){
            alert(res.message || 'Registered! Please login.');
            $('#regName,#regEmail,#regPhone,#regPass,#regConfirm').val('');
            $('[data-bs-target="#login"]').tab('show');
          } else {
            if (res.field == 'email') {
              alert(res.message || 'email already exist.');
            } else if (res.field == 'phone_number') {
              alert(res.message || 'phone number already exist.');
            }
            else
              alert(res.message || 'Registration failed.');
          }
        },
        error: function(xhr){
          alert('Server error: ' + xhr.status);
        }
      });
    }

    function login(e) {
      e.preventDefault();

      const identifier = $('#loginIdentifier').val().trim();
      const pass = $('#loginPass').val();
      console.log(identifier, pass);
      // Always send as top-level keys: account + password
      const payload = {
        account: identifier,
        password: pass
      };

      $.ajax({
      url: '/api/login',
      type: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      dataType: 'json',
      xhrFields: { withCredentials: true },
      success: function(res) {
        if (res.success) {
          // store the entire user object as JSON
          sessionStorage.setItem('user', JSON.stringify(res.user));
          console.log('Logged in user:', res.user);
          window.location.href = '/dashboard';
        } else {
          alert('Invalid credentials');
        }
      },
      error: function(xhr) {
        alert('Server error: ' + xhr.status);
      }
    });
    }
  </script>
</body>
</html>
