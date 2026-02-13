<?php
// Load configuration
require_once __DIR__ . '/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error but don't expose details to user
    error_log("Database Connection Error: " . $conn->connect_error);
    die("Terjadi kesalahan pada koneksi database. Silakan coba lagi nanti.");
}

// Set charset
$conn->set_charset("utf8mb4");

// Enable error reporting in development
if (APP_ENV === 'development') {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}
?>
