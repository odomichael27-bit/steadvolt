<?php
// ============================================================
//  STEADVOLT — My Account / Profile
//  File: pages/profile.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_login('/pages/login.php?redirect=/pages/profile.php');

$user_id  = auth_user()['id'];
$user     = DB::row("SELECT * FROM users WHERE id=?", [$user_id]);
$page_title = 'My Account';

// Stats
$total_orders   = DB::val("SELECT COUNT(*) FROM orders WHERE user_id=?", [$user_id]);
$total_spent    = DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND payment_status='paid'", [$user_id]);
$wishlist_count = DB::val("SELECT COUNT(*) FROM wishlist WHERE user_id=?", [$user_id]);
$recent_orders  = DB::all(
    "SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5",
    [$user_id]
);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="profile-page">
  <div class="container">
    <div class="profile-layout">

      <!-- SIDEBAR NAV -->
      <?php include dirname(__DIR__) . '/includes/profile-nav.php'; ?>

      <!-- MAIN -->
      <main class="profile-main">
        <?= flash_html('profile') ?>

        <!-- Welcome banner -->
        <div class="profile-welcome">
          <div class="profile-welcome-avatar">
            <?php if ($user['avatar']): ?>
              <img src="<?= avatar_url($user['avatar']) ?>" alt="Avatar"/>
            <?php else: ?>
              <div class="avatar-initials"><?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?></div>
            <?php endif; ?>
          </div>
          <div>
            <h1>Hi, <?= clean($user['first_name']) ?>! 👋</h1>
            <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?>
              
          </p>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="profile-stats-grid">
          <div class="profile-stat-card">
            <i class="fas fa-shopping-bag"></i>
            <div><strong><?= number_format($total_orders) ?></strong><span>Total Orders</span></div>
          </div>
          <div class="profile-stat-card">
            <i class="fas fa-naira-sign"></i>
            <div><strong><?= money((float)$total_spent) ?></strong><span>Total Spent</span></div>
          </div>
          <div class="profile-stat-card">
            <i class="fas fa-heart"></i>
            <div><strong><?= number_format($wishlist_count) ?></strong><span>Wishlist Items</span></div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
            <a href="<?= BASE_URL ?>/pages/profile-orders.php" class="btn btn-outline btn-sm">View All</a>
          </div>
          <?php if (empty($recent_orders)): ?>
          <div class="empty-state small">
            <i class="fas fa-box-open"></i>
            <p>You haven't placed any orders yet. <a href="<?= BASE_URL ?>/shop.php">Start shopping →</a></p>
          </div>
          <?php else: ?>
          <div class="orders-table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                  <td><strong><?= clean($order['order_number']) ?></strong></td>
                  <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                  <td><?= DB::val("SELECT SUM(qty) FROM order_items WHERE order_id=?", [$order['id']]) ?></td>
                  <td><?= money($order['total']) ?></td>
                  <td><span class="status-pill status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                  <td><a href="<?= BASE_URL ?>/pages/track-order.php?order=<?= clean($order['order_number']) ?>" class="btn btn-outline btn-xs">Track</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
