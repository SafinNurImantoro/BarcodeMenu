<?php
// Check if this is being included in an admin page
if (!function_exists('isAdminLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<button type="button" class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Buka menu admin">
  Menu
</button>

<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

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

<script>
(() => {
  const toggleButton = document.getElementById('mobileSidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');

  if (!toggleButton || !overlay) return;

  const closeSidebar = () => {
    document.body.classList.remove('sidebar-open');
    toggleButton.setAttribute('aria-expanded', 'false');
  };

  const openSidebar = () => {
    document.body.classList.add('sidebar-open');
    toggleButton.setAttribute('aria-expanded', 'true');
  };

  toggleButton.addEventListener('click', () => {
    if (document.body.classList.contains('sidebar-open')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  overlay.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1199) {
      closeSidebar();
    }
  });
})();
</script>
