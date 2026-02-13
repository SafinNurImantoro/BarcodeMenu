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
    if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        $message = 'CSRF token tidak valid.';
        $message_type = 'danger';
    } else {
        $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
        $new_status = $_POST['new_status'] ?? '';
        $old_status = $_POST['old_status'] ?? '';

        if ($order_id && in_array($new_status, ['pending', 'paid'])) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);

            if ($stmt->execute()) {
                $message = 'Status pesanan berhasil diperbarui.';
                $message_type = 'success';
                logOrderAction('status_changed', $order_id, "Status changed from {$old_status} to {$new_status}");
            } else {
                $message = 'Error: ' . htmlspecialchars($stmt->error);
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (($_GET['csrf'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        $message = 'CSRF token tidak valid untuk delete.';
        $message_type = 'danger';
    } else {
        $order_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);

        if ($order_id) {
            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);

            if ($stmt->execute()) {
                $message = 'Pesanan berhasil dihapus.';
                $message_type = 'success';
                logOrderAction('deleted', $order_id, 'Order permanently deleted');
            } else {
                $message = 'Error: ' . htmlspecialchars($stmt->error);
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

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $count_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

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

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
    <title>Orders - TEAZZI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-minimal.css?v=20260213a">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
    <h1 class="page-title">Database Pesanan</h1>
    <p class="page-subtitle">Kelola status, pencarian, dan histori pesanan.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <section class="surface-card mb-4">
        <h2 class="section-title">Filter</h2>
        <form method="GET" action="" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>Semua status</option>
                    <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= ($status_filter === 'paid') ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Cari meja, nama, atau WhatsApp" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
            <div class="col-md-2">
                <a href="?status=all" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </section>

    <section class="surface-card">
        <h2 class="section-title">Daftar Pesanan (Halaman <?= $page ?> dari <?= $total_pages ?: 1 ?>)</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-wrapper table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Meja</th>
                            <th>Nama</th>
                            <th>Menu</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><strong><?= htmlspecialchars($row['table_no']) ?></strong></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['item']) ?></td>
                                <td><?= htmlspecialchars($row['qty']) ?></td>
                                <td><strong>Rp <?= number_format($row['total']) ?></strong></td>
                                <td>
                                    <form method="POST" action="" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="old_status" value="<?= htmlspecialchars($row['status']) ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="pending" <?= ($row['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                            <option value="paid" <?= ($row['status'] === 'paid') ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="text-nowrap">
                                    <a href="https://wa.me/<?= htmlspecialchars($row['customer_whatsapp']) ?>" target="_blank" class="btn btn-sm btn-success">WA</a>
                                    <a href="?delete=<?= $row['id'] ?>&csrf=<?= htmlspecialchars($_SESSION['csrf_token']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pesanan ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">Prev</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&status=<?= htmlspecialchars($status_filter) ?>&search=<?= htmlspecialchars($search) ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-data">
                <p class="mb-1">Tidak ada pesanan.</p>
                <small>Coba ubah filter atau kembali ke halaman pertama.</small>
            </div>
        <?php endif; ?>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
