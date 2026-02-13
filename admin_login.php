<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TEAZZI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-minimal.css?v=20260213a">
</head>
<body class="login-page">
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
    $debug_mode = (APP_ENV === 'development');

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Validate inputs first
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi';
        } else {
            $loginResult = loginAdmin($username, $password);

            if ($loginResult['success']) {
                // Make sure session is saved before redirect
                session_write_close();

                // Redirect to dashboard
                header('Location: dashboard.php', true, 302);
                exit;
            } else {
                $error = $loginResult['message'];
            }
        }
    }
    ?>

    <main class="login-container">
        <div class="login-content">
            <div class="surface-card login-card">
                <div class="login-header">
                    <h1 class="page-title">TEAZZI Admin</h1>
                    <p class="page-subtitle">Masuk untuk mengelola dashboard.</p>
                    <?php if ($debug_mode): ?>
                        <span class="badge text-bg-warning ms-2">Development</span>
                    <?php endif; ?>
                </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php if ($debug_mode): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <small><strong>Debug:</strong> <?= htmlspecialchars($error) ?> | <?= isset($_POST['username']) ? 'username submitted' : 'no username' ?></small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($timeout): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                Sesi Anda telah habis. Silakan login kembali.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($logout): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                Anda telah logout.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary w-100 login-submit">Login</button>
        </form>

        <div class="login-demo">
            <small><strong>Testing:</strong> admin / admin123</small>
        </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
