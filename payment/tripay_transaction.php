<?php
// tripay_create_transaction.php

$apiKey       = 'API_KEY_KAMU';
$privateKey   = 'PRIVATE_KEY_KAMU';
$merchantCode = 'MERCHANT_CODE_KAMU';
$merchantRef  = 'INV' . time();
$amount       = 20000; // jumlah total transaksi

// Data transaksi
$data = [
    'method'         => 'BRIVA', // kode channel pembayaran (cek di dokumentasi Tripay)
    'merchant_ref'   => $merchantRef,
    'amount'         => $amount,
    'customer_name'  => 'Stassy Zefanya',
    'customer_email' => 'stassy@example.com',
    'order_items'    => [
        [
            'sku'         => 'MENU001',
            'name'        => 'Milk Tea',
            'price'       => 20000,
            'quantity'    => 1
        ]
    ],
    'callback_url'   => 'https://abcd1234.ngrok.io/tripay_callback.php', // ganti dengan URL ngrok kamu
    'return_url'     => 'https://abcd1234.ngrok.io/success.php',         // halaman sukses
    'expired_time'   => (time() + (24 * 60 * 60)), // 24 jam
    'signature'      => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey)
];

// CURL request ke Tripay
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_FRESH_CONNECT  => true,
    CURLOPT_URL            => 'https://tripay.co.id/api/transaction/create',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
    CURLOPT_FAILONERROR    => false,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
]);

$response = curl_exec($curl);
curl_close($curl);

echo '<pre>';
print_r(json_decode($response, true));
echo '</pre>';
?>
