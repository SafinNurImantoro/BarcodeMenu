<?php
require 'auth.php';
require 'db.php';

// Check if admin is logged in
requireAuth();

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Build query
$query = "SELECT * FROM orders WHERE 1=1";
$params = [];
$types = '';

// Filter by status
if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Search
if (!empty($search)) {
    $query .= " AND (table_no LIKE ? OR customer_name LIKE ? OR customer_whatsapp LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Filter by date
if (!empty($date_filter)) {
    $query .= " AND DATE(created_at) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$query .= " ORDER BY created_at DESC";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM orders";
$count_result = $conn->query($count_query);
$total_orders = $count_result->fetch_assoc()['total'];

// Get today's revenue
$revenue_query = "SELECT SUM(total) as revenue FROM orders WHERE DATE(created_at) = CURDATE()";
$revenue_result = $conn->query($revenue_query);
$daily_revenue = $revenue_result->fetch_assoc()['revenue'] ?? 0;

// Get status breakdown
$status_breakdown = [
    'pending' => 0,
    'paid' => 0
];

$status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_result = $conn->query($status_query);
while ($row = $status_result->fetch_assoc()) {
    $status_breakdown[$row['status']] = $row['count'];
}

// Execute main query with prepared statement
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TEAZZI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin-minimal.css?v=20260213a">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
    <h1 class="page-title">Dashboard Pesanan</h1>
    <p class="page-subtitle">Ringkasan operasional dan daftar pesanan harian.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-label">Total Pesanan</span>
                <p class="stat-value"><?= $total_orders ?></p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-label">Status Pending</span>
                <p class="stat-value"><?= $status_breakdown['pending'] ?></p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-label">Status Paid</span>
                <p class="stat-value"><?= $status_breakdown['paid'] ?></p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <span class="stat-label">Omset Hari Ini</span>
                <p class="stat-value currency">Rp <?= number_format($daily_revenue) ?></p>
            </div>
        </div>
    </div>

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
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari meja, nama, atau WhatsApp" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
        </form>
    </section>

    <section class="surface-card">
        <h2 class="section-title">Daftar Pesanan</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-wrapper table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Meja</th>
                            <th>Customer</th>
                            <th>WhatsApp</th>
                            <th>Menu</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><strong><?= htmlspecialchars($row['table_no']) ?></strong></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= htmlspecialchars($row['customer_whatsapp']) ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars(substr($row['customer_whatsapp'], -10)) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['item']) ?></td>
                                <td><?= htmlspecialchars($row['qty']) ?></td>
                                <td><strong>Rp <?= number_format($row['total']) ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                                        <?= ($row['status'] === 'pending') ? 'Pending' : 'Paid' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m H:i', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <p class="mb-1">Tidak ada pesanan.</p>
                <small>Ubah filter atau tunggu pesanan masuk.</small>
            </div>
        <?php endif; ?>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(function() {
        location.reload();
    }, 30000);
</script>

</body>
</html>
