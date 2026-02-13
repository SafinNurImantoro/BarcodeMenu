<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TEAZZI Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #080e83 0%, #1a1f5c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #080e83;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #080e83;
            box-shadow: 0 0 0 0.2rem rgba(8, 14, 131, 0.25);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .btn-login {
            background: #080e83;
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-login:hover {
            background: #0a0f6a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 14, 131, 0.4);
            color: white;
            text-decoration: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .remember-me label {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 0.85rem;
        }

        .disclaimer {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #666;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-badge.development {
            background-color: #ffc107;
            color: #333;
        }

        .icon-lock {
            font-size: 3rem;
            color: #080e83;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php
    require 'auth.php';

    // Check if user already logged in
    if (isAdminLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }

    $error = '';
    $success = '';
    $timeout = isset($_GET['timeout']);
    $logout = isset($_GET['logout']);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $loginResult = loginAdmin($username, $password);

        if ($loginResult['success']) {
            // Force session save and redirect
            session_write_close();
            // Use proper Location header redirect
            header('Location: dashboard.php', true, 302);
            exit;
        } else {
            $error = $loginResult['message'];
        }
    }
    ?>

    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="icon-lock">🔐</div>
            <h2>ADMIN PANEL</h2>
            <p>TEAZZI Cafe Menu System</p>
            <span class="status-badge development">🟠 Development</span>
        </div>

        <!-- Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                ✅ <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                ⚠️ <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($timeout): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                ⏱️ Sesi Anda telah habis. Silakan login kembali.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($logout): ?>
            <div class="alert alert-info alert-dismissible fade show">
                👋 Anda telah berhasil logout.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">👤 Username</label>
                <input type="text" name="username" class="form-control" 
                       placeholder="Masukkan username" 
                       required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label">🔑 Password</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="Masukkan password" 
                       required autocomplete="current-password">
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="btn btn-login">🚀 Login</button>
        </form>

        <!-- Divider -->
        <div class="divider">Demo Credentials</div>

        <!-- Test Credentials Info -->
        <div class="disclaimer">
            <strong>Untuk Testing:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code><br>
            <small style="color: #999;">⚠️ Ubah password di production!</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
