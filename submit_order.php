<?php
require __DIR__ . '/vendor/autoload.php';
include 'db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil data dari form
$menu = $_POST['menu'] ?? [];
$customer_name = $_POST['customer_name'] ?? '';
$customer_whatsapp = $_POST['customer_whatsapp'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';
$table_no = $_POST['table_no'] ?? '';
$notes = $_POST['notes'] ?? '';

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
if (!is_dir('invoices')) mkdir('invoices');
$file_path = 'invoices/invoice_' . time() . '.pdf';
file_put_contents($file_path, $dompdf->output());
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
    <p><strong>Nama Pemesan:</strong> <?= htmlspecialchars($customer_name) ?></p>
    <p><strong>No. Meja:</strong> <?= htmlspecialchars($table_no) ?></p>
    <p><strong>Total:</strong> Rp <?= number_format($total, 0, ',', '.') ?></p>
    <div class="text-center mt-4">
      <a href="<?= $file_path ?>" target="_blank" class="btn btn-danger me-2">Download Invoice (PDF)</a>
      <a href="index.php" class="btn btn-secondary">Kembali ke Menu</a>
    </div>
  </div>
</div>
</body>
</html>
