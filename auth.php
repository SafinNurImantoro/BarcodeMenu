<?php
/**
 * Admin Authentication Module
 * Handles session management and admin login/logout
 */

session_start();

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit_log.php';

// Import credentials from environment (config.php)
// These are now defined as constants from .env file

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
 * Check if user has exceeded login attempts
 * @param string $username
 * @return bool
 */
function isRateLimited($username) {
    if (!ENABLE_RATE_LIMITING) {
        return false;
    }
    
    $rate_limit_key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR'] ?? '');
    $attempts_data = $_SESSION[$rate_limit_key] ?? null;
    
    // Check if there are recorded attempts
    if (!$attempts_data) {
        return false;
    }
    
    $last_attempt_time = $attempts_data['time'] ?? 0;
    $attempt_count = $attempts_data['count'] ?? 0;
    $current_time = time();
    
    // If timeout period has passed, reset counter
    if ($current_time - $last_attempt_time >= LOGIN_ATTEMPT_TIMEOUT) {
        unset($_SESSION[$rate_limit_key]);
        return false;
    }
    
    // If exceeded max attempts, still blocked
    return $attempt_count >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Record failed login attempt
 * @param string $username
 */
function recordFailedAttempt($username) {
    if (!ENABLE_RATE_LIMITING) {
        return;
    }
    
    $rate_limit_key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR'] ?? '');
    $attempts_data = $_SESSION[$rate_limit_key] ?? null;
    
    if (!$attempts_data) {
        $_SESSION[$rate_limit_key] = [
            'count' => 1,
            'time' => time()
        ];
    } else {
        $_SESSION[$rate_limit_key]['count']++;
        $_SESSION[$rate_limit_key]['time'] = time();
    }
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
    
    // Check rate limiting
    if (isRateLimited($username)) {
        $result['message'] = 'Terlalu banyak percobaan login gagal. Silakan coba dalam ' . (LOGIN_ATTEMPT_TIMEOUT / 60) . ' menit.';
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('Rate limit exceeded for login', 'warning', $username);
        }
        return $result;
    }
    
    // Check credentials
    $username_match = ($username === ADMIN_USERNAME);
    $password_match = password_verify($password, ADMIN_PASSWORD_HASH);
    
    if ($username_match && $password_match) {
        // Clear rate limit on successful login
        $rate_limit_key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR'] ?? '');
        unset($_SESSION[$rate_limit_key]);
        
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
        // Record failed attempt for rate limiting
        recordFailedAttempt($username);
        
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
 * Check and enforce session timeout
 */
function checkSessionTimeout() {
    $timeout = SESSION_TIMEOUT; // Use config value instead of hardcoded 15 minutes
    
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


// Initialize security checks for logged-in users
if (isAdminLoggedIn()) {
    checkSessionTimeout();
}
?>
