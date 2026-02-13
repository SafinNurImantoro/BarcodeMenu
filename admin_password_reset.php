<?php
/**
 * Admin Password Reset Helper
 * Helper untuk test login dan reset password jika diperlukan
 * HANYA untuk development, hapus di production!
 */

require 'config.php';
require 'auth.php';

// Pastikan hanya accessible di development
if (APP_ENV !== 'development') {
    die('This tool is only available in development mode');
}

$message = '';
$password_to_test = 'admin123';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_password') {
        $test_result = password_verify($password_to_test, ADMIN_PASSWORD_HASH);
        $message = $test_result 
            ? '✅ Password "admin123" MATCHES the hash in .env!' 
            : '❌ Password "admin123" DOES NOT MATCH the hash in .env!';
    } elseif ($action === 'generate_hash') {
        $new_password = $_POST['new_password'] ?? 'admin123';
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $message = '✅ New password hash generated:<br><code style="background: #f0f0f0; padding: 10px; display: block; word-break: break-all;">' . htmlspecialchars($new_hash) . '</code>';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset Helper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #080e83;
            margin-bottom: 30px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #080e83;
            padding: 15px;
            margin-bottom: 20px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Admin Password Reset Helper</h1>
        
        <div class="alert alert-warning">
            ⚠️ <strong>DEVELOPMENT ONLY!</strong> Hapus file ini di production!
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h5>Current Configuration:</h5>
            <ul style="margin-bottom: 0;">
                <li><strong>Username:</strong> <code><?= htmlspecialchars(ADMIN_USERNAME) ?></code></li>
                <li><strong>Password Hash:</strong> <code style="font-size: 0.8em;"><?= htmlspecialchars(ADMIN_PASSWORD_HASH) ?></code></li>
                <li><strong>Environment:</strong> <code><?= htmlspecialchars(APP_ENV) ?></code></li>
            </ul>
        </div>

        <h5>Test Login Password</h5>
        <form method="POST" class="mb-4">
            <input type="hidden" name="action" value="verify_password">
            <button type="submit" class="btn btn-primary">Verify "admin123" Password</button>
        </form>

        <hr>

        <h5>Generate New Password Hash</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">New Password:</label>
                <input type="text" name="new_password" class="form-control" value="admin123" required>
                <small class="form-text text-muted">Default: admin123</small>
            </div>
            <input type="hidden" name="action" value="generate_hash">
            <button type="submit" class="btn btn-warning">Generate Hash</button>
        </form>

        <hr>

        <h5>Manual Fix Instructions:</h5>
        <ol>
            <li>Copy the generated hash from above</li>
            <li>Edit <code>.env</code> file</li>
            <li>Replace the <code>ADMIN_PASSWORD_HASH=</code> value with the new hash</li>
            <li>Save the file</li>
            <li>Try logging in with username <code>admin</code> and the password you used</li>
            <li>Delete this file after successful login</li>
        </ol>

        <div class="alert alert-info mt-4">
            <strong>Quick Test:</strong><br>
            <strong>Username:</strong> admin<br>
            <strong>Password:</strong> admin123
        </div>
    </div>
</body>
</html>
