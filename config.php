<?php
/**
 * Configuration file
 * Load environment variables from .env file
 */

// Load .env file
function loadEnv($file = __DIR__ . '/.env') {
    if (!file_exists($file)) {
        throw new Exception("Configuration file (.env) not found");
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') === false) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^"(.+)"$/', $value, $match)) {
            $value = $match[1];
        } elseif (preg_match('/^\'(.+)\'$/', $value, $match)) {
            $value = $match[1];
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Load environment variables
loadEnv();

// Database Configuration from .env
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'cafemenu');

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
