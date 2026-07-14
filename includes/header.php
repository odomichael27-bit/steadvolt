<?php
// ============================================================
//  STEADVOLT — Header Template
//  File: includes/header.php
// ============================================================
require_once __DIR__ . '/functions.php';
sv_session_start();

$site_name = DB::setting('site_name', 'SteadVolt Energy');
$announce  = DB::setting('announcement_bar', '');
$user      = auth_user();
$cart_qty  = cart_count();
$current   = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= isset($page_title) ? clean($page_title) . ' — ' : '' ?><?= clean($site_name) ?></title>
  <?php if (isset($meta_desc)): ?>
  <meta name="description" content="<?= clean($meta_desc) ?>"/>
  <?php endif; ?>
  <!-- Favicon (inline SVG bolt icon — no extra file/request needed) -->
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='20' fill='%2300A86B'/%3E%3Cpath d='M55 15 L30 55 H48 L42 85 L72 42 H53 Z' fill='white'/%3E%3C/svg%3E"/>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">

  <!-- fort awesome 5 -->
    <link href="<?= BASE_URL ?>/fontawesome5/css/all.css" rel="stylesheet">
  <!-- Main CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"/>
  <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>

<?php if ($announce): ?>
<div class="announcement-bar">
  <i class="fas fa-truck"></i> <?= clean($announce) ?>
</div>
<?php endif; ?>

<header id="header">
  <div class="container">
    <nav class="nav-inner">
      <!-- Logo -->
      <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
        <div class="logo-mark"><i class="fas fa-bolt"></i></div>
        <span>Stead<em>Volt</em></span>
      </a>

      <!-- Desktop Links -->
      <ul class="nav-links">
        <li><a href="<?= BASE_URL ?>/index.php" <?= $current==='index'?'class="active"':'' ?>>Home</a></li>
        <li><a href="<?= BASE_URL ?>/shop.php"   <?= $current==='shop'  ?'class="active"':'' ?>>Shop</a></li>
        <li><a href="<?= BASE_URL ?>/solar.php"  <?= $current==='solar' ?'class="active"':'' ?>>Solar</a></li>
        <li><a href="<?= BASE_URL ?>/batteries.php" <?= $current==='batteries'?'class="active"':'' ?>>Batteries</a></li>
        <li><a href="<?= BASE_URL ?>/cameras.php"   <?= $current==='cameras'  ?'class="active"':'' ?>>Cameras</a></li>
        <li><a href="<?= BASE_URL ?>/pages/about.php" <?= $current==='about'?'class="active"':'' ?>>About</a></li>
      </ul>

      <!-- Actions -->
      <div class="nav-actions">
        <div class="search-bar" id="searchBar">
          <i class="far fa-search"></i>
          <input type="text" id="siteSearch" placeholder="Search products…" autocomplete="off"/>
          <div id="searchDropdown" class="search-dropdown hidden"></div>
        </div>

        <?php if ($user): ?>
        <div class="nav-user-dropdown">
          <button class="nav-icon-btn user-toggle" title="Account">
            <?php if ($user['avatar']): ?>
              <img src="<?= avatar_url($user['avatar']) ?>" class="nav-avatar" alt="avatar"/>
            <?php else: ?>
              <i class="fas fa-user-circle" style="font-size:1.4rem"></i>
            <?php endif; ?>
          </button>
          <div class="user-dropdown-menu">
            <div class="udm-header">
              <strong><?= clean($user['first_name'] . ' ' . $user['last_name']) ?></strong>
              <span><?= clean($user['email']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user"></i> My Account</a>
            <a href="<?= BASE_URL ?>/pages/profile-orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
            <a href="<?= BASE_URL ?>/pages/profile-wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
            <?php if (is_admin()): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Panel</a>
            <?php endif; ?>
            <div class="udm-divider"></div>
            <a href="<?= BASE_URL ?>/api/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/pages/login.php" class="nav-icon-btn" title="Sign In">
          <i class="fas fa-user"></i>
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/cart.php" class="nav-icon-btn cart-btn" aria-label="Cart">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-badge" id="cartBadge"><?= $cart_qty ?></span>
        </a>

        <button class="hamburger" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
  </div>
</header>

<!-- Mobile Nav -->
<nav class="mobile-nav">
  <button class="close-btn" aria-label="Close menu"><i class="fas fa-times"></i></button>
  <a href="<?= BASE_URL ?>/index.php"><i class="fas fa-home"></i> Home</a>
  <a href="<?= BASE_URL ?>/shop.php"><i class="fas fa-store"></i> Shop</a>
  <a href="<?= BASE_URL ?>/solar.php"><i class="fas fa-sun"></i> Solar Systems</a>
  <a href="<?= BASE_URL ?>/batteries.php"><i class="fas fa-battery-full"></i> Batteries</a>
  <a href="<?= BASE_URL ?>/cameras.php"><i class="fas fa-video"></i> Cameras</a>
  <a href="<?= BASE_URL ?>/pages/about.php"><i class="fas fa-info-circle"></i> About Us</a>
  <a href="<?= BASE_URL ?>/pages/contact.php"><i class="fas fa-envelope"></i> Contact</a>
  <a href="<?= BASE_URL ?>/cart.php"><i class="fas fa-shopping-cart"></i> Cart (<?= $cart_qty ?>)</a>
  <?php if ($user): ?>
  <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user"></i> My Account</a>
  <a href="<?= BASE_URL ?>/api/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  <?php else: ?>
  <a href="<?= BASE_URL ?>/pages/login.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
  <?php endif; ?>
</nav>
<div class="nav-overlay"></div>
