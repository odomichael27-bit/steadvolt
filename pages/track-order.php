<?php
// ============================================================
//  STEADVOLT — Track Order (Public)
//  File: pages/track-order.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title  = 'Track Your Order';
$order_num   = strtoupper(trim(get_param('order', post('order'))));
$order       = null;
$items       = [];

if ($order_num) {
    $order = DB::row(
        "SELECT o.*, u.email AS user_email
         FROM orders o LEFT JOIN users u ON u.id=o.user_id
         WHERE o.order_number=?",
        [$order_num]
    );
    if ($order) {
        $items = DB::all("SELECT * FROM order_items WHERE order_id=?", [$order['id']]);
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm">
  <div class="container">
    <h1><i class="fas fa-truck"></i> Track Your Order</h1>
    <p>Enter your order number to see real-time status updates.</p>
  </div>
</div>

<section class="section-pad">
  <div class="container" style="max-width:700px">
    <?= flash_html('order') ?>

    <div class="profile-card">
      <form method="GET" action="" novalidate>
        <div class="track-search">
          <div class="input-wrap" style="flex:1">
            <i class="fas fa-search input-icon"></i>
            <input type="text" name="order" value="<?= clean($order_num) ?>"
                   placeholder="e.g. SV-A1B2C3-20250101" required
                   autocomplete="off"/>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Track</button>
        </div>
      </form>
    </div>

    <?php if ($order_num && !$order): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> No order found with number <strong><?= clean($order_num) ?></strong>. Please check and try again.</div>
    <?php endif; ?>

    <?php if ($order): ?>
    <div class="order-track-card">
      <div class="order-track-header">
        <div>
          <h2><?= clean($order['order_number']) ?></h2>
          <p>Placed on <?= date('D, M j Y', strtotime($order['created_at'])) ?></p>
        </div>
        <div>
          <span class="status-pill status-<?= $order['status'] ?>" style="font-size:1rem"><?= ucfirst($order['status']) ?></span>
          <span class="status-pill status-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span>
        </div>
      </div>

      <!-- Progress bar -->
      <?php
      $steps = ['pending'=>0,'paid'=>1,'processing'=>2,'shipped'=>3,'delivered'=>4];
      $current_step = $steps[$order['status']] ?? 0;
      if (in_array($order['status'], ['cancelled','refunded'])) $current_step = -1;
      ?>
      <?php if ($current_step >= 0): ?>
      <div class="track-progress">
        <?php $step_labels = ['Order Placed','Payment Confirmed','Processing','Shipped','Delivered']; ?>
        <?php $step_icons  = ['fa-shopping-cart','fa-credit-card','fa-cogs','fa-truck','fa-check-circle']; ?>
        <?php foreach ($step_labels as $i => $label): ?>
        <div class="track-step <?= $current_step > $i ? 'done' : ($current_step === $i ? 'active' : '') ?>">
          <div class="track-step-dot"><i class="fas <?= $step_icons[$i] ?>"></i></div>
          <span><?= $label ?></span>
        </div>
        <?php if ($i < count($step_labels)-1): ?>
        <div class="track-step-line <?= $current_step > $i ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Tracking number -->
      <?php if ($order['tracking_number']): ?>
      <div class="track-number-box">
        <i class="fas fa-truck"></i>
        <div>
          <strong>Tracking Number</strong>
          <span><?= clean($order['tracking_number']) ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- Delivery address -->
      <div class="order-track-address">
        <h4><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
        <p><?= clean($order['ship_name']) ?><br>
           <?= clean($order['ship_phone']) ?><br>
           <?= clean($order['ship_address']) ?>, <?= clean($order['ship_city']) ?>, <?= clean($order['ship_state']) ?></p>
      </div>

      <!-- Items -->
      <div class="order-track-items">
        <h4><i class="fas fa-box"></i> Items (<?= count($items) ?>)</h4>
        <?php foreach ($items as $item): ?>
        <div class="order-track-item">
          <?php if ($item['product_img']): ?>
          <img src="<?= product_img_url($item['product_img']) ?>" alt="<?= clean($item['product_name']) ?>"/>
          <?php endif; ?>
          <div>
            <strong><?= clean($item['product_name']) ?></strong>
            <span>Qty: <?= $item['qty'] ?> × <?= money($item['unit_price']) ?></span>
          </div>
          <span><?= money($item['total_price']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary -->
      <div class="order-track-totals">
        <div><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
        <?php if ($order['discount_amount'] > 0): ?>
        <div class="text-green"><span>Discount</span><span>-<?= money($order['discount_amount']) ?></span></div>
        <?php endif; ?>
        <div><span>Shipping</span><span><?= $order['shipping_fee'] > 0 ? money($order['shipping_fee']) : 'Free' ?></span></div>
        <div class="total-row"><strong>Total</strong><strong><?= money($order['total']) ?></strong></div>
      </div>

      <div class="order-track-help">
        <i class="fas fa-headset"></i>
        <p>Have questions? <a href="<?= BASE_URL ?>/pages/contact.php">Contact us</a> or WhatsApp <a href="https://wa.me/<?= preg_replace('/\D/', '', DB::setting('site_whatsapp')) ?>">+<?= preg_replace('/\D/', '', DB::setting('site_whatsapp')) ?></a></p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
