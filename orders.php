<?php
require 'auth.php';
require 'db.php';

// Check if admin is logged in
requireAuth();

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Validate CSRF token
    if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        $message = '❌ CSRF token tidak valid!';
        $message_type = 'danger';
    } else {
        $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
        $new_status = $_POST['new_status'] ?? '';
        $old_status = $_POST['old_status'] ?? '';
        
        if ($order_id && in_array($new_status, ['pending', 'paid'])) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            
            if ($stmt->execute()) {
                $message = '✅ Status pesanan berhasil diperbarui!';
                $message_type = 'success';
                // Log the action
                logOrderAction('status_changed', $order_id, "Status changed from {$old_status} to {$new_status}");
            } else {
                $message = '❌ Error: ' . htmlspecialchars($stmt->error);
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    // Validate CSRF parameter
    if (($_GET['csrf'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        $message = '❌ CSRF token tidak valid untuk delete!';
        $message_type = 'danger';
    } else {
        $order_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
        
        if ($order_id) {
            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                $message = '✅ Pesanan berhasil dihapus!';
                $message_type = 'success';
                // Log the action
                logOrderAction('deleted', $order_id, 'Order permanently deleted');
            } else {
                $message = '❌ Error: ' . htmlspecialchars($stmt->error);
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min' => 1]]);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT * FROM orders WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM orders WHERE 1=1";
$params = [];
$types = '';

// Filter by status
if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $count_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Search
if (!empty($search)) {
    $query .= " AND (table_no LIKE ? OR customer_name LIKE ? OR customer_whatsapp LIKE ?)";
    $count_query .= " AND (table_no LIKE ? OR customer_name LIKE ? OR customer_whatsapp LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Get total count
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total = $conn->query($count_query)->fetch_assoc()['total'];
}

// Add pagination to main query
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

// Execute query with pagination
$stmt = $conn->prepare($query);
$bind_params = $params;
$bind_params[] = $limit;
$bind_params[] = $offset;
$bind_types = $types . 'ii';

$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();

$total_pages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Pesanan - TEAZZI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f5f0;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 230px;
            padding: 30px;
        }

        .title {
            color: #080e83;
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #080e83;
            box-shadow: 0 0 0 0.2rem rgba(8, 14, 131, 0.25);
        }

        .action-btn {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        .pagination {
            margin-top: 20px;
            justify-content: center;
        }

        .pagination .page-link {
            color: #080e83;
        }

        .pagination .page-link.active {
            background-color: #080e83;
            border-color: #080e83;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1 class="title">📦 Database Pesanan</h1>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="filter-card">
        <h5 class="mb-3">🔍 Filter & Pencarian</h5>
        <form method="GET" action="" class="row g-2">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Semua Status</option>
                    <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="paid" <?= ($status_filter === 'paid') ? 'selected' : '' ?>>✅ Paid</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Cari meja/nama/wa..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">🔍 Cari</button>
            </div>
            <div class="col-md-2">
                <a href="?status=all" class="btn btn-secondary w-100">↻ Reset</a>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-card">
        <h5 class="mb-3">📝 Daftar Pesanan (Halaman <?= $page ?> dari <?= $total_pages ?: 1 ?>)</h5>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 8%;">Meja</th>
                            <th style="width: 12%;">Nama</th>
                            <th style="width: 12%;">Menu</th>
                            <th style="width: 8%;">Qty</th>
                            <th style="width: 12%;">Total</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 18%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><strong style="font-size: 1.1rem;"><?= htmlspecialchars($row['table_no']) ?></strong></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['item']) ?></td>
                                <td><?= htmlspecialchars($row['qty']) ?></td>
                                <td><strong>Rp <?= number_format($row['total']) ?></strong></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="old_status" value="<?= $row['status'] ?>">
                                        <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?= ($row['status'] === 'pending') ? 'selected' : '' ?>>⏳ Pending</option>
                                            <option value="paid" <?= ($row['status'] === 'paid') ? 'selected' : '' ?>>✅ Paid</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= htmlspecialchars($row['customer_whatsapp']) ?>" 
                                       target="_blank" class="btn btn-sm btn-success action-btn" title="Chat WhatsApp">
                                        💬 WA
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>&csrf=<?= htmlspecialchars($_SESSION['csrf_token']) ?>" 
                                       class="btn btn-sm btn-danger action-btn"
                                       onclick="return confirm('Hapus pesanan ini?')">
                                        🗑️ Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">« First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">‹ Prev</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">Next ›</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">Last »</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-data">
                <p style="font-size: 1.2rem; margin-bottom: 20px;">📭 Tidak ada pesanan</p>
                <p style="color: #ccc;">Coba ubah filter atau kembali ke halaman pertama</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
