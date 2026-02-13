<?php
require 'auth.php';
require 'db.php';
require 'helpers.php';

// Check if admin is logged in
requireAuth();

$message = '';
$message_type = '';

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Add Menu
if(isset($_POST['simpan'])){
  // Validate CSRF token
  if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $message = 'CSRF token tidak valid!';
    $message_type = 'danger';
  } else {
    // Get and validate inputs
    $name = sanitizeMenuName($_POST['name'] ?? '');
    $price = validateMenuPrice($_POST['price'] ?? 0);
    $category = sanitizeMenuCategory($_POST['category'] ?? '');
    
    // Validate all inputs
    if (empty($name)) {
      $message = 'Nama menu tidak valid (minimal 3 karakter)';
      $message_type = 'warning';
    } else if ($price === false) {
      $message = 'Harga harus angka dan lebih dari 0';
      $message_type = 'warning';
    } else if (empty($category)) {
      $message = 'Kategori tidak boleh kosong';
      $message_type = 'warning';
    } else {
      // Use prepared statement to prevent SQL injection
      $stmt = $conn->prepare("INSERT INTO menu(name, price, category) VALUES (?, ?, ?)");
      $stmt->bind_param("sis", $name, $price, $category);
      
      if ($stmt->execute()) {
        $message = '✅ Menu berhasil ditambahkan!';
        $message_type = 'success';
        // Regenerate token after successful operation
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      } else {
        $message = '❌ Error: ' . htmlspecialchars($stmt->error);
        $message_type = 'danger';
      }
      $stmt->close();
    }
  }
}

// Handle Delete Menu
if(isset($_GET['hapus'])){
  // Validate CSRF parameter
  if (empty($_GET['csrf']) || $_GET['csrf'] !== $_SESSION['csrf_token']) {
    $message = 'CSRF token tidak valid untuk delete!';
    $message_type = 'danger';
  } else {
    $id = filter_var($_GET['hapus'], FILTER_VALIDATE_INT);
    
    if ($id === false) {
      $message = 'ID menu tidak valid';
      $message_type = 'warning';
    } else {
      // Use prepared statement for DELETE
      $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
      $stmt->bind_param("i", $id);
      
      if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = '✅ Menu berhasil dihapus!';
        $message_type = 'success';
      } else {
        $message = '❌ Menu tidak ditemukan atau gagal dihapus';
        $message_type = 'danger';
      }
      $stmt->close();
    }
  }
}

// Helper functions for validation
function sanitizeMenuName($name) {
  $name = trim($name);
  if (strlen($name) < 3 || strlen($name) > 100) {
    return '';
  }
  // Remove dangerous characters but keep valid menu names
  return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
}

function validateMenuPrice($price) {
  $price = filter_var($price, FILTER_VALIDATE_INT);
  return ($price && $price > 0) ? $price : false;
}

function sanitizeMenuCategory($category) {
  $category = trim($category);
  // Whitelist allowed categories
  $allowed = ['OUR SIGNATURE', 'PURE TEA', 'MILK TEA', 'HONEY SERIES', 'topping'];
  return in_array($category, $allowed) ? $category : '';
}
?>

<?php include 'sidebar.php'; ?>

<div class="container p-4">

<h4>🍜 CRUD Menu</h4>

<!-- Alert Messages -->
<?php if (!empty($message)): ?>
  <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Add Menu Form -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">➕ Tambah Menu Baru</h5>
  </div>
  <div class="card-body">
    <form method="POST" class="row g-2">
      <!-- CSRF Token -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      
      <div class="col-md-4">
        <input type="text" name="name" class="form-control" placeholder="Nama Menu" 
               minlength="3" maxlength="100" required>
        <small class="text-muted">Min 3 karakter</small>
      </div>
      <div class="col-md-3">
        <input type="number" name="price" class="form-control" placeholder="Harga (Rp)" 
               min="1000" max="999999" required>
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select" required>
          <option value="">-- Pilih Kategori --</option>
          <option value="OUR SIGNATURE">OUR SIGNATURE</option>
          <option value="PURE TEA">PURE TEA</option>
          <option value="MILK TEA">MILK TEA</option>
          <option value="HONEY SERIES">HONEY SERIES</option>
          <option value="topping">Topping</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" name="simpan" class="btn btn-success w-100">💾 Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Menu List -->
<div class="card">
  <div class="card-header bg-info text-white">
    <h5 class="mb-0">📋 Daftar Menu</h5>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-hover">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Harga</th>
          <th>Kategori</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <tbody>
<?php
$data = $conn->query("SELECT id, name, price, category FROM menu ORDER BY id DESC");
while($m = $data->fetch_assoc()){
  $id = htmlspecialchars($m['id']);
  $name = htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8');
  $price = htmlspecialchars($m['price']);
  $category = htmlspecialchars($m['category'], ENT_QUOTES, 'UTF-8');
  $csrf = htmlspecialchars($_SESSION['csrf_token']);
?>
        <tr>
          <td><?= $id ?></td>
          <td><?= $name ?></td>
          <td>Rp <?= number_format($price) ?></td>
          <td><span class="badge bg-secondary"><?= $category ?></span></td>
          <td>
            <a href="?hapus=<?= $id ?>&csrf=<?= $csrf ?>" 
               class="btn btn-danger btn-sm"
               onclick="return confirm('Yakin hapus menu: <?= addslashes($name) ?>?')">
               🗑️ Hapus
            </a>
          </td>
        </tr>
<?php } ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
