<?php
require __DIR__ . '/vendor/autoload.php';
include 'db.php';
include 'helpers.php';

// Ambil data dari form
$menu = $_POST['menu'] ?? [];
$formData = [
    'table_no' => $_POST['table_no'] ?? '',
    'customer_name' => $_POST['customer_name'] ?? '',
    'customer_whatsapp' => $_POST['customer_whatsapp'] ?? '',
    'payment_method' => $_POST['payment_method'] ?? '',
    'notes' => $_POST['notes'] ?? ''
];

// Validate form data
$validation = validateOrderForm($formData);

if (!$validation['valid']) {
    // Log security event
    logSecurityEvent('Invalid form submission attempt: ' . json_encode($validation['errors']));

    // Redirect back with error
    $_SESSION['errors'] = $validation['errors'];
    header('Location: checkout.php');
    exit;
}

// Use validated data
$table_no = $validation['data']['table_no'];
$customer_name = $validation['data']['customer_name'];
$customer_whatsapp = $validation['data']['customer_whatsapp'];
$payment_method = $validation['data']['payment_method'];
$notes = $validation['data']['notes'] ?? '';

$total = 0;
foreach ($menu as $item) {
    $total += $item['subtotal'];
}

// Status pesanan (pastikan sesuai ENUM atau VARCHAR di DB)
$status = "pending"; // bisa diganti sesuai ENUM atau VARCHAR
$created_at = date('Y-m-d H:i:s');
// Buat unique order id/merchant_ref untuk Tripay
$order_id = "INV-" . time();

// Simpan ke tabel orders
$stmt = $conn->prepare("INSERT INTO orders
    (merchant_ref, table_no, customer_name, customer_whatsapp, item, qty, price, total, payment_method, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($menu as $item_name => $item) {
    $qty = (int)$item['qty'];
    $price = (float)($item['subtotal'] / $qty);
    $total_item = $qty * $price; // subtotal per item

    // Bind param sesuai tipe data: s=string, i=int, d=double
    $stmt->bind_param(
        "sssssdddsss",
        $order_id,
        $table_no,
        $customer_name,
        $customer_whatsapp,
        $item_name,
        $qty,
        $price,
        $total_item,
        $payment_method,
        $status,
        $created_at
    );

    $stmt->execute();
}
$stmt->close();

require_once __DIR__ . '/payment/tripay_transaction.php';

// Build order items untuk payload Tripay
$orderItems = [];
foreach ($menu as $item_name => $item) {
    $qty = (int)($item['qty'] ?? 0);
    if ($qty <= 0) {
        continue;
    }

    $price = (int)(($item['subtotal'] ?? 0) / $qty);
    $orderItems[] = [
        'sku' => $item_name,
        'name' => $item_name,
        'price' => $price,
        'quantity' => $qty
    ];
}

if ($payment_method === 'CASH') {
    echo "Silakan bayar langsung di kasir.";
    exit;
}

// Buat transaksi Tripay
$response = createTripayTransaction(
    $order_id,
    $total,
    $customer_name,
    $customer_whatsapp . "@email.com",
    $orderItems,
    $customer_whatsapp,
    $payment_method
);

if (isset($response['success']) && $response['success'] == true && isset($response['data']['checkout_url'])) {
    header("Location: " . $response['data']['checkout_url']);
    exit;
}

echo "<pre>";
print_r($response);
die("Gagal membuat transaksi");
