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

// Handle Add/Edit Menu via AJAX
if (isset($_POST['simpan'])) {
    header('Content-Type: application/json');
    
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid.']);
        exit;
    }

    $id = isset($_POST['id']) && !empty($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
    $name = sanitizeMenuName($_POST['name'] ?? '');
    $price = validateMenuPrice($_POST['price'] ?? 0);
    $category = sanitizeMenuCategory($_POST['category'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama menu tidak valid (minimal 3 karakter).']);
        exit;
    } else if ($price === false) {
        echo json_encode(['success' => false, 'message' => 'Harga harus angka dan lebih dari 0.']);
        exit;
    } else if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak boleh kosong.']);
        exit;
    }

    $image = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Format gambar harus JPEG, PNG, atau WEBP.']);
            exit;
        }

        if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB max
            echo json_encode(['success' => false, 'message' => 'Ukuran gambar maksimal 5MB.']);
            exit;
        }

        $upload_dir = 'assets/img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image = $new_filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload gambar.']);
            exit;
        }
    }

    try {
        if ($id) {
            // Update Menu
            if ($image) {
                $stmt = $conn->prepare("UPDATE menu SET name = ?, price = ?, category = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sissi", $name, $price, $category, $image, $id);
            } else {
                $stmt = $conn->prepare("UPDATE menu SET name = ?, price = ?, category = ? WHERE id = ?");
                $stmt->bind_param("sisi", $name, $price, $category, $id);
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu berhasil diperbarui.']);
                logMenuAction('updated', $id, ['name' => $name, 'price' => $price, 'category' => $category]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . htmlspecialchars($stmt->error)]);
            }
            $stmt->close();
        } else {
            // Add New Menu
            $stmt = $conn->prepare("INSERT INTO menu(name, price, category, image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $name, $price, $category, $image);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu berhasil ditambahkan.']);
                logMenuAction('added', $conn->insert_id, ['name' => $name, 'price' => $price, 'category' => $category]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . htmlspecialchars($stmt->error)]);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle Delete Menu via AJAX
if (isset($_POST['hapus'])) {
    header('Content-Type: application/json');
    
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid.']);
        exit;
    }

    $id = filter_var($_POST['hapus'], FILTER_VALIDATE_INT);

    if ($id === false) {
        echo json_encode(['success' => false, 'message' => 'ID menu tidak valid.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT name, price, category, image FROM menu WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $menu_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$menu_data) {
            echo json_encode(['success' => false, 'message' => 'Menu tidak ditemukan.']);
            exit;
        }

        // Delete old image if exists
        if ($menu_data['image'] && file_exists('assets/img/' . $menu_data['image'])) {
            unlink('assets/img/' . $menu_data['image']);
        }

        $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu berhasil dihapus.']);
            logMenuAction('deleted', $id, $menu_data);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus menu.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle Get All Menus via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'get_all') {
    header('Content-Type: application/json');
    
    try {
        $stmt = $conn->prepare("SELECT id, name, price, category, image FROM menu ORDER BY category ASC, name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $menus = [];
        
        while ($row = $result->fetch_assoc()) {
            $menus[] = $row;
        }
        
        echo json_encode(['success' => true, 'menus' => $menus]);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle Get Single Menu via AJAX
if (isset($_GET['get_menu'])) {
    header('Content-Type: application/json');

    $id = filter_var($_GET['get_menu'], FILTER_VALIDATE_INT);
    if ($id === false) {
        echo json_encode(['success' => false, 'message' => 'ID menu tidak valid.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id, name, price, category, image FROM menu WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $menu = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($menu) {
            echo json_encode(['success' => true, 'data' => $menu]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Menu tidak ditemukan.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
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
  <link rel="stylesheet" href="assets/css/admin-minimal.css?v=20260213a">
  <style>
    .menu-image {
      max-width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
    }
    .menu-card {
      transition: box-shadow 0.3s ease;
    }
    .menu-card:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .search-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .search-bar input,
    .search-bar select {
      flex: 1;
      min-width: 200px;
    }
    .search-bar button {
      flex-shrink: 0;
    }
    @media (max-width: 1024px) {
      .search-bar input,
      .search-bar select {
        min-width: 170px;
      }
    }
    @media (max-width: 767.98px) {
      .search-bar {
        gap: 8px;
      }
      .search-bar input,
      .search-bar select,
      .search-bar button {
        width: 100%;
        min-width: 100%;
      }
    }
    .no-image {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      background-color: #f0f0f0;
      border-radius: 4px;
      font-size: 12px;
      color: #999;
    }
    .modal-body .form-group {
      margin-bottom: 15px;
    }
    .image-preview {
      max-width: 100%;
      max-height: 200px;
      margin-top: 10px;
      border-radius: 4px;
    }
    .alert-auto-dismiss {
      animation: fadeOut 4s ease-in-out forwards;
    }
    @keyframes fadeOut {
      0% { opacity: 1; }
      90% { opacity: 1; }
      100% { opacity: 0; display: none; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
  <h1 class="page-title">Manajemen Menu</h1>
  <p class="page-subtitle">Kelola daftar produk dengan mudah.</p>

  <div id="alertContainer"></div>

  <!-- Search & Add Menu Section -->
  <section class="surface-card mb-4">
    <div class="search-bar">
      <input 
        type="text" 
        id="searchInput" 
        class="form-control" 
        placeholder="🔍 Cari menu..." 
      >
      <select id="filterCategory" class="form-select">
        <option value="">Semua Kategori</option>
        <option value="OUR SIGNATURE">OUR SIGNATURE</option>
        <option value="PURE TEA">PURE TEA</option>
        <option value="MILK TEA">MILK TEA</option>
        <option value="HONEY SERIES">HONEY SERIES</option>
        <option value="topping">TOPPING</option>
      </select>
      <button 
        type="button" 
        class="btn btn-primary" 
        data-bs-toggle="modal" 
        data-bs-target="#menuModal"
        onclick="resetForm()"
      >
        ➕ Tambah Menu
      </button>
    </div>
  </section>

  <!-- Menu Table Section -->
  <section class="surface-card">
    <h2 class="section-title">Daftar Menu</h2>
    <div class="table-wrapper table-responsive">
      <table class="table table-hover align-middle" id="menuTable">
        <thead>
          <tr>
            <th>Foto</th>
            <th>Nama</th>
            <th>Harga</th>
            <th>Kategori</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="menuTableBody">
          <!-- Data will be loaded here -->
        </tbody>
      </table>
      <div id="noData" class="alert alert-info" style="display: none;">
        Tidak ada menu yang cocok dengan pencarian.
      </div>
    </div>
  </section>
</main>

<!-- Add/Edit Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Tambah Menu Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="menuForm" enctype="multipart/form-data">
          <input type="hidden" id="menuId" name="id" value="">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

          <div class="form-group">
            <label for="menuName" class="form-label fw-bold">Nama Menu <span class="text-danger">*</span></label>
            <input 
              type="text" 
              id="menuName" 
              name="name" 
              class="form-control" 
              placeholder="Contoh: Deep Roast Milk Tea"
              minlength="3" 
              maxlength="100" 
              required
            >
          </div>

          <div class="form-group">
            <label for="menuPrice" class="form-label fw-bold">Harga (Rp) <span class="text-danger">*</span></label>
            <input 
              type="number" 
              id="menuPrice" 
              name="price" 
              class="form-control" 
              placeholder="Contoh: 35000"
              min="1000" 
              max="999999" 
              required
            >
          </div>

          <div class="form-group">
            <label for="menuCategory" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
            <select id="menuCategory" name="category" class="form-select" required>
              <option value="">Pilih kategori</option>
              <option value="OUR SIGNATURE">OUR SIGNATURE</option>
              <option value="PURE TEA">PURE TEA</option>
              <option value="MILK TEA">MILK TEA</option>
              <option value="HONEY SERIES">HONEY SERIES</option>
              <option value="topping">TOPPING</option>
            </select>
          </div>

          <div class="form-group">
            <label for="menuImage" class="form-label fw-bold">Foto Menu</label>
            <input 
              type="file" 
              id="menuImage" 
              name="image" 
              class="form-control" 
              accept="image/jpeg,image/png,image/webp"
            >
            <small class="text-muted">Format: JPEG, PNG, WEBP | Ukuran max: 5MB</small>
            <div id="imagePreviewContainer" style="display: none; margin-top: 10px;">
              <img id="imagePreview" src="" alt="Preview" class="image-preview">
            </div>
          </div>

          <div class="alert alert-danger" id="formError" style="display: none;"></div>

          <div class="d-flex gap-2 pt-3">
            <button type="submit" class="btn btn-primary flex-grow-1">
              <span id="submitBtnText">Simpan</span>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize
document.addEventListener('DOMContentLoaded', function() {
  loadMenuData();
  setupEventListeners();
});

function setupEventListeners() {
  // Form submission
  document.getElementById('menuForm').addEventListener('submit', handleFormSubmit);

  // Search functionality
  document.getElementById('searchInput').addEventListener('keyup', filterMenuTable);
  document.getElementById('filterCategory').addEventListener('change', filterMenuTable);

  // Image preview
  document.getElementById('menuImage').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('imagePreview').src = e.target.result;
        document.getElementById('imagePreviewContainer').style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
}

function loadMenuData() {
  fetch('menu.php?action=get_all')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateMenuTable(data.menus);
      } else {
        showAlert('Gagal memuat data menu', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showAlert('Terjadi kesalahan saat memuat data', 'danger');
    });
}

function populateMenuTable(menus) {
  const tbody = document.getElementById('menuTableBody');
  tbody.innerHTML = '';

  if (menus.length === 0) {
    document.getElementById('noData').style.display = 'block';
    document.getElementById('menuTable').style.display = 'none';
    return;
  }

  document.getElementById('noData').style.display = 'none';
  document.getElementById('menuTable').style.display = 'table';

  menus.forEach(menu => {
    const row = document.createElement('tr');
    const imageCell = menu.image 
      ? `<img src="assets/img/${encodeURIComponent(menu.image)}" alt="${escapeHtml(menu.name)}" class="menu-image">`
      : '<div class="no-image">No Image</div>';

    row.innerHTML = `
      <td>${imageCell}</td>
      <td><strong>${escapeHtml(menu.name)}</strong></td>
      <td>Rp ${parseInt(menu.price).toLocaleString('id-ID')}</td>
      <td><span class="badge text-bg-secondary">${escapeHtml(menu.category)}</span></td>
      <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-primary" onclick="editMenu(${menu.id})">Edit</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteMenu(${menu.id}, '${escapeHtml(menu.name).replace(/'/g, "\\'")}')">Hapus</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

function filterMenuTable() {
  const searchTerm = document.getElementById('searchInput').value.toLowerCase();
  const category = document.getElementById('filterCategory').value;
  const rows = document.querySelectorAll('#menuTableBody tr');
  let visibleCount = 0;

  rows.forEach(row => {
    const name = row.cells[1].textContent.toLowerCase();
    const rowCategory = row.cells[3].textContent.trim();

    const matchesSearch = name.includes(searchTerm);
    const matchesCategory = !category || rowCategory === category;

    if (matchesSearch && matchesCategory) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('noData').style.display = visibleCount === 0 ? 'block' : 'none';
}

function resetForm() {
  document.getElementById('menuForm').reset();
  document.getElementById('menuId').value = '';
  document.getElementById('modalTitle').textContent = 'Tambah Menu Baru';
  document.getElementById('submitBtnText').textContent = 'Simpan';
  document.getElementById('formError').style.display = 'none';
  document.getElementById('imagePreviewContainer').style.display = 'none';
}

function editMenu(id) {
  fetch(`menu.php?get_menu=${id}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const menu = data.data;
        document.getElementById('menuId').value = menu.id;
        document.getElementById('menuName').value = menu.name;
        document.getElementById('menuPrice').value = menu.price;
        document.getElementById('menuCategory').value = menu.category;
        
        document.getElementById('modalTitle').textContent = 'Edit Menu';
        document.getElementById('submitBtnText').textContent = 'Update';
        
        if (menu.image) {
          document.getElementById('imagePreview').src = `assets/img/${encodeURIComponent(menu.image)}`;
          document.getElementById('imagePreviewContainer').style.display = 'block';
        } else {
          document.getElementById('imagePreviewContainer').style.display = 'none';
        }
        
        const modal = new bootstrap.Modal(document.getElementById('menuModal'));
        modal.show();
      } else {
        showAlert('Gagal memuat data menu', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showAlert('Terjadi kesalahan', 'danger');
    });
}

function handleFormSubmit(e) {
  e.preventDefault();
  
  const formData = new FormData(document.getElementById('menuForm'));
  formData.append('simpan', '1');
  
  fetch('menu.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showAlert(data.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('menuModal')).hide();
      document.getElementById('menuForm').reset();
      loadMenuData();
    } else {
      document.getElementById('formError').textContent = data.message;
      document.getElementById('formError').style.display = 'block';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    document.getElementById('formError').textContent = 'Terjadi kesalahan saat menyimpan data';
    document.getElementById('formError').style.display = 'block';
  });
}

function deleteMenu(id, name) {
  if (!confirm(`Yakin ingin hapus menu: ${name}?`)) {
    return;
  }

  const formData = new FormData();
  formData.append('hapus', id);
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

  fetch('menu.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    showAlert(data.message, data.success ? 'success' : 'danger');
    loadMenuData();
  })
  .catch(error => {
    console.error('Error:', error);
    showAlert('Terjadi kesalahan saat menghapus', 'danger');
  });
}

function showAlert(message, type = 'info') {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto-dismiss`;
  alertDiv.role = 'alert';
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  document.getElementById('alertContainer').appendChild(alertDiv);
  
  setTimeout(() => {
    alertDiv.remove();
  }, 4000);
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
</body>
</html>
