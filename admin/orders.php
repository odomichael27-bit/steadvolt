<?php
// ============================================================
//  STEADVOLT — Admin Orders Manager
//  File: admin/orders.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Orders';
$view_id    = (int)get_param('view');

// ---- UPDATE ORDER STATUS -----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/orders.php'); }

    $order_id       = (int)post('order_id');
    $status         = post('status');
    $payment_status = post('payment_status');
    $tracking       = trim(post('tracking_number'));
    $notes          = trim(post('notes'));

    $valid_statuses  = ['pending','paid','processing','shipped','delivered','cancelled','refunded'];
    $valid_pay_st    = ['unpaid','paid','failed','refunded'];

    if (!in_array($status, $valid_statuses) || !in_array($payment_status, $valid_pay_st)) {
        flash('admin', 'Invalid status value.', 'error');
        redirect('/admin/orders.php?view=' . $order_id);
    }

    $extras = '';
    $params = [$status, $payment_status, $tracking, $notes];

    // Automatically set timestamps
    if ($status === 'shipped' && $tracking) {
        $extras  = ', shipped_at = NOW()';
    }
    if ($status === 'delivered') {
        $extras .= ', delivered_at = NOW()';
    }
    if ($payment_status === 'paid') {
        $extras .= ', paid_at = COALESCE(paid_at, NOW())';
    }

    $params[] = $order_id;
    DB::query(
        "UPDATE orders SET status=?, payment_status=?, tracking_number=?, notes=? {$extras} WHERE id=?",
        $params
    );

    flash('admin', 'Order updated successfully.', 'success');
    redirect('/admin/orders.php?view=' . $order_id);
}

// ---- SINGLE ORDER VIEW -------------------------------------
if ($view_id) {
    $order = DB::row(
        "SELECT o.*, u.email AS user_email, u.first_name, u.last_name, u.phone AS user_phone
         FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?",
        [$view_id]
    );
    if (!$order) { flash('admin', 'Order not found.', 'error'); redirect('/admin/orders.php'); }
    $items = DB::all("SELECT * FROM order_items WHERE order_id=?", [$view_id]);
}

// ---- ORDER LIST filters ------------------------------------
$status_filter = get_param('status');
$search        = get_param('q');
$page_num      = max(1, (int)get_param('page', 1));
$per_page      = 20;
$where         = ['1=1'];
$params        = [];

if ($status_filter) { $where[] = 'o.status=?'; $params[] = $status_filter; }
if ($search) {
    $where[]  = '(o.order_number LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR o.guest_email LIKE ?)';
    $t        = '%'.$search.'%';
    array_push($params, $t, $t, $t, $t);
}
$where_sql = implode(' AND ', $where);
$total     = (int)DB::val(
    "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE {$where_sql}", $params
);
$pager   = paginate($total, $per_page, $page_num);
$orders  = DB::all(
    "SELECT o.*, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS customer_name,
            COALESCE(u.email, o.guest_email) AS email
     FROM orders o LEFT JOIN users u ON u.id=o.user_id
     WHERE {$where_sql}
     ORDER BY o.created_at DESC
     LIMIT {$per_page} OFFSET {$pager['offset']}",
    $params
);

// Status counts for tabs
$status_counts = [];
$rows = DB::all("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
foreach ($rows as $r) $status_counts[$r['status']] = $r['cnt'];

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">

<?php if ($view_id && isset($order)): ?>
<!-- ====================================================
     SINGLE ORDER VIEW
===================================================== -->
<div class="admin-page-header">
  <div>
    <h1>Order #<?= clean($order['order_number']) ?></h1>
    <p>Placed <?= date('D, M j Y \a\t H:i', strtotime($order['created_at'])) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/admin/orders.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> All Orders</a>
</div>

<?= flash_html('admin') ?>

<div class="order-view-grid">
  <!-- LEFT -->
  <div class="order-view-main">

    <!-- Items -->
    <div class="admin-card">
      <h3><i class="fas fa-box"></i> Order Items</h3>
      <table class="data-table">
        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <?php if ($item['product_img']): ?>
              <img src="<?= product_img_url($item['product_img']) ?>" class="table-product-img" alt=""/>
              <?php endif; ?>
              <?= clean($item['product_name']) ?>
              <?php if ($item['product_sku']): ?><br><small><?= clean($item['product_sku']) ?></small><?php endif; ?>
            </td>
            <td><?= $item['qty'] ?></td>
            <td><?= money($item['unit_price']) ?></td>
            <td><?= money($item['total_price']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3"><strong>Subtotal</strong></td><td><?= money($order['subtotal']) ?></td></tr>
          <?php if ($order['discount_amount'] > 0): ?>
          <tr class="text-green"><td colspan="3"><strong>Discount <?= $order['coupon_code'] ? '(' . clean($order['coupon_code']) . ')' : '' ?></strong></td><td>-<?= money($order['discount_amount']) ?></td></tr>
          <?php endif; ?>
          <tr><td colspan="3"><strong>Shipping</strong></td><td><?= $order['shipping_fee'] > 0 ? money($order['shipping_fee']) : '<span class="text-green">Free</span>' ?></td></tr>
          <tr class="total-row"><td colspan="3"><strong>Total</strong></td><td><strong><?= money($order['total']) ?></strong></td></tr>
        </tfoot>
      </table>
    </div>

    <!-- Update Status -->
    <div class="admin-card">
      <h3><i class="fas fa-edit"></i> Update Order</h3>
      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>"/>
        <div class="form-row-2">
          <div class="form-group">
            <label>Order Status</label>
            <select name="status">
              <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
              <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Payment Status</label>
            <select name="payment_status">
              <?php foreach (['unpaid','paid','failed','refunded'] as $s): ?>
              <option value="<?= $s ?>" <?= $order['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Tracking Number</label>
          <div class="input-wrap"><i class="fas fa-truck input-icon"></i>
            <input type="text" name="tracking_number" value="<?= clean($order['tracking_number'] ?? '') ?>" placeholder="Enter courier tracking number"/>
          </div>
        </div>
        <div class="form-group">
          <label>Internal Notes</label>
          <textarea name="notes" rows="3" placeholder="Notes visible only to admin"><?= clean($order['notes'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Order</button>
      </form>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="order-view-sidebar">
    <!-- Status -->
    <div class="admin-card">
      <h3><i class="fas fa-info-circle"></i> Status</h3>
      <div class="order-status-row">
        <span class="status-pill status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
        <span class="status-pill status-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span>
      </div>
      <?php if ($order['payment_ref']): ?>
      <p class="hint"><strong>Paystack Ref:</strong> <?= clean($order['payment_ref']) ?></p>
      <?php endif; ?>
      <?php if ($order['paid_at']): ?>
      <p class="hint"><strong>Paid at:</strong> <?= date('M j Y H:i', strtotime($order['paid_at'])) ?></p>
      <?php endif; ?>
      <?php if ($order['tracking_number']): ?>
      <p class="hint"><strong>Tracking:</strong> <?= clean($order['tracking_number']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Customer -->
    <div class="admin-card">
      <h3><i class="fas fa-user"></i> Customer</h3>
      <?php if ($order['user_id']): ?>
        <p><strong><?= clean($order['first_name'] . ' ' . $order['last_name']) ?></strong></p>
        <p><a href="mailto:<?= clean($order['user_email']) ?>"><?= clean($order['user_email']) ?></a></p>
        <p><?= clean($order['user_phone'] ?? '—') ?></p>
        <a href="<?= BASE_URL ?>/admin/customers.php?view=<?= $order['user_id'] ?>" class="btn btn-outline btn-xs">View Customer</a>
      <?php else: ?>
        <p><strong><?= clean($order['guest_name'] ?? 'Guest') ?></strong></p>
        <p><a href="mailto:<?= clean($order['guest_email'] ?? '') ?>"><?= clean($order['guest_email'] ?? '—') ?></a></p>
        <p><?= clean($order['guest_phone'] ?? '—') ?></p>
        <span class="badge">Guest Order</span>
      <?php endif; ?>
    </div>

    <!-- Shipping Address -->
    <div class="admin-card">
      <h3><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
      <address>
        <strong><?= clean($order['ship_name'] ?? '') ?></strong><br>
        <?= clean($order['ship_phone'] ?? '') ?><br>
        <?= clean($order['ship_address'] ?? '') ?><br>
        <?= clean($order['ship_city'] ?? '') ?>, <?= clean($order['ship_state'] ?? '') ?>
      </address>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ====================================================
     ORDER LIST
===================================================== -->
<div class="admin-page-header">
  <h1>Orders <small>(<?= number_format($total) ?>)</small></h1>
</div>

<!-- Status tabs -->
<div class="admin-status-tabs">
  <a href="?" class="<?= !$status_filter ? 'active' : '' ?>">All <span><?= array_sum($status_counts) ?></span></a>
  <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
  <a href="?status=<?= $s ?>" class="<?= $status_filter===$s?'active':'' ?>">
    <?= ucfirst($s) ?>
    <?php if (!empty($status_counts[$s])): ?><span><?= $status_counts[$s] ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Search -->
<div class="admin-filters">
  <form method="GET" class="filter-form">
    <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= clean($status_filter) ?>"/><?php endif; ?>
    <div class="input-wrap" style="flex:1;max-width:360px">
      <i class="fas fa-search input-icon"></i>
      <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Search by order #, email, name…"/>
    </div>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-search"></i> Search</button>
    <?php if ($search): ?><a href="?<?= $status_filter ? 'status='.$status_filter : '' ?>" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
  </form>
</div>

<div class="admin-card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong><?= clean($o['order_number']) ?></strong></td>
          <td>
            <?= clean(trim($o['customer_name']) ?: $o['guest_name'] ?? '—') ?>
            <?php if ($o['email']): ?><br><small><?= clean($o['email']) ?></small><?php endif; ?>
          </td>
          <td><?= money($o['total']) ?></td>
          <td><span class="status-pill status-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
          <td><span class="status-pill status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
          <td><?= date('M j, Y', strtotime($o['created_at'])) ?><br><small><?= date('H:i', strtotime($o['created_at'])) ?></small></td>
          <td><a href="?view=<?= $o['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-eye"></i> View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="text-center text-muted" style="padding:32px">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pager['total_pages'] > 1): ?>
  <nav class="pagination pagination-admin">
    <?php for ($i = 1; $i <= $pager['total_pages']; $i++): ?>
    <a href="?page=<?= $i ?><?= $status_filter ? '&status='.$status_filter : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="page-btn <?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
