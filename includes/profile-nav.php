<?php
// ============================================================
//  STEADVOLT — Profile Sidebar Nav
//  File: includes/profile-nav.php
// ============================================================
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="profile-sidebar">
  <div class="profile-sidebar-user">
    <?php $u = auth_user(); ?>
    <?php if ($u['avatar']): ?>
      <img src="<?= avatar_url($u['avatar']) ?>" class="sidebar-avatar" alt="Avatar"/>
    <?php else: ?>
      <div class="avatar-initials sidebar-avatar"><?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?></div>
    <?php endif; ?>
    <div>
      <strong><?= clean($u['first_name'] . ' ' . $u['last_name']) ?></strong>
      <span><?= clean($u['email']) ?></span>
    </div>
  </div>
  <nav class="profile-nav">
    <a href="<?= BASE_URL ?>/pages/profile.php" class="<?= $current_page==='profile'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>/pages/profile-orders.php" class="<?= $current_page==='profile-orders'?'active':'' ?>">
      <i class="fas fa-shopping-bag"></i> My Orders
    </a>
    <a href="<?= BASE_URL ?>/pages/profile-wishlist.php" class="<?= $current_page==='profile-wishlist'?'active':'' ?>">
      <i class="fas fa-heart"></i> Wishlist
    </a>
    <a href="<?= BASE_URL ?>/pages/profile-addresses.php" class="<?= $current_page==='profile-addresses'?'active':'' ?>">
      <i class="fas fa-map-marker-alt"></i> Addresses
    </a>
    <a href="<?= BASE_URL ?>/pages/profile-settings.php" class="<?= $current_page==='profile-settings'?'active':'' ?>">
      <i class="fas fa-cog"></i> Account Settings
    </a>
    <a href="<?= BASE_URL ?>/pages/track-order.php" class="<?= $current_page==='track-order'?'active':'' ?>">
      <i class="fas fa-truck"></i> Track Order
    </a>
    <div class="profile-nav-divider"></div>
    <a href="<?= BASE_URL ?>/api/auth.php?action=logout" class="text-danger">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </nav>
</aside>
