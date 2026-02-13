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
    <style>
        body {
            background-color: #f8f5f0;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 230px;
            padding: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #080e83 0%, #1a1f5c 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card h6 {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .stat-card .icon {
            font-size: 2rem;
            opacity: 0.7;
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

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
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

        .btn-action {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 6px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .title {
            color: #080e83;
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #080e83;
            box-shadow: 0 0 0 0.2rem rgba(8, 14, 131, 0.25);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1 class="title">📊 Dashboard Pesanan</h1>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6>Total Pesanan</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= $total_orders ?></h3>
                    <div class="icon">📋</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6>Pesanan Pending</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= $status_breakdown['pending'] ?></h3>
                    <div class="icon">⏳</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6>Pesanan Bayar</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3><?= $status_breakdown['paid'] ?></h3>
                    <div class="icon">✅</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h6>Omset Hari Ini</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h3 style="font-size: 1.5rem;">Rp <?= number_format($daily_revenue) ?></h3>
                    <div class="icon">💰</div>
                </div>
            </div>
        </div>
    </div>

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
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari meja/nama/wa..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">🔍 Cari</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-card">
        <h5 class="mb-3">📝 Daftar Pesanan</h5>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 8%;">Meja</th>
                            <th style="width: 15%;">Nama Customer</th>
                            <th style="width: 12%;">WhatsApp</th>
                            <th style="width: 15%;">Menu</th>
                            <th style="width: 8%;">Qty</th>
                            <th style="width: 12%;">Total</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><strong><?= htmlspecialchars($row['table_no']) ?></strong></td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= htmlspecialchars($row['customer_whatsapp']) ?>" 
                                       target="_blank" class="text-decoration-none">
                                        📱 <?= htmlspecialchars(substr($row['customer_whatsapp'], -10)) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['item']) ?></td>
                                <td><?= htmlspecialchars($row['qty']) ?></td>
                                <td><strong>Rp <?= number_format($row['total']) ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                                        <?= ($row['status'] === 'pending') ? '⏳ Pending' : '✅ Paid' ?>
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
                <p style="font-size: 1.2rem; margin-bottom: 20px;">😴 Tidak ada pesanan</p>
                <p style="color: #ccc;">Coba ubah filter atau tunggu pesanan masuk</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-refresh every 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000);
</script>

</body>
</html>
