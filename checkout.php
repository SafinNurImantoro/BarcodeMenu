<?php
$menu = $_POST['menu'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Checkout</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h3 class="mb-3 text-center">Checkout Pesanan</h3>

  <?php if(count($menu) === 0): ?>
    <p class="text-center text-muted">Keranjang kosong. <a href="index.php">Kembali ke menu</a></p>
  <?php else: ?>
    <form action="submit_order.php" method="POST">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark text-center">
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
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td class="text-center"><?= $item['qty'] ?></td>
            <td>
              <?php
              if(count($toppings) > 0){
                foreach($toppings as $t){
                  echo htmlspecialchars($t['name']).' (Rp '.number_format($t['price'],0,',','.').')<br>';
                }
              } else { echo '-'; }
              ?>
            </td>
            <td>Rp <?= number_format($subtotal,0,',','.') ?></td>
          </tr>

          <!-- Hidden inputs untuk submit -->
          <input type="hidden" name="menu[<?= $item['name'] ?>][qty]" value="<?= $item['qty'] ?>">
          <input type="hidden" name="menu[<?= $item['name'] ?>][subtotal]" value="<?= $subtotal ?>">
          <input type="hidden" name="menu[<?= $item['name'] ?>][toppings]" value='<?= htmlspecialchars(json_encode($toppings)) ?>'>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Total</th>
            <th>Rp <?= number_format($total,0,',','.') ?></th>
          </tr>
        </tfoot>
      </table>

      <div class="row mt-4">
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
          <option value="QRIS">QRIS</option>
          <option value="Tunai">Tunai</option>
          <option value="Transfer">Transfer Bank</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Pesan Tambahan</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Es sedikit, tanpa gula, dll."></textarea>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-success btn-lg px-4">Bayar Sekarang</button>
      </div>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
