<?php
/**
 * Configuration file
 * Load environment variables from .env file
 */

// Load .env file
function loadEnv($file = __DIR__ . '/.env') {
    if (!file_exists($file)) {
        error_log("CRITICAL: Configuration file (.env) not found at: " . $file);
        throw new Exception("Configuration file (.env) not found");
    }

    $env_file_readable = is_readable($file);
    if (!$env_file_readable) {
        error_log("CRITICAL: Configuration file (.env) exists but is not readable");
        throw new Exception("Configuration file (.env) is not readable");
    }

    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("CRITICAL: Failed to read configuration file (.env)");
        throw new Exception("Failed to read configuration file (.env)");
    }

    $loaded_count = 0;
    foreach ($lines as $line) {
        // Skip empty lines and comments
        $trimmed = trim($line);
        if (empty($trimmed) || substr($trimmed, 0, 1) === '#') {
            continue;
        }

        // Parse KEY=VALUE (only first = is delimiter)
        if (strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove surrounding quotes if present (handle both single and double quotes)
        $first_char = substr($value, 0, 1);
        $last_char = substr($value, -1);
        
        if (($first_char === '"' && $last_char === '"') || ($first_char === "'" && $last_char === "'")) {
            $value = substr($value, 1, -1);
        }

        // Set both putenv and $_ENV
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $loaded_count++;
        
        // Log critical vars for debugging
        if (strpos($key, 'ADMIN') !== false || strpos($key, 'PASSWORD') !== false) {
            error_log("Loaded env var: $key (length: " . strlen($value) . ")");
        }
    }
    
    error_log("Environment configuration loaded successfully: $loaded_count variables");
}

// Load environment variables
loadEnv();

// Database Configuration from .env
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'cafemenu');

// ============================================
// ADMIN CREDENTIALS & SECURITY CONFIG
// ============================================
$admin_username_env = getenv('ADMIN_USERNAME');
$admin_hash_env = getenv('ADMIN_PASSWORD_HASH');

if (empty($admin_username_env) || empty($admin_hash_env)) {
    error_log("CRITICAL: ADMIN_USERNAME or ADMIN_PASSWORD_HASH not set in .env");
}

define('ADMIN_USERNAME', $admin_username_env ?: 'admin');
define('ADMIN_PASSWORD_HASH', $admin_hash_env ?: '$2y$10$QhPNvEv96X/KYQ/LlvQkzOKg7cztCYlf3KJfqcL3Gc0cXPQD7rVJW');

// Security Settings
define('SESSION_TIMEOUT', (int)(getenv('SESSION_TIMEOUT') ?: 900)); // 15 minutes
define('MAX_LOGIN_ATTEMPTS', (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 5));
define('LOGIN_ATTEMPT_TIMEOUT', (int)(getenv('LOGIN_ATTEMPT_TIMEOUT') ?: 120)); // 2 minutes

// Features
define('ENABLE_AUDIT_LOG', getenv('ENABLE_AUDIT_LOG') === 'true');
define('ENABLE_RATE_LIMITING', getenv('ENABLE_RATE_LIMITING') === 'true');
define('CSRF_TOKEN_ENABLED', getenv('CSRF_TOKEN_ENABLED') === 'true');

// Admin email
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@teazzi.id');

// Payment Gateway Configuration - Tripay Only
define('TRIPAY_PRIVATE_KEY', getenv('TRIPAY_PRIVATE_KEY') ?: '');
define('TRIPAY_MERCHANT_CODE', getenv('TRIPAY_MERCHANT_CODE') ?: '');
define('TRIPAY_API_KEY', getenv('TRIPAY_API_KEY') ?: '');

// Application Configuration
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/BARCODEMENU');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

?>
