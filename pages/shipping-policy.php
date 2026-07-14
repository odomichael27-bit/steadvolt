<?php
// ============================================================
//  STEADVOLT — Shipping Policy
//  File: pages/shipping-policy.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Shipping Policy';

$zones = DB::all("SELECT * FROM shipping_zones ORDER BY fee ASC");
$free_min = (float)DB::setting('free_shipping_min', '150000');

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm"><div class="container"><h1><i class="fas fa-truck"></i> Shipping Policy</h1><p>Nationwide delivery across all 36 states and the FCT.</p></div></div>

<section class="section-pad"><div class="container" style="max-width:860px">

  <div style="background:var(--green-light);border-radius:var(--radius-lg);padding:24px;margin-bottom:32px;display:flex;align-items:center;gap:16px">
    <i class="fas fa-gift" style="font-size:2rem;color:var(--green);flex-shrink:0"></i>
    <div>
      <strong style="display:block;margin-bottom:4px">Free Shipping Available!</strong>
      <span style="font-size:.9rem;color:var(--gray-700)">Orders totalling <?= money($free_min) ?> or more qualify for free nationwide shipping.</span>
    </div>
  </div>

  <div class="profile-card" style="margin-bottom:28px">
    <h2 style="margin-bottom:20px"><i class="fas fa-map-marked-alt" style="color:var(--green)"></i> Delivery Fees by Zone</h2>
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Zone</th><th>States Covered</th><th>Delivery Fee</th><th>Estimated Time</th></tr></thead>
      <tbody>
        <?php foreach ($zones as $z): ?>
        <tr>
          <td><strong><?= clean($z['zone_name']) ?></strong></td>
          <td style="font-size:.85rem;color:var(--gray-600)"><?= $z['states'] ? clean($z['states']) : 'All other states' ?></td>
          <td><?= $z['fee'] == 0 ? '<span class="text-green" style="font-weight:700">Free</span>' : money($z['fee']) ?></td>
          <td><?= $z['min_days'] ?>–<?= $z['max_days'] ?> business days</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="profile-card" style="margin-bottom:28px">
    <h2 style="margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--green)"></i> Delivery Information</h2>
    <ul style="color:var(--gray-600);line-height:2;padding-left:20px;font-size:.9rem">
      <li>All orders are processed within 1 business day of payment confirmation.</li>
      <li>Delivery times are estimates and begin counting from the dispatch date, not the order date.</li>
      <li>You will receive a tracking number via email once your order ships.</li>
      <li>For large or fragile items (solar panels, batteries), special courier handling may add 1–2 extra days.</li>
      <li>We currently do not offer same-day delivery.</li>
      <li>Public holidays may affect delivery timelines.</li>
      <li>A valid phone number is required for all deliveries — our courier partners will call before arrival.</li>
    </ul>
  </div>

  <div style="background:var(--off-white);border-radius:var(--radius-lg);padding:28px;text-align:center">
    <h3 style="margin-bottom:8px">Track Your Order</h3>
    <p style="color:var(--gray-600);margin-bottom:20px">Already placed an order? Check its status anytime.</p>
    <a href="<?= BASE_URL ?>/pages/track-order.php" class="btn btn-primary"><i class="fas fa-search"></i> Track Order</a>
  </div>
</div></section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
