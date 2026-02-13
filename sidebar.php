<?php
// Check if this is being included in an admin page
if (!function_exists('isAdminLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="admin-sidebar">
  <h4 class="sidebar-brand">TEAZZI Admin</h4>

  <div class="sidebar-user">
    <span class="sidebar-user-label">Logged in as</span>
    <strong><?= htmlspecialchars(getAdminUsername() ?? 'admin') ?></strong>
  </div>

  <nav class="sidebar-nav" aria-label="Admin menu">
    <a href="dashboard.php" class="sidebar-link <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>">
      Dashboard
    </a>
    <a href="menu.php" class="sidebar-link <?= ($current_page === 'menu.php') ? 'active' : '' ?>">
      Menu
    </a>
    <a href="orders.php" class="sidebar-link <?= ($current_page === 'orders.php') ? 'active' : '' ?>">
      Orders
    </a>
    <a href="logout.php" class="sidebar-link logout">Logout</a>
  </nav>

  <div class="sidebar-footer">
    Session timeout: 15 minutes
  </div>
</aside>
