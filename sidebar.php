<?php
// Check if this is being included in an admin page
if (!function_exists('isAdminLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="d-flex">
  <div class="bg-dark text-white p-3 vh-100" style="width:230px; position: fixed; left: 0; top: 0; overflow-y: auto;">
    <h4 class="text-center mb-4" style="color: #ffd700; font-weight: 700;">⭐ ADMIN PANEL</h4>

    <div class="mb-3 p-2 rounded" style="background-color: rgba(255, 215, 0, 0.1);">
      <small style="color: #ffd700;">👤 Logged in as:</small><br>
      <small class="text-white"><strong><?= htmlspecialchars(getAdminUsername()) ?></strong></small>
    </div>

    <ul class="nav nav-pills flex-column gap-2">

      <li class="nav-item">
        <a href="dashboard.php" class="nav-link text-white <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>" style="<?= ($current_page === 'dashboard.php') ? 'background-color: #080e83;' : '' ?>">
          📋 Daftar Pesanan
        </a>
      </li>

      <li class="nav-item">
        <a href="menu.php" class="nav-link text-white <?= ($current_page === 'menu.php') ? 'active' : '' ?>" style="<?= ($current_page === 'menu.php') ? 'background-color: #080e83;' : '' ?>">
          ➕ CRUD Menu
        </a>
      </li>

      <li class="nav-item">
        <a href="orders.php" class="nav-link text-white <?= ($current_page === 'orders.php') ? 'active' : '' ?>" style="<?= ($current_page === 'orders.php') ? 'background-color: #080e83;' : '' ?>">
          📦 Database Pesanan
        </a>
      </li>

      <li class="nav-item mt-4">
        <hr style="border-color: rgba(255, 255, 255, 0.2);">
      </li>

      <li class="nav-item">
        <a href="logout.php" class="nav-link text-white" style="color: #ff6b6b !important;">
          🚪 Logout
        </a>
      </li>

    </ul>

    <!-- Session Info -->
    <div class="mt-5 pt-3 border-top border-secondary" style="font-size: 0.8rem;">
      <p class="text-muted mb-1">Session Info:</p>
      <small class="text-muted">
        ✅ Active & Secured<br>
        ⏱️ Timeout: 15 min
      </small>
    </div>
  </div>
