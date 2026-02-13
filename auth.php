<?php
/**
 * Admin Authentication Module
 * Handles session management and admin login/logout
 */

session_start();

// Default admin credentials (CHANGE THIS IN PRODUCTION!)
const ADMIN_USERNAME = 'admin';
// Password hash for 'admin123' - Use simpler approach for reliability
// To change password, use: password_hash('your_password', PASSWORD_DEFAULT)
const ADMIN_PASSWORD = 'admin123'; // For now, simple plaintext comparison
const ADMIN_PASSWORD_HASH = '$2y$10$QhPNvEv96X/KYQ/LlvQkzOKg7cztCYlf3KJfqcL3Gc0cXPQD7rVJW'; // Correct hash for 'admin123'

/**
 * Check if user is authenticated as admin
 * Redirects to login page if not
 */
function requireAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

/**
 * Check if admin is currently logged in
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true;
}

/**
 * Get current admin username
 * @return string|null
 */
function getAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

/**
 * Login admin with username and password
 * @param string $username
 * @param string $password
 * @return array Result with 'success' and 'message'
 */
function loginAdmin($username, $password) {
    $result = ['success' => false, 'message' => ''];
    
    // Sanitize inputs
    $username = trim($username ?? '');
    $password = trim($password ?? '');
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $result['message'] = 'Username dan password harus diisi';
        return $result;
    }
    
    // Check credentials - Use both methods for compatibility
    $username_match = ($username === ADMIN_USERNAME);
    
    // Try password_verify first (for hashed passwords)
    $password_match = password_verify($password, ADMIN_PASSWORD_HASH);
    
    // Fallback to plaintext for development (REMOVE IN PRODUCTION!)
    if (!$password_match && defined('ADMIN_PASSWORD')) {
        $password_match = ($password === ADMIN_PASSWORD);
    }
    
    if ($username_match && $password_match) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Force session save
        session_write_close();
        session_start();
        
        // Log success
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('Admin login successful', 'success', $username);
        }
        
        $result['success'] = true;
        $result['message'] = 'Login berhasil!';
        return $result;
    } else {
        // Log failed attempt
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('Failed admin login attempt', 'warning', $username);
        }
        
        $result['message'] = 'Username atau password salah';
        return $result;
    }
}

/**
 * Logout admin
 */
function logoutAdmin() {
    if (isset($_SESSION['admin_username'])) {
        logSecurityEvent('Admin logged out', 'success', $_SESSION['admin_username']);
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login
    header('Location: admin_login.php?logout=1');
    exit;
}

/**
 * Check and enforce session timeout (15 minutes)
 */
function checkSessionTimeout() {
    $timeout = 15 * 60; // 15 minutes
    
    // Only check if user is logged in
    if (!isAdminLoggedIn()) {
        return;
    }
    
    // Check last activity
    if (isset($_SESSION['last_activity'])) {
        $inactivity = time() - $_SESSION['last_activity'];
        
        if ($inactivity > $timeout) {
            // Session expired - destroy and redirect
            @logSecurityEvent('Session timeout', 'info', $_SESSION['admin_username'] ?? 'unknown');
            session_destroy();
            header('Location: admin_login.php?timeout=1');
            exit;
        }
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}

/**
 * Log security events
 * @param string $event
 * @param string $level (info, warning, danger, success)
 * @param string $details
 */
function logSecurityEvent($event, $level = 'info', $details = '') {
    try {
        $log_dir = __DIR__ . '/logs';
        
        // Check and create logs directory if doesn't exist
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        // Only log if directory is writable
        if (!is_writable($log_dir)) {
            error_log("Log directory not writable: " . $log_dir);
            return;
        }
        
        $log_file = $log_dir . '/admin_security.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100);
        
        $log_entry = "[{$timestamp}] IP: {$ip_address} | LEVEL: {$level} | EVENT: {$event}";
        if (!empty($details)) {
            $log_entry .= " | DETAILS: {$details}";
        }
        $log_entry .= " | UA: {$user_agent}\n";
        
        // Suppress warnings if write fails
        @error_log($log_entry, 3, $log_file);
    } catch (Exception $e) {
        // Silent fail - don't break login process
        error_log("Security logging error: " . $e->getMessage());
    }
}

/**
 * Check if it's the first login (need to change password)
 * @return bool
 */
function isFirstLogin() {
    return !isset($_SESSION['admin_loggedin']) || !isset($_SESSION['login_time']);
}

// Initialize security checks for logged-in users
if (isAdminLoggedIn()) {
    checkSessionTimeout();
}
?>
