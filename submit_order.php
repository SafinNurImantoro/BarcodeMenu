<?php
require __DIR__ . '/vendor/autoload.php';
include 'db.php';
include 'helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

// Simpan ke tabel orders
$stmt = $conn->prepare("INSERT INTO orders 
    (table_no, customer_name, customer_whatsapp, item, qty, price, total, payment_method, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($menu as $item_name => $item) {
    $qty = (int)$item['qty'];
    $price = (float)($item['subtotal'] / $qty);
    $total_item = $qty * $price; // subtotal per item

    // Bind param sesuai tipe data: s=string, i=int, d=double
    $stmt->bind_param(
        "ssssdddsss",
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

// 🔽 Buat tampilan invoice
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice Pesanan</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
    h2 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #888; padding: 8px; text-align: left; }
    th { background-color: #eee; }
    .total { font-weight: bold; text-align: right; }
    .info p { margin: 4px 0; }
  </style>
</head>
<body>
  <h2>Invoice Pesanan</h2>

  <div class="info">
    <p><strong>Nama Pemesan:</strong> <?= htmlspecialchars($customer_name) ?></p>
    <p><strong>No. WhatsApp:</strong> <?= htmlspecialchars($customer_whatsapp) ?></p>
    <p><strong>No. Meja:</strong> <?= htmlspecialchars($table_no) ?></p>
    <p><strong>Metode Pembayaran:</strong> <?= htmlspecialchars($payment_method) ?></p>
    <p><strong>Waktu Pesan:</strong> <?= htmlspecialchars($created_at) ?></p>
    <?php if(!empty($notes)): ?>
      <p><strong>Pesan Tambahan:</strong> <?= nl2br(htmlspecialchars($notes)) ?></p>
    <?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>Menu</th>
        <th>Jumlah</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($menu as $item_name => $item): ?>
      <tr>
        <td><?= htmlspecialchars($item_name) ?></td>
        <td><?= (int)$item['qty'] ?></td>
        <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="2" class="total">Total</td>
        <td>Rp <?= number_format($total, 0, ',', '.') ?></td>
      </tr>
    </tbody>
  </table>
</body>
</html>
<?php
$html = ob_get_clean();

// 🔽 Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();

// Simpan file PDF di folder "invoices"
$invoiceDir = __DIR__ . '/invoices';
if (!is_dir($invoiceDir)) {
    if (!mkdir($invoiceDir, 0755, true)) {
        error_log("Failed to create invoices directory");
        die("Gagal membuat folder invoice");
    }
}

// Generate secure filename with timestamp and random string
$timestamp = time();
$randomStr = bin2hex(random_bytes(4));
$fileName = "invoice_{$timestamp}_{$randomStr}.pdf";
$filePath = $invoiceDir . DIRECTORY_SEPARATOR . $fileName;

// Write PDF file with restricted permissions
if (!file_put_contents($filePath, $dompdf->output())) {
    error_log("Failed to write PDF file: " . $filePath);
    die("Gagal menyimpan file invoice");
}

// Restrict file permissions (read-only for user)
chmod($filePath, 0600);

// Store invoice filename in session for access control
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['last_invoice'] = $fileName;

// Generate download link using safe download.php handler
$downloadPath = "download.php?file=" . urlencode($fileName);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice Selesai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card p-4 shadow-sm">
    <h4 class="text-center mb-4 fw-bold">Pesanan Berhasil!</h4>
    <p><strong>Nama Pemesan:</strong> <?= htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>No. Meja:</strong> <?= htmlspecialchars($table_no, ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Total:</strong> Rp <?= number_format($total, 0, ',', '.') ?></p>
    <div class="text-center mt-4">
      <a href="<?= $downloadPath ?>" target="_blank" class="btn btn-danger me-2">Download Invoice (PDF)</a>
      <a href="index.php" class="btn btn-secondary">Kembali ke Menu</a>
    </div>
  </div>
</div>
</body>
</html>

