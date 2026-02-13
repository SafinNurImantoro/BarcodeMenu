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

// Sales statistics
$paid_today_query = "SELECT COALESCE(SUM(total), 0) AS paid_today FROM orders WHERE status = 'paid' AND DATE(created_at) = CURDATE()";
$paid_today_result = $conn->query($paid_today_query);
$paid_today = (float)($paid_today_result->fetch_assoc()['paid_today'] ?? 0);

$monthly_sales_query = "SELECT COALESCE(SUM(total), 0) AS monthly_sales, COUNT(*) AS paid_orders_month FROM orders WHERE status = 'paid' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
$monthly_sales_result = $conn->query($monthly_sales_query);
$monthly_sales_data = $monthly_sales_result->fetch_assoc();
$monthly_sales = (float)($monthly_sales_data['monthly_sales'] ?? 0);

$avg_paid_query = "SELECT COALESCE(AVG(total), 0) AS avg_paid_total FROM orders WHERE status = 'paid'";
$avg_paid_result = $conn->query($avg_paid_query);
$avg_paid_total = (float)($avg_paid_result->fetch_assoc()['avg_paid_total'] ?? 0);

$paid_rate = $total_orders > 0 ? (($status_breakdown['paid'] / $total_orders) * 100) : 0;

$top_items_query = "SELECT item, SUM(qty) AS qty_sold, SUM(total) AS total_revenue 
                    FROM orders 
                    WHERE status = 'paid' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY item
                    ORDER BY qty_sold DESC, total_revenue DESC
                    LIMIT 5";
$top_items_result = $conn->query($top_items_query);
$top_items = [];
while ($row = $top_items_result->fetch_assoc()) {
    $top_items[] = $row;
}

$daily_sales_query = "SELECT DATE(created_at) AS sale_date, COALESCE(SUM(total), 0) AS total_sales
                      FROM orders
                      WHERE status = 'paid' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY sale_date ASC";
$daily_sales_result = $conn->query($daily_sales_query);
$daily_sales_map = [];
while ($row = $daily_sales_result->fetch_assoc()) {
    $daily_sales_map[$row['sale_date']] = (float)$row['total_sales'];
}

$daily_sales = [];
for ($i = 6; $i >= 0; $i--) {
    $date_key = date('Y-m-d', strtotime("-{$i} day"));
    $daily_sales[] = [
        'date' => $date_key,
        'total' => $daily_sales_map[$date_key] ?? 0
    ];
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
        <h2 class="section-title">Statistik Penjualan</h2>
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <span class="stat-label">Penjualan Paid Hari Ini</span>
                    <p class="stat-value currency">Rp <?= number_format($paid_today) ?></p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <span class="stat-label">Penjualan Paid Bulan Ini</span>
                    <p class="stat-value currency">Rp <?= number_format($monthly_sales) ?></p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <span class="stat-label">Rata-rata Nilai Transaksi Paid</span>
                    <p class="stat-value currency">Rp <?= number_format($avg_paid_total) ?></p>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card h-100">
                    <span class="stat-label">Paid Rate</span>
                    <p class="stat-value"><?= number_format($paid_rate, 1) ?>%</p>
                    <small class="text-muted"><?= $status_breakdown['paid'] ?> paid / <?= $total_orders ?> total</small>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="table-wrapper table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th colspan="3">Produk Terlaris (30 Hari)</th>
                            </tr>
                            <tr>
                                <th>Menu</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_items)): ?>
                                <?php foreach ($top_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['item']) ?></td>
                                        <td><?= (int)$item['qty_sold'] ?></td>
                                        <td>Rp <?= number_format((float)$item['total_revenue']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada transaksi paid.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="table-wrapper table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th colspan="2">Tren Penjualan Paid (7 Hari)</th>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_sales as $day): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($day['date'])) ?></td>
                                    <td>Rp <?= number_format($day['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

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
