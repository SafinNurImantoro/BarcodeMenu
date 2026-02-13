<?php
include 'db.php';
include 'midtrans_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $table = $_POST['table'];
  $total = $_POST['total'];

  // Buat order ID unik
  $orderId = 'ORDER-' . rand(10000, 99999);

  // Simpan ke database
  $stmt = $conn->prepare("INSERT INTO orders (order_id, table_no, total) VALUES (?, ?, ?)");
  $stmt->bind_param("ssi", $orderId, $table, $total);
  $stmt->execute();

  // Data transaksi ke Midtrans
  $payload = [
    'transaction_details' => [
      'order_id' => $orderId,
      'gross_amount' => (int)$total,
    ],
    'credit_card' => [
      'secure' => true
    ],
    'customer_details' => [
      'first_name' => "Table " . $table,
    ]
  ];

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Content-Type: application/json",
      "Authorization: Basic " . base64_encode($serverKey . ":")
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
  ]);

  $response = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);

  if ($err) {
    echo "cURL Error #: " . $err;
  } else {
    $result = json_decode($response, true);
    $redirectUrl = $result['redirect_url'];

    // Arahkan ke halaman pembayaran
    header("Location: " . $redirectUrl);
    exit;
  }
}
?>
