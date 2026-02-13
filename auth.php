<?php
/**
 * Admin Authentication Module
 * Handles session management and admin login/logout
 */

// Configure session before starting
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Ensure session directory is writable
$session_save_path = session_save_path();
if ($session_save_path && !is_writable($session_save_path)) {
    // Try to create a custom session path
    $custom_session_path = __DIR__ . '/session_data';
    if (!is_dir($custom_session_path)) {
        @mkdir($custom_session_path, 0755, true);
    }
    if (is_dir($custom_session_path) && is_writable($custom_session_path)) {
        session_save_path($custom_session_path);
    }
}

session_start();

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
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
 * Uses database for credentials
 * @param string $username
 * @param string $password
 * @return array Result with 'success' and 'message'
 */
function loginAdmin($username, $password) {
    global $conn;
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
        $timeout_minutes = (int)(LOGIN_ATTEMPT_TIMEOUT / 60);
        $result['message'] = 'Terlalu banyak percobaan login gagal. Silakan coba dalam ' . $timeout_minutes . ' menit.';
        if (function_exists('logSecurityEvent')) {
            @logSecurityEvent('Rate limit exceeded for login', 'warning', $username);
        }
        return $result;
    }
    
    try {
        // Query database for admin user
        $sql = "SELECT id, username, password, email, role, is_active FROM admins WHERE username = ? AND is_active = 1";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare error: " . $conn->error);
            $result['message'] = 'Terjadi kesalahan sistem';
            recordFailedAttempt($username);
            return $result;
        }
        
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            error_log("DB execute error: " . $stmt->error);
            $result['message'] = 'Terjadi kesalahan sistem';
            recordFailedAttempt($username);
            return $result;
        }
        
        $db_result = $stmt->get_result();
        
        // Check if user exists
        if ($db_result->num_rows === 0) {
            error_log("Login failed: User not found - $username");
            recordFailedAttempt($username);
            
            try {
                if (function_exists('logSecurityEvent')) {
                    @logSecurityEvent('Failed admin login attempt - user not found', 'warning', $username);
                }
            } catch (Exception $e) {
                error_log("Audit log error: " . $e->getMessage());
            }
            
            $result['message'] = 'Username atau password salah';
            return $result;
        }
        
        $admin = $db_result->fetch_assoc();
        $stmt->close();
        
        // Verify password
        $password_match = password_verify($password, $admin['password']);
        
        if (!$password_match) {
            error_log("Login failed: Password mismatch for user - $username");
            recordFailedAttempt($username);
            
            try {
                if (function_exists('logSecurityEvent')) {
                    @logSecurityEvent('Failed admin login attempt - wrong password', 'warning', $username);
                }
            } catch (Exception $e) {
                error_log("Audit log error: " . $e->getMessage());
            }
            
            $result['message'] = 'Username atau password salah';
            return $result;
        }
        
        // Password correct - login successful
        // Clear rate limit on successful login
        $rate_limit_key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR'] ?? '');
        unset($_SESSION[$rate_limit_key]);
        
        // Set session variables
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        @session_regenerate_id(true);
        
        // Update last_login in database
        $update_sql = "UPDATE admins SET last_login = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param("i", $admin['id']);
            @$update_stmt->execute();
            $update_stmt->close();
        }
        
        // Try to log success
        try {
            if (function_exists('logSecurityEvent')) {
                @logSecurityEvent('Admin login successful', 'success', $username);
            }
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
        
        $result['success'] = true;
        $result['message'] = 'Login berhasil!';
        error_log("Admin login successful: $username");
        return $result;
        
    } catch (Exception $e) {
        error_log("Login exception: " . $e->getMessage());
        recordFailedAttempt($username);
        $result['message'] = 'Terjadi kesalahan sistem';
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
