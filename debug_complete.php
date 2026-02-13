<?php
/**
 * Complete Login Debug Script
 * Diagnose semua masalah login
 */

// Test 1: .env file exists and readable
echo "=== TEST 1: .env File ===\n";
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    echo "✅ .env file exists\n";
    if (is_readable($env_file)) {
        echo "✅ .env file is readable\n";
    } else {
        echo "❌ .env file is NOT readable\n";
    }
} else {
    echo "❌ .env file NOT found at: $env_file\n";
}

// Test 2: Load config
echo "\n=== TEST 2: Load Configuration ===\n";
try {
    require 'config.php';
    echo "✅ config.php loaded successfully\n";
    echo "  - ADMIN_USERNAME: " . (defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'NOT DEFINED') . "\n";
    echo "  - ADMIN_PASSWORD_HASH: " . (defined('ADMIN_PASSWORD_HASH') ? substr(ADMIN_PASSWORD_HASH, 0, 20) . '...' : 'NOT DEFINED') . "\n";
} catch (Exception $e) {
    echo "❌ Error loading config.php: " . $e->getMessage() . "\n";
    die();
}

// Test 3: Password verification
echo "\n=== TEST 3: Password Verification ===\n";
$test_password = 'admin123';
$test_hash = ADMIN_PASSWORD_HASH;

echo "Password to test: " . $test_password . "\n";
echo "Hash from config: " . $test_hash . "\n";
echo "Hash format: " . (substr($test_hash, 0, 4) === '$2y$' ? 'bcrypt (valid)' : 'INVALID') . "\n";

$verify_result = password_verify($test_password, $test_hash);
echo "password_verify() result: " . ($verify_result ? 'TRUE ✅' : 'FALSE ❌') . "\n";

if (!$verify_result) {
    echo "\n⚠️ PASSWORD MISMATCH!\n";
    echo "Generating new hash for 'admin123':\n";
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    echo $new_hash . "\n";
    echo "\nUpdate your .env file with this hash:\n";
    echo "ADMIN_PASSWORD_HASH=" . $new_hash . "\n";
}

// Test 4: Session configuration
echo "\n=== TEST 4: Session Configuration ===\n";
echo "Session save path: " . session_save_path() . "\n";
echo "Is writable: " . (is_writable(session_save_path()) ? 'YES ✅' : 'NO ❌') . "\n";

$custom_session = __DIR__ . '/session_data';
echo "Custom session folder: " . $custom_session . "\n";
echo "Exists: " . (is_dir($custom_session) ? 'YES ✅' : 'NO ❌') . "\n";
echo "Writable: " . (is_writable($custom_session) ? 'YES ✅' : 'NO ❌') . "\n";

// Test 5: Database connection (optional)
echo "\n=== TEST 5: Database Configuration ===\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";

// Try to connect
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "\n";
    } else {
        echo "✅ Database connection successful\n";
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}

// Test 6: Simulate login
echo "\n=== TEST 6: Simulate Login ===\n";
echo "Testing loginAdmin('admin', 'admin123')...\n";

try {
    require 'auth.php';
    
    // Start fresh session for test
    $_SESSION = [];
    
    $result = loginAdmin('admin', 'admin123');
    echo "Result: " . json_encode($result) . "\n";
    
    if ($result['success']) {
        echo "✅ LOGIN SUCCESS!\n";
        echo "Session variables set:\n";
        echo "  - admin_loggedin: " . ($_SESSION['admin_loggedin'] ? 'YES' : 'NO') . "\n";
        echo "  - admin_username: " . ($_SESSION['admin_username'] ?? 'NOT SET') . "\n";
    } else {
        echo "❌ LOGIN FAILED: " . $result['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error during login simulation: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
