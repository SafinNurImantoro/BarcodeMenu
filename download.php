<?php
require_once __DIR__ . '/config.php';

// Get filename from URL parameter
$filename = $_GET['file'] ?? '';

// Validate filename - prevent directory traversal
if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    http_response_code(400);
    die('Invalid filename');
}

// Only allow PDF files
if (!preg_match('/^invoice_\d+_[a-f0-9]+\.pdf$/', $filename)) {
    http_response_code(400);
    die('Invalid file format');
}

// Full file path
$filePath = __DIR__ . '/invoices/' . $filename;

// Verify file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Verify file is actually in invoices directory (prevent traversal)
$realPath = realpath($filePath);
$invoicesPath = realpath(__DIR__ . '/invoices');

if ($realPath === false || strpos($realPath, $invoicesPath) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Serve the file
readfile($filePath);
exit;
?>
