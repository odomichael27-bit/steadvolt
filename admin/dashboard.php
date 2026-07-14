<?php
// ============================================================
//  STEADVOLT — Admin Dashboard
//  File: admin/dashboard.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Admin Dashboard';

// KPI stats
$today        = date('Y-m-d');
$this_month   = date('Y-m-01');
$last_month_s = date('Y-m-01', strtotime('-1 month'));
$last_month_e = date('Y-m-t', strtotime('-1 month'));

$revenue_today   = (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=? AND payment_status='paid'", [$today]);
$revenue_month   = (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE created_at>=? AND payment_status='paid'", [$this_month]);
$orders_today    = (int)DB::val("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=?", [$today]);
$orders_pending  = (int)DB::val("SELECT COUNT(*) FROM orders WHERE status='pending'");
$total_customers = (int)DB::val("SELECT COUNT(*) FROM users WHERE role='customer'");
$new_customers   = (int)DB::val("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at>=?", [$this_month]);
$low_stock       = (int)DB::val("SELECT COUNT(*) FROM products WHERE stock <= low_stock_alert AND is_active=1");
$unread_msgs     = (int)DB::val("SELECT COUNT(*) FROM contact_messages WHERE is_read=0");

// Revenue last 7 days
$revenue_7days = DB::all(
    "SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS total
     FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND payment_status='paid'
     GROUP BY DATE(created_at) ORDER BY day ASC"
);

// Recent orders
$recent_orders = DB::all(
    "SELECT o.*, CONCAT(u.first_name,' ',u.last_name) AS customer_name
     FROM orders o LEFT JOIN users u ON u.id=o.user_id
     ORDER BY o.created_at DESC LIMIT 10"
);

// Top products
$top_products = DB::all(
    "SELECT p.name, SUM(oi.qty) AS units_sold, SUM(oi.total_price) AS revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN orders o ON o.id = oi.order_id AND o.payment_status='paid'
     GROUP BY oi.product_id ORDER BY units_sold DESC LIMIT 5"
);

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1>Dashboard</h1>
      <p>Welcome back, <?= clean(auth_user()['first_name']) ?>. Here's what's happening with your store today.</p>
    </div>
    <div class="admin-topbar-actions">
      <a href="<?= BASE_URL ?>/api/google_reviews.php?admin_refresh=1" class="btn btn-outline btn-sm" id="refreshReviews">
        <i class="fab fa-google"></i> Refresh Reviews
      </a>
      <a href="<?= BASE_URL ?>/admin/products.php?action=add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Add Product
      </a>
    </div>
  </div>

  <!-- KPI CARDS -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon kpi-green"><i class="far fa-naira-sign"></i></div>
      <div class="kpi-data">
        <span class="kpi-label">Revenue This Month</span>
        <strong class="kpi-value"><?= money($revenue_month) ?></strong>
        <span class="kpi-sub">Today: <?= money($revenue_today) ?></span>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon kpi-blue"><i class="fas fa-shopping-bag"></i></div>
      <div class="kpi-data">
        <span class="kpi-label">Orders Today</span>
        <strong class="kpi-value"><?= $orders_today ?></strong>
        <span class="kpi-sub kpi-alert"><?= $orders_pending ?> pending</span>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon kpi-purple"><i class="fas fa-users"></i></div>
      <div class="kpi-data">
        <span class="kpi-label">Total Customers</span>
        <strong class="kpi-value"><?= number_format($total_customers) ?></strong>
        <span class="kpi-sub">+<?= $new_customers ?> this month</span>
      </div>
    </div>
    <div class="kpi-card <?= $low_stock > 0 ? 'kpi-card-warn' : '' ?>">
      <div class="kpi-icon kpi-orange"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="kpi-data">
        <span class="kpi-label">Low Stock Alerts</span>
        <strong class="kpi-value"><?= $low_stock ?></strong>
        <a href="<?= BASE_URL ?>/admin/products.php?filter=low_stock" class="kpi-sub">View products →</a>
      </div>
    </div>
    <?php if ($unread_msgs > 0): ?>
    <div class="kpi-card kpi-card-warn">
      <div class="kpi-icon kpi-red"><i class="fas fa-envelope"></i></div>
      <div class="kpi-data">
        <span class="kpi-label">Unread Messages</span>
        <strong class="kpi-value"><?= $unread_msgs ?></strong>
        <a href="<?= BASE_URL ?>/admin/messages.php" class="kpi-sub">View messages →</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- CHARTS ROW -->
  <div class="admin-charts-row">
    <!-- Revenue Chart -->
    <div class="admin-card chart-card">
      <div class="admin-card-header">
        <h3>Revenue — Last 7 Days</h3>
      </div>
      <canvas id="revenueChart" height="200"></canvas>
    </div>

    <!-- Top Products -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3>Top Selling Products</h3>
        <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline btn-xs">All Products</a>
      </div>
      <?php if (empty($top_products)): ?>
      <p class="empty-state-sm">No sales data yet.</p>
      <?php else: ?>
      <ul class="top-products-list">
        <?php foreach ($top_products as $i => $tp): ?>
        <li>
          <span class="tp-rank"><?= $i+1 ?></span>
          <span class="tp-name"><?= clean($tp['name']) ?></span>
          <span class="tp-units"><?= $tp['units_sold'] ?> sold</span>
          <span class="tp-rev"><?= money($tp['revenue']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- RECENT ORDERS -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3>Recent Orders</h3>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline btn-xs">View All</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_orders as $o): ?>
          <tr>
            <td><strong><?= clean($o['order_number']) ?></strong></td>
            <td><?= clean($o['customer_name'] ?: $o['guest_name'] ?: '—') ?></td>
            <td><?= money($o['total']) ?></td>
            <td><span class="status-pill status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
            <td><span class="status-pill status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td><?= date('M j, H:i', strtotime($o['created_at'])) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/admin/orders.php?view=<?= $o['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-eye"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
(function(){
  const labels = <?= json_encode(array_column($revenue_7days, 'day')) ?>;
  const values = <?= json_encode(array_map(fn($r) => (float)$r['total'], $revenue_7days)) ?>;
  const ctx = document.getElementById('revenueChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.map(d => new Date(d).toLocaleDateString('en-NG',{weekday:'short',month:'short',day:'numeric'})),
      datasets: [{
        label: 'Revenue (₦)',
        data: values,
        backgroundColor: 'rgba(0,168,107,0.7)',
        borderColor: '#00A86B',
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { ticks: { callback: v => '₦' + v.toLocaleString() } }
      }
    }
  });

  // Refresh Google reviews via AJAX
  document.getElementById('refreshReviews')?.addEventListener('click', function(e){
    e.preventDefault();
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing…';
    fetch(this.href).then(r=>r.json()).then(d=>{
      this.innerHTML = d.ok
        ? '<i class="fas fa-check"></i> ' + d.msg
        : '<i class="fas fa-times"></i> ' + d.msg;
    }).catch(()=>{ this.innerHTML='<i class="fas fa-times"></i> Error'; });
  });
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
