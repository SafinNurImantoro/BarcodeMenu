<?php
/**
 * Debug Login Issues
 */

echo "=== ADMIN LOGIN DEBUG ===\n\n";

// Check 1: Password hash test
echo "1. PASSWORD HASH TEST:\n";
$test_password = 'admin123';
$stored_hash = '$2y$10$QT.q3iM5d6KsuNfbDa8YJuHDXvWuDfKmXvvzrPWYYvV9dLBJYpMGG';

echo "Test password: " . $test_password . "\n";
echo "Stored hash: " . $stored_hash . "\n";
echo "password_verify result: " . (password_verify($test_password, $stored_hash) ? "TRUE ✅" : "FALSE ❌") . "\n";

// Generate new hash for testing
echo "\nGenerating new hash for 'admin123':\n";
$new_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "New hash: " . $new_hash . "\n";
echo "Verify with new hash: " . (password_verify($test_password, $new_hash) ? "TRUE ✅" : "FALSE ❌") . "\n";

// Check 2: Session test
echo "\n2. SESSION TEST:\n";
session_start();
$_SESSION['test'] = 'working';
echo "Session started: " . (isset($_SESSION['test']) ? "YES ✅" : "NO ❌") . "\n";
echo "Session ID: " . session_id() . "\n";

// Check 3: Directory permissions
echo "\n3. DIRECTORY PERMISSIONS:\n";
$logs_dir = __DIR__ . '/logs';
if (is_dir($logs_dir)) {
    echo "Logs folder: EXISTS ✅\n";
    echo "Writable: " . (is_writable($logs_dir) ? "YES ✅" : "NO ❌") . "\n";
} else {
    echo "Logs folder: NOT EXISTS ❌\n";
    echo "Creating logs folder...\n";
    if (mkdir($logs_dir, 0755, true)) {
        echo "Created: YES ✅\n";
    } else {
        echo "Created: NO ❌\n";
    }
}

// Check 4: Database connection
echo "\n4. DATABASE CONNECTION:\n";
require 'db.php';
if ($conn) {
    echo "Database connected: YES ✅\n";
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Orders count: " . $row['count'] . "\n";
    }
} else {
    echo "Database connected: NO ❌\n";
}

// Check 5: auth.php functions
echo "\n5. AUTH.PHP FUNCTIONS:\n";
require 'auth.php';
echo "requireAuth function: " . (function_exists('requireAuth') ? "EXISTS ✅" : "NOT FOUND ❌") . "\n";
echo "isAdminLoggedIn function: " . (function_exists('isAdminLoggedIn') ? "EXISTS ✅" : "NOT FOUND ❌") . "\n";
echo "loginAdmin function: " . (function_exists('loginAdmin') ? "EXISTS ✅" : "NOT FOUND ❌") . "\n";

// Check 6: Test login function
echo "\n6. TEST LOGIN FUNCTION:\n";
$test_login = loginAdmin('admin', 'admin123');
echo "Login result: " . ($test_login['success'] ? "SUCCESS ✅" : "FAILED ❌") . "\n";
echo "Message: " . $test_login['message'] . "\n";

// Check 7: Session after login
echo "\n7. SESSION AFTER LOGIN:\n";
echo "Session admin_loggedin: " . (isset($_SESSION['admin_loggedin']) ? "SET ✅" : "NOT SET ❌") . "\n";
echo "Session admin_username: " . (isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] . " ✅" : "NOT SET ❌") . "\n";

echo "\n=== END DEBUG ===\n";
?>
