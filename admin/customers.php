<?php
// ============================================================
//  STEADVOLT — Admin Customers Manager
//  File: admin/customers.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Customers';
$view_id    = (int)get_param('view');
$search     = get_param('q');
$page_num   = max(1, (int)get_param('page', 1));
$per_page   = 20;

// ── SINGLE CUSTOMER VIEW ────────────────────────────────────
if ($view_id) {
    $customer = DB::row("SELECT * FROM users WHERE id=? AND role='customer'", [$view_id]);
    if (!$customer) { flash('admin','Customer not found.','error'); redirect('/admin/customers.php'); }
    $cust_orders = DB::all(
        "SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 20",
        [$view_id]
    );
    $total_spent = (float)DB::val("SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND payment_status='paid'", [$view_id]);
}

// ── LIST ────────────────────────────────────────────────────
$where = ["role = 'customer'"];
$params = [];
if ($search) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $t = '%'.$search.'%';
    array_push($params, $t, $t, $t, $t);
}
$ws    = implode(' AND ', $where);
$total = (int)DB::val("SELECT COUNT(*) FROM users WHERE $ws", $params);
$pager = paginate($total, $per_page, $page_num);
$customers = DB::all(
    "SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count,
            (SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=u.id AND payment_status='paid') AS total_spent
     FROM users u WHERE $ws ORDER BY u.created_at DESC LIMIT $per_page OFFSET {$pager['offset']}",
    $params
);

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">

<?php if ($view_id && isset($customer)): ?>
<!-- SINGLE CUSTOMER -->
<div class="admin-page-header">
  <div>
    <h1><?= clean($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
    <p>Customer since <?= date('M j, Y', strtotime($customer['created_at'])) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/admin/customers.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> All Customers</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px">
  <div class="kpi-card">
    <div class="kpi-icon kpi-blue"><i class="fas fa-shopping-bag"></i></div>
    <div class="kpi-data"><span class="kpi-label">Total Orders</span><strong class="kpi-value"><?= count($cust_orders) ?></strong></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon kpi-green"><i class="fas fa-naira-sign"></i></div>
    <div class="kpi-data"><span class="kpi-label">Total Spent</span><strong class="kpi-value"><?= money($total_spent) ?></strong></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon kpi-purple"><i class="fas fa-heart"></i></div>
    <div class="kpi-data"><span class="kpi-label">Wishlist Items</span><strong class="kpi-value"><?= DB::val("SELECT COUNT(*) FROM wishlist WHERE user_id=?",[$view_id]) ?></strong></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:24px">
  <div class="admin-card">
    <h3>Profile</h3>
    <div style="text-align:center;margin-bottom:16px">
      <?php if ($customer['avatar']): ?>
        <img src="<?= avatar_url($customer['avatar']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #00A86B" alt="avatar"/>
      <?php else: ?>
        <div style="width:80px;height:80px;border-radius:50%;background:#00A86B;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto;"><?= strtoupper(substr($customer['first_name'],0,1).substr($customer['last_name'],0,1)) ?></div>
      <?php endif; ?>
    </div>
    <table style="width:100%;font-size:.85rem;border-collapse:collapse">
      <tr><td style="padding:6px 0;color:#6b7280;width:50%"><b>Email</b></td><td><?= clean($customer['email']) ?></td></tr>
      <tr><td style="padding:6px 0;color:#6b7280"><b>Phone</b></td><td><?= clean($customer['phone'] ?? '—') ?></td></tr>
      <tr><td style="padding:6px 0;color:#6b7280"><b>Status</b></td><td><span class="status-pill <?= $customer['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $customer['is_active'] ? 'Active' : 'Inactive' ?></span></td></tr>
      <tr><td style="padding:6px 0;color:#6b7280"><b>Joined</b></td><td><?= date('M j, Y', strtotime($customer['created_at'])) ?></td></tr>
    </table>
  </div>
  <div class="admin-card">
    <h3>Recent Orders</h3>
    <?php if (empty($cust_orders)): ?>
    <p class="empty-state-sm">No orders yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Order #</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cust_orders as $o): ?>
      <tr>
        <td><strong><?= clean($o['order_number']) ?></strong></td>
        <td><?= money($o['total']) ?></td>
        <td><span class="status-pill status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
        <td><span class="status-pill status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
        <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
        <td><a href="<?= BASE_URL ?>/admin/orders.php?view=<?= $o['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-eye"></i></a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- CUSTOMERS LIST -->
<div class="admin-page-header">
  <h1>Customers <small>(<?= number_format($total) ?>)</small></h1>
</div>

<div class="admin-filters">
  <form method="GET" class="filter-form">
    <div class="input-wrap" style="flex:1;max-width:360px">
      <i class="fas fa-search input-icon"></i>
      <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Search name, email, phone…"/>
    </div>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-search"></i> Search</button>
    <?php if ($search): ?><a href="?" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
  </form>
</div>

<div class="admin-card">
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Spent</th><th>Status</th><th>Joined</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <?php if ($c['avatar']): ?>
            <img src="<?= avatar_url($c['avatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt=""/>
          <?php else: ?>
            <div style="width:36px;height:36px;border-radius:50%;background:#00A86B;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0"><?= strtoupper(substr($c['first_name'],0,1).substr($c['last_name'],0,1)) ?></div>
          <?php endif; ?>
          <span><?= clean($c['first_name'].' '.$c['last_name']) ?></span>
        </div>
      </td>
      <td><?= clean($c['email']) ?></td>
      <td><?= clean($c['phone'] ?? '—') ?></td>
      <td><?= $c['order_count'] ?></td>
      <td><?= money((float)$c['total_spent']) ?></td>
      <td><span class="status-pill <?= $c['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
      <td><a href="?view=<?= $c['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-eye"></i></a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($customers)): ?><tr><td colspan="8" class="text-center text-muted" style="padding:32px">No customers found.</td></tr><?php endif; ?>
    </tbody>
  </table></div>

  <?php if ($pager['total_pages'] > 1): ?>
  <nav class="pagination pagination-admin">
    <?php for ($i = 1; $i <= $pager['total_pages']; $i++): ?>
    <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn <?= $i===$pager['current']?'active':''?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
