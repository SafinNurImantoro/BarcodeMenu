<?php
// Load configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Get private key from environment
$privateKey = TRIPAY_PRIVATE_KEY;

if (empty($privateKey)) {
    http_response_code(500);
    die('Payment gateway not configured');
}

// Ambil data callback
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Validasi signature
$signature = hash_hmac('sha256', $data['merchant_ref'].$data['reference'].$data['status'], $privateKey);

if ($signature !== $data['signature']) {
    http_response_code(403);
    exit('Invalid signature');
}

// Jika valid, simpan status ke database
if ($data['status'] == 'PAID') {
    // update status order ke "LUNAS"
    // contoh:
    // mysqli_query($conn, "UPDATE orders SET status='paid' WHERE reference='".$data['reference']."'");
}

file_put_contents('callback_log.txt', $json . PHP_EOL, FILE_APPEND);
http_response_code(200);
echo 'Callback received';
?>
