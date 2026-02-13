<?php
require 'auth.php';
require 'db.php';
require 'helpers.php';

// Check if admin is logged in
requireAuth();

$message = '';
$message_type = '';
$edit_id = null;
$edit_data = null;

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Add Menu
if (isset($_POST['simpan'])) {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'CSRF token tidak valid.';
        $message_type = 'danger';
    } else {
        $id = isset($_POST['id']) && !empty($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
        $name = sanitizeMenuName($_POST['name'] ?? '');
        $price = validateMenuPrice($_POST['price'] ?? 0);
        $category = sanitizeMenuCategory($_POST['category'] ?? '');

        if (empty($name)) {
            $message = 'Nama menu tidak valid (minimal 3 karakter).';
            $message_type = 'warning';
        } else if ($price === false) {
            $message = 'Harga harus angka dan lebih dari 0.';
            $message_type = 'warning';
        } else if (empty($category)) {
            $message = 'Kategori tidak boleh kosong.';
            $message_type = 'warning';
        } else {
            if ($id) {
                $stmt = $conn->prepare("UPDATE menu SET name = ?, price = ?, category = ? WHERE id = ?");
                $stmt->bind_param("sisi", $name, $price, $category, $id);

                if ($stmt->execute()) {
                    $message = 'Menu berhasil diperbarui.';
                    $message_type = 'success';
                    logMenuAction('updated', $id, ['name' => $name, 'price' => $price, 'category' => $category]);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $edit_id = null;
                    $edit_data = null;
                } else {
                    $message = 'Error: ' . htmlspecialchars($stmt->error);
                    $message_type = 'danger';
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO menu(name, price, category) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $name, $price, $category);

                if ($stmt->execute()) {
                    $message = 'Menu berhasil ditambahkan.';
                    $message_type = 'success';
                    logMenuAction('added', $conn->insert_id, ['name' => $name, 'price' => $price, 'category' => $category]);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $message = 'Error: ' . htmlspecialchars($stmt->error);
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// Handle Edit request (GET parameter)
if (isset($_GET['edit'])) {
    $edit_id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt = $conn->prepare("SELECT id, name, price, category FROM menu WHERE id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_data = $result->fetch_assoc();
        $stmt->close();
    }
}

// Handle Delete Menu
if (isset($_GET['hapus'])) {
    if (empty($_GET['csrf']) || $_GET['csrf'] !== $_SESSION['csrf_token']) {
        $message = 'CSRF token tidak valid untuk delete.';
        $message_type = 'danger';
    } else {
        $id = filter_var($_GET['hapus'], FILTER_VALIDATE_INT);

        if ($id === false) {
            $message = 'ID menu tidak valid.';
            $message_type = 'warning';
        } else {
            $stmt = $conn->prepare("SELECT name, price, category FROM menu WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $menu_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = 'Menu berhasil dihapus.';
                $message_type = 'success';
                logMenuAction('deleted', $id, $menu_data);
            } else {
                $message = 'Menu tidak ditemukan atau gagal dihapus.';
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
    return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
}

function validateMenuPrice($price) {
    $price = filter_var($price, FILTER_VALIDATE_INT);
    return ($price && $price > 0) ? $price : false;
}

function sanitizeMenuCategory($category) {
    $category = trim($category);
    $allowed = ['OUR SIGNATURE', 'PURE TEA', 'MILK TEA', 'HONEY SERIES', 'topping'];
    return in_array($category, $allowed) ? $category : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - TEAZZI Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/admin-minimal.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
  <h1 class="page-title">Manajemen Menu</h1>
  <p class="page-subtitle">Tambah, edit, dan hapus daftar produk.</p>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <section class="surface-card mb-4">
    <h2 class="section-title"><?= $edit_id ? 'Edit Menu' : 'Tambah Menu Baru' ?></h2>
    <form method="POST" class="row g-2">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <?php if ($edit_id): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_id) ?>">
      <?php endif; ?>

      <div class="col-md-4">
        <input type="text" name="name" class="form-control" placeholder="Nama menu" value="<?= ($edit_data ? htmlspecialchars($edit_data['name']) : '') ?>" minlength="3" maxlength="100" required>
      </div>
      <div class="col-md-3">
        <input type="number" name="price" class="form-control" placeholder="Harga (Rp)" value="<?= ($edit_data ? htmlspecialchars($edit_data['price']) : '') ?>" min="1000" max="999999" required>
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select" required>
          <option value="">Pilih kategori</option>
          <option value="OUR SIGNATURE" <?= ($edit_data && $edit_data['category'] === 'OUR SIGNATURE') ? 'selected' : '' ?>>OUR SIGNATURE</option>
          <option value="PURE TEA" <?= ($edit_data && $edit_data['category'] === 'PURE TEA') ? 'selected' : '' ?>>PURE TEA</option>
          <option value="MILK TEA" <?= ($edit_data && $edit_data['category'] === 'MILK TEA') ? 'selected' : '' ?>>MILK TEA</option>
          <option value="HONEY SERIES" <?= ($edit_data && $edit_data['category'] === 'HONEY SERIES') ? 'selected' : '' ?>>HONEY SERIES</option>
          <option value="topping" <?= ($edit_data && $edit_data['category'] === 'topping') ? 'selected' : '' ?>>TOPPING</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button type="submit" name="simpan" class="btn btn-primary">
          <?= $edit_id ? 'Update' : 'Simpan' ?>
        </button>
      </div>

      <?php if ($edit_id): ?>
        <div class="col-12">
          <a href="menu.php" class="btn btn-outline-secondary">Batal edit</a>
        </div>
      <?php endif; ?>
    </form>
  </section>

  <section class="surface-card">
    <h2 class="section-title">Daftar Menu</h2>
    <div class="table-wrapper table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Harga</th>
            <th>Kategori</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $data = $conn->query("SELECT id, name, price, category FROM menu ORDER BY id DESC");
          while ($m = $data->fetch_assoc()) {
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
              <td><span class="badge text-bg-secondary"><?= $category ?></span></td>
              <td class="text-nowrap">
                <a href="?edit=<?= $id ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="?hapus=<?= $id ?>&csrf=<?= $csrf ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus menu: <?= addslashes($name) ?>?')">Hapus</a>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
