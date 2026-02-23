<?php
$menu = $_POST['menu'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=20260213a">
  <style>
    :root {
      --teazzi-blue: #080e83;
      --teazzi-cream: #f8f5f0;
      --surface: #fffaf5;
      --line: #e6ddd2;
      --text-muted: #746d6d;
    }
    body.checkout-page {
      background: var(--teazzi-cream);
      min-height: 100vh;
      color: #2f2a2a;
    }
    .navbar-sticky {
      position: sticky;
      top: 0;
      z-index: 1030;
      box-shadow: 0 4px 14px rgba(8, 14, 131, 0.18);
    }
    .brand-title {
      font-weight: 700;
      letter-spacing: 0.4px;
      color: #fff !important;
    }
    .surface-card {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 10px 24px rgba(8, 14, 131, 0.08);
      padding: 1rem;
    }
    .section-title {
      color: var(--teazzi-blue);
      font-weight: 700;
      margin-bottom: 0.4rem;
    }
    .section-subtitle {
      color: var(--text-muted);
      margin-bottom: 0;
      font-size: 0.94rem;
    }
    .table-wrapper {
      border: 1px solid var(--line);
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
    }
    .table {
      margin-bottom: 0;
      min-width: 700px;
    }
    .table thead th {
      white-space: nowrap;
      background: #f5f1eb;
      color: #1a1f5c;
    }
    .total-row th {
      background: #f5f1eb;
      color: #1a1f5c;
    }
    .btn-primary {
      background: var(--teazzi-blue);
      border-color: var(--teazzi-blue);
    }
    .btn-primary:hover {
      background: #1a1f5c;
      border-color: #1a1f5c;
    }
    .order-empty {
      text-align: center;
      color: var(--text-muted);
      padding: 2rem 1rem;
    }
    @media (max-width: 991.98px) {
      .checkout-main {
        padding-top: 1.25rem !important;
      }
    }
    @media (max-width: 767.98px) {
      .surface-card {
        padding: 0.85rem;
        border-radius: 12px;
      }
      .checkout-main {
        padding-left: 12px !important;
        padding-right: 12px !important;
      }
      .section-title {
        font-size: 1.25rem;
      }
      .table-wrapper {
        border: 0;
        background: transparent;
        overflow: visible;
      }
      .table {
        min-width: 0;
        border: 0;
        background: transparent;
      }
      .table thead {
        display: none;
      }
      .table tbody tr {
        display: block;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        padding: 0.65rem;
        margin-bottom: 0.75rem;
      }
      .table tbody td {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        border: 0;
        border-bottom: 1px dashed var(--line);
        padding: 0.45rem 0;
        font-size: 0.92rem;
      }
      .table tbody td:last-child {
        border-bottom: 0;
        padding-bottom: 0;
      }
      .table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #1a1f5c;
        flex: 0 0 84px;
      }
      .table tfoot {
        display: block;
      }
      .table tfoot tr {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        padding: 0.65rem;
      }
      .table tfoot th {
        border: 0;
        padding: 0;
        background: transparent !important;
      }
    }
  </style>
</head>
<body class="checkout-page">
<nav class="navbar navbar-expand-lg navbar-sticky" style="background-color:#080e83;">
  <div class="container">
    <a href="index.php" class="navbar-brand brand-title text-decoration-none">TEAZZI</a>
    <div class="ms-auto">
      <a href="index.php" class="btn btn-outline-light btn-sm">Kembali ke Menu</a>
    </div>
  </div>
</nav>

<main class="container checkout-main py-4">
  <div class="surface-card mb-3 mb-md-4">
    <h1 class="section-title h3 mb-1">Checkout Pesanan</h1>
    <p class="section-subtitle">Periksa pesanan Anda, lalu lengkapi data pemesan.</p>
  </div>

  <?php if(count($menu) === 0): ?>
    <div class="surface-card order-empty">
      Keranjang kosong. <a href="index.php">Kembali ke menu</a>
    </div>
  <?php else: ?>
    <form action="submit_order.php" method="POST" class="d-grid gap-3 gap-md-4">
      <section class="surface-card">
        <h2 class="h5 mb-3">Ringkasan Keranjang</h2>
        <div class="table-wrapper table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="text-center">
              <tr>
                <th>Menu</th>
                <th>Jumlah</th>
                <th>Topping</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($menu as $item): 
                $toppings = json_decode($item['toppings'], true) ?? [];
                $subtotal = $item['subtotal'];
                $total += $subtotal;
              ?>
              <tr>
                <td data-label="Menu"><?= htmlspecialchars($item['name']) ?></td>
                <td data-label="Jumlah" class="text-center"><?= $item['qty'] ?></td>
                <td data-label="Topping">
                  <?php
                  if(count($toppings) > 0){
                    foreach($toppings as $t){
                      echo htmlspecialchars($t['name']).' (Rp '.number_format($t['price'],0,',','.').')<br>';
                    }
                  } else { echo '-'; }
                  ?>
                </td>
                <td data-label="Subtotal"><strong>Rp <?= number_format($subtotal,0,',','.') ?></strong></td>
              </tr>

              <input type="hidden" name="menu[<?= $item['name'] ?>][qty]" value="<?= $item['qty'] ?>">
              <input type="hidden" name="menu[<?= $item['name'] ?>][subtotal]" value="<?= $subtotal ?>">
              <input type="hidden" name="menu[<?= $item['name'] ?>][toppings]" value='<?= htmlspecialchars(json_encode($toppings)) ?>'>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="total-row">
                <th colspan="3" class="text-end">Total</th>
                <th>Rp <?= number_format($total,0,',','.') ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      <section class="surface-card">
        <h2 class="h5 mb-3">Data Pemesan</h2>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nomor Meja</label>
            <input type="text" name="table_no" class="form-control" required placeholder="Contoh: 5">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nama Pemesan</label>
            <input type="text" name="customer_name" class="form-control" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Nomor WhatsApp</label>
          <input type="tel" name="customer_whatsapp" class="form-control" required
                 placeholder="Contoh: 6281234567890"
                 pattern="62[0-9]{9,13}"
                 title="Gunakan format: 62 diikuti nomor tanpa 0">
          <div class="form-text text-muted">
            Gunakan format internasional, misal: <strong>6281234567890</strong>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Metode Pembayaran</label>
          <select name="payment_method" class="form-select" required>
            <option value="QRIS2">QRIS</option>
            <option value="BRIVA">BRI Virtual Account</option>
            <option value="BNIVA">BNI Virtual Account</option>
            <option value="MANDIRIVA">Mandiri Virtual Account</option>
            <option value="PERMATAVA">Permata Virtual Account</option>
            <option value="ALFAMART">Alfamart</option>
            <option value="INDOMARET">Indomaret</option>
            <option value="CASH">Tunai</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Pesan Tambahan</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Es sedikit, tanpa gula, dll."></textarea>
        </div>

        <div class="d-grid d-md-flex justify-content-md-end gap-2">
          <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn btn-primary px-4">Bayar Sekarang</button>
        </div>
      </section>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
