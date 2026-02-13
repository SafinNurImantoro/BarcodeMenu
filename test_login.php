<?php
/**
 * Simple Login Test Page - Database Version
 * Go to: http://localhost/BARCODEMENU/test_login.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_loaded = false;
$db_connected = false;
$admin_exists = false;
$test_result = null;

try {
    require 'config.php';
    $config_loaded = true;
    
    require 'db.php';
    if ($conn && !$conn->connect_error) {
        $db_connected = true;
    }
    
    require 'auth.php';
} catch (Exception $e) {
    echo "ERROR: " . htmlspecialchars($e->getMessage());
    die();
}

// Check if admin exists in database
if ($db_connected) {
    $check_sql = "SELECT id, username, email FROM admins WHERE username = 'admin'";
    $check_result = $conn->query($check_sql);
    $admin_exists = ($check_result && $check_result->num_rows > 0);
}

// Test login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $test_result = loginAdmin($username, $password);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login Test - Database Version</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 30px;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-info { color: #0056b3; }
        .info-block {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 0.9em;
        }
        .step {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e0e0e0;
        }
        .step:last-child {
            border-bottom: none;
        }
        .step-number {
            display: inline-block;
            background: #080e83;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        h3 {
            color: #080e83;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Admin Login Test - Database Version</h1>
        
        <div class="step">
            <h3><span class="step-number">1</span>Configuration Status</h3>
            <ul class="list-unstyled">
                <li>
                    <strong>Environment:</strong> 
                    <span class="<?php echo APP_ENV === 'development' ? 'status-ok' : 'status-error'; ?>">
                        <?php echo htmlspecialchars(APP_ENV); ?>
                    </span>
                </li>
                <li>
                    <strong>Config Loaded:</strong> 
                    <span class="<?php echo $config_loaded ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $config_loaded ? '✅ YES' : '❌ NO'; ?>
                    </span>
                </li>
            </ul>
        </div>

        <div class="step">
            <h3><span class="step-number">2</span>Database Connection</h3>
            <ul class="list-unstyled">
                <li>
                    <strong>Connected:</strong> 
                    <span class="<?php echo $db_connected ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $db_connected ? '✅ YES' : '❌ NO'; ?>
                    </span>
                </li>
                <?php if ($db_connected): ?>
                    <li><strong>Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?></li>
                    <li><strong>Database:</strong> <?php echo htmlspecialchars(DB_NAME); ?></li>
                    <li><strong>User:</strong> <?php echo htmlspecialchars(DB_USER); ?></li>
                <?php else: ?>
                    <li class="text-danger">
                        <strong>Error:</strong> Cannot connect to database<br>
                        Check database credentials in .env file
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="step">
            <h3><span class="step-number">3</span>Admin User in Database</h3>
            <ul class="list-unstyled">
                <li>
                    <strong>Exists:</strong> 
                    <span class="<?php echo $admin_exists ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $admin_exists ? '✅ YES' : '❌ NO'; ?>
                    </span>
                </li>
                <?php if (!$admin_exists && $db_connected): ?>
                    <li class="text-warning">
                        <strong>⚠️ Action Required:</strong><br>
                        Admin user not found in database. Please run setup first:<br>
                        <a href="setup_admin.php" class="btn btn-sm btn-warning">Run Setup →</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="step">
            <h3><span class="step-number">4</span>Login Form Test</h3>
            <p>Test credentials: <strong>admin</strong> / <strong>admin123</strong></p>
            
            <?php if ($test_result): ?>
                <div class="alert <?php echo $test_result['success'] ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($test_result['message']); ?>
                </div>

                <?php if ($test_result['success']): ?>
                    <div class="alert alert-info">
                        <strong>✅ Session Variables Set:</strong><br>
                        <ul>
                            <li>admin_loggedin: <code><?php echo isset($_SESSION['admin_loggedin']) ? 'TRUE' : 'FALSE'; ?></code></li>
                            <li>admin_username: <code><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></code></li>
                            <li>admin_id: <code><?php echo htmlspecialchars($_SESSION['admin_id'] ?? ''); ?></code></li>
                            <li>login_time: <code><?php echo date('Y-m-d H:i:s', $_SESSION['login_time'] ?? 0); ?></code></li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" class="mt-3">
                <div class="mb-3">
                    <label class="form-label">Username:</label>
                    <input type="text" name="username" class="form-control" value="admin">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password:</label>
                    <input type="password" name="password" class="form-control" value="admin123">
                </div>
                <button type="submit" name="test_login" class="btn btn-primary">🚀 Test Login</button>
            </form>
        </div>

        <div class="step">
            <h3><span class="step-number">5</span>System Information</h3>
            <div class="info-block">
PHP Version: <?php echo phpversion(); ?>
Session Status: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'; ?>
Server OS: <?php echo php_uname('s'); ?>
            </div>
        </div>

        <div class="mt-4">
            <h4>Next Steps:</h4>
            <ul>
                <?php if (!$admin_exists && $db_connected): ?>
                    <li><a href="setup_admin.php" class="btn btn-warning">Step 1: Setup Admin in Database</a></li>
                    <li>Step 2: Come back here and test login</li>
                    <li>Step 3: Go to main login page</li>
                <?php elseif ($admin_exists && $db_connected && (!$test_result || !$test_result['success'])): ?>
                    <li>Admin user exists in database ✅</li>
                    <li>Test login with form above</li>
                    <li>Fix any errors that appear</li>
                    <li>Once successful, go to main login page</li>
                <?php elseif ($test_result && $test_result['success']): ?>
                    <li class="text-success">✅ Login test successful!</li>
                    <li><a href="admin_login.php" class="btn btn-success">Go to Main Login Page →</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <hr>
        <p class="text-muted">
            <a href="DATABASE_SETUP_GUIDE.md">📖 Read Database Setup Guide</a>
        </p>
    </div>
</body>
</html>

