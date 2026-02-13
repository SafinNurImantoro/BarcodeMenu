<?php
// tripay_callback.php
$privateKey = 'PRIVATE_KEY_KAMU';

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
