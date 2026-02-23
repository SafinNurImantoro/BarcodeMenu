<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$privateKey = TRIPAY_PRIVATE_KEY;

if (empty($privateKey)) {
    http_response_code(500);
    exit('Tripay not configured');
}

// Ambil raw JSON
$json = file_get_contents("php://input");

// Ambil signature dari header
$callbackSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';

// Validasi signature
$validSignature = hash_hmac('sha256', $json, $privateKey);

if ($callbackSignature !== $validSignature) {
    http_response_code(403);
    exit('Invalid signature');
}

// Decode JSON
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Proses pembayaran berhasil
if ($data['status'] === 'PAID') {

    $merchantRef = $data['merchant_ref'];
    $paidStatus = 'paid';

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE merchant_ref = ?");
    $stmt->bind_param("ss", $paidStatus, $merchantRef);
    $stmt->execute();
    $stmt->close();
}

// Simpan log untuk debug
file_put_contents(
    __DIR__ . '/callback_log.txt',
    $json . PHP_EOL,
    FILE_APPEND
);

http_response_code(200);
echo json_encode(['success' => true]);