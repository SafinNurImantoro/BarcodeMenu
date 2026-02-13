<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup Helper - TEAZZI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #080e83 0%, #1a1f5c 100%);
            min-height: 100vh;
            padding: 30px 0;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            max-width: 800px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }

        .card-header {
            background: #080e83;
            color: white;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 20px;
        }

        .card-body {
            padding: 20px;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }

        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }

        code {
            color: #080e83;
            font-weight: 600;
        }

        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .danger-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .step-num {
            display: inline-block;
            background: #080e83;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: 700;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-center mb-4" style="color: white;">
        <h1>🔑 Admin Setup Helper</h1>
        <p>Diagnose dan setup admin authentication</p>
    </div>

    <?php
    // Check 1: Basic info
    echo '<div class="card">
        <div class="card-header">✅ System Information</div>
        <div class="card-body">
            <p><strong>PHP Version:</strong> ' . phpversion() . '</p>
            <p><strong>Server:</strong> ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</p>
            <p><strong>Current Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </div>';

    // Check 2: Password hash generation
    echo '<div class="card">
        <div class="card-header">🔐 Password Hash Generator</div>
        <div class="card-body">';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_hash'])) {
        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            echo '<div class="success-box">
                <strong>✅ Hash Generated Successfully!</strong><br><br>
                For password: <code>' . htmlspecialchars($password) . '</code><br><br>
                Copy this hash:<br>
                <pre>const ADMIN_PASSWORD_HASH = \'' . $hash . '\';</pre>
                <p><small>Paste this into <code>auth.php</code> line 11</small></p>
            </div>';
        }
    }

    echo '
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Enter password to hash:</label>
                    <input type="text" name="password" class="form-control" placeholder="e.g. admin123" required>
                </div>
                <button type="submit" name="generate_hash" class="btn btn-primary btn-custom">🔄 Generate Hash</button>
            </form>

            <div class="info-box mt-3">
                <strong>ℹ️ Current Credentials:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>admin123</code> (or as configured in auth.php)
            </div>
        </div>
    </div>';

    // Check 3: Password verification test
    echo '<div class="card">
        <div class="card-header">🧪 Test Login Credentials</div>
        <div class="card-body">';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
        $test_password = $_POST['test_password'] ?? '';
        $test_hash = $_POST['test_hash'] ?? '';

        echo '<div class="info-box">
            <strong>Testing verification...</strong><br>
            Password: <code>' . htmlspecialchars($test_password) . '</code><br>
            Hash: <code>' . substr($test_hash, 0, 20) . '...</code>
        </div>';

        $result = password_verify($test_password, $test_hash);
        if ($result) {
            echo '<div class="success-box">✅ Password matches! Login should work.</div>';
        } else {
            echo '<div class="danger-box">❌ Password does NOT match. Try again.</div>';
        }
    }

    echo '
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Password to test:</label>
                    <input type="text" name="test_password" class="form-control" placeholder="admin123" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hash to verify against:</label>
                    <textarea name="test_hash" class="form-control" rows="2" placeholder="Paste hash here" required></textarea>
                </div>
                <button type="submit" name="test_login" class="btn btn-warning btn-custom">🧪 Test Hash</button>
            </form>
        </div>
    </div>';

    // Check 4: File & Directory status
    echo '<div class="card">
        <div class="card-header">📁 File & Directory Status</div>
        <div class="card-body">';

    $checks = [
        'auth.php' => __DIR__ . '/auth.php',
        'admin_login.php' => __DIR__ . '/admin_login.php',
        'dashboard.php' => __DIR__ . '/dashboard.php',
        'logout.php' => __DIR__ . '/logout.php',
        'logs/ directory' => __DIR__ . '/logs',
    ];

    foreach ($checks as $name => $path) {
        $exists = file_exists($path);
        $icon = $exists ? '✅' : '❌';
        $status = $exists ? 'EXISTS' : 'MISSING';
        echo '<p><strong>' . $icon . ' ' . $name . ':</strong> ' . $status . '</p>';
    }

    echo '</div>
    </div>';

    // Check 5: Database status
    echo '<div class="card">
        <div class="card-header">🗄️ Database Connection Test</div>
        <div class="card-body">';

    try {
        require 'db.php';
        if ($conn) {
            echo '<div class="success-box">✅ Database connected successfully!</div>';

            // Test query
            $result = $conn->query("SELECT COUNT(*) as count FROM orders");
            if ($result) {
                $row = $result->fetch_assoc();
                echo '<p><strong>Orders in database:</strong> ' . $row['count'] . '</p>';
            }
        } else {
            echo '<div class="danger-box">❌ Database connection failed!</div>';
        }
    } catch (Exception $e) {
        echo '<div class="danger-box">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    echo '</div>
    </div>';

    // Check 6: Session test
    echo '<div class="card">
        <div class="card-header">💾 Session Status</div>
        <div class="card-body">';

    session_start();
    $_SESSION['test'] = 'working';
    $session_ok = isset($_SESSION['test']);

    echo '<p><strong>Session Status:</strong> ' . ($session_ok ? '✅ Working' : '❌ Failed') . '</p>';
    echo '<p><strong>Session ID:</strong> <code>' . session_id() . '</code></p>';
    echo '<p><strong>Session Save Path:</strong> ' . session_save_path() . '</p>';

    echo '</div>
    </div>';

    // Check 7: Quick actions
    echo '<div class="card">
        <div class="card-header">⚡ Quick Actions</div>
        <div class="card-body">';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'create_default_hash') {
            $default_hash = password_hash('admin123', PASSWORD_DEFAULT);
            echo '<div class="success-box">
                <strong>Default Hash (admin123):</strong><br>
                <pre>' . $default_hash . '</pre>
            </div>';
        }

        if ($action === 'clear_session') {
            session_destroy();
            echo '<div class="success-box">✅ Session cleared!</div>';
        }

        if ($action === 'create_logs') {
            @mkdir(__DIR__ . '/logs', 0755, true);
            echo '<div class="success-box">✅ Logs directory created!</div>';
        }
    }

    echo '
            <form method="POST" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <button type="submit" name="action" value="create_default_hash" class="btn btn-info btn-custom">Generate Default Hash</button>
                <button type="submit" name="action" value="clear_session" class="btn btn-warning btn-custom">Clear Session</button>
                <button type="submit" name="action" value="create_logs" class="btn btn-success btn-custom">Create Logs Folder</button>
                <a href="admin_login.php" class="btn btn-primary btn-custom" style="text-decoration: none;">Go to Login</a>
            </form>
        </div>
    </div>';

    // Final setup instructions
    echo '<div class="card">
        <div class="card-header">📝 Setup Instructions</div>
        <div class="card-body">
            <div class="info-box">
                <span class="step-num">1</span> <strong>Generate Password Hash</strong><br>
                Use the generator above to create a hash for your password
            </div>

            <div class="info-box">
                <span class="step-num">2</span> <strong>Update auth.php</strong><br>
                Replace line 11 in <code>auth.php</code> with your new hash
            </div>

            <div class="info-box">
                <span class="step-num">3</span> <strong>Test Login</strong><br>
                Go back to <a href="admin_login.php" style="color: #0c5460; font-weight: bold;">admin_login.php</a> and try to login
            </div>

            <div class="info-box">
                <span class="step-num">4</span> <strong>Verify Dashboard</strong><br>
                If login works, you should see the dashboard with order data
            </div>

            <div class="danger-box" style="margin-top: 20px;">
                <strong>⚠️ IMPORTANT:</strong> Delete this file (<code>admin_setup.php</code>) before going to production!
            </div>
        </div>
    </div>';
    ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
