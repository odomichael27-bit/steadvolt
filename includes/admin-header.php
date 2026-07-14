<?php
// ============================================================
//  STEADVOLT — Admin Header
//  File: includes/admin-header.php
// ============================================================
require_once __DIR__ . '/functions.php';
require_admin();

$current_admin = basename($_SERVER['PHP_SELF'], '.php');
$site_name     = DB::setting('site_name', 'SteadVolt Energy');
$user          = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= isset($page_title) ? clean($page_title) . ' — ' : '' ?>Admin | <?= clean($site_name) ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='20' fill='%231B4D3E'/%3E%3Cpath d='M55 15 L30 55 H48 L42 85 L72 42 H53 Z' fill='%2300A86B'/%3E%3C/svg%3E"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/fontawesome5/css/all.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css"/>
  <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body class="admin-body">

<div class="admin-shell">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-logo">
      <div class="logo-mark"><i class="fas fa-bolt"></i></div>
      <span>Stead<em>Volt</em> <small>Admin</small></span>
    </div>

    <nav class="admin-nav">
      <div class="admin-nav-label">Main</div>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $current_admin==='dashboard'?'active':'' ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="<?= $current_admin==='orders'?'active':'' ?>">
        <i class="fas fa-shopping-bag"></i> Orders
        <?php $pending=(int)DB::val("SELECT COUNT(*) FROM orders WHERE status='pending'"); ?>
        <?php if ($pending): ?><span class="nav-count"><?= $pending ?></span><?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>/admin/products.php" class="<?= $current_admin==='products'?'active':'' ?>">
        <i class="fas fa-box"></i> Products
      </a>
      <a href="<?= BASE_URL ?>/admin/customers.php" class="<?= $current_admin==='customers'?'active':'' ?>">
        <i class="fas fa-users"></i> Customers
      </a>
      <a href="<?= BASE_URL ?>/admin/reviews.php" class="<?= $current_admin==='reviews'?'active':'' ?>">
        <i class="fas fa-star"></i> Reviews
        <?php $unrev=(int)DB::val("SELECT COUNT(*) FROM reviews WHERE is_approved=0"); ?>
        <?php if ($unrev): ?><span class="nav-count"><?= $unrev ?></span><?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>/admin/messages.php" class="<?= $current_admin==='messages'?'active':'' ?>">
        <i class="fas fa-envelope"></i> Messages
        <?php $unread=(int)DB::val("SELECT COUNT(*) FROM contact_messages WHERE is_read=0"); ?>
        <?php if ($unread): ?><span class="nav-count"><?= $unread ?></span><?php endif; ?>
      </a>
      <div class="admin-nav-label">Catalogue</div>
      <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= $current_admin==='categories'?'active':'' ?>">
        <i class="fas fa-tags"></i> Categories
      </a>
      <a href="<?= BASE_URL ?>/admin/brands.php" class="<?= $current_admin==='brands'?'active':'' ?>">
        <i class="fas fa-trademark"></i> Brands
      </a>
      <a href="<?= BASE_URL ?>/admin/coupons.php" class="<?= $current_admin==='coupons'?'active':'' ?>">
        <i class="fas fa-ticket-alt"></i> Coupons
      </a>
      <div class="admin-nav-label">Settings</div>
      <a href="<?= BASE_URL ?>/admin/settings.php" class="<?= $current_admin==='settings'?'active':'' ?>">
        <i class="fas fa-cog"></i> Store Settings
      </a>
      <a href="<?= BASE_URL ?>/admin/shipping.php" class="<?= $current_admin==='shipping'?'active':'' ?>">
        <i class="fas fa-truck"></i> Shipping Zones
      </a>
      <a href="<?= BASE_URL ?>/admin/chatbot.php" class="<?= $current_admin==='chatbot'?'active':'' ?>">
        <i class="fas fa-robot"></i> Chatbot Replies
      </a>
      <a href="<?= BASE_URL ?>/admin/profile.php" class="<?= $current_admin==='profile'?'active':'' ?>">
        <i class="fas fa-user-circle"></i> My Profile
      </a>
      <div class="admin-nav-divider"></div>
      <a href="<?= BASE_URL ?>/index.php" target="_blank">
        <i class="fas fa-external-link-alt"></i> View Storefront
      </a>
      <a href="<?= BASE_URL ?>/api/auth.php?action=logout" class="text-danger">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </nav>
  </aside>
  <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

  <!-- MAIN -->
  <div class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:14px">
        <button class="admin-hamburger" id="adminHamburger" aria-label="Menu">
          <i class="fas fa-bars"></i>
        </button>
        <h2 class="admin-page-title"><?= $page_title ?? 'Dashboard' ?></h2>
      </div>
      <div class="admin-topbar-right">
        <a href="<?= BASE_URL ?>/admin/messages.php" class="admin-icon-btn" title="Messages">
          <i class="fas fa-bell"></i>
          <?php if ($unread ?? 0): ?><span class="dot"></span><?php endif; ?>
        </a>
        <div class="admin-user-chip" onclick="location.href='<?= BASE_URL ?>/admin/profile.php'" style="cursor:pointer" title="My Profile">
          <?php if ($user['avatar']): ?>
          <img src="<?= avatar_url($user['avatar']) ?>" class="av-img" alt="avatar"/>
          <?php else: ?>
          <div class="av"><?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?></div>
          <?php endif; ?>
          <span><?= clean($user['first_name']) ?></span>
        </div>
      </div>
    </div>

    <!-- Flash messages -->
    <?= flash_html('admin') ?>
