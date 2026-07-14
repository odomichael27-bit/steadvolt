<?php
// ============================================================
//  STEADVOLT — Returns & Warranty Policy Page
//  File: pages/returns-warranty.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Returns & Warranty Policy';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm">
  <div class="container"><h1><i class="fas fa-shield-alt"></i> Returns & Warranty</h1><p>Our commitment to your satisfaction and peace of mind.</p></div>
</div>

<section class="section-pad">
  <div class="container" style="max-width:860px">

    <div class="profile-card" style="margin-bottom:28px">
      <h2><i class="fas fa-undo" style="color:var(--green)"></i> Return Policy</h2>
      <p style="margin-bottom:14px;color:var(--gray-600)">We stand behind every product we sell. If you receive a defective or damaged item, we make it right.</p>
      <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:12px">
        <?php
        $returns = [
          ['fa-calendar-check', '30-Day Return Window', 'Report defective products within 30 calendar days of delivery.'],
          ['fa-box', 'Original Packaging Required', 'Items must be returned in original packaging with all accessories and documentation.'],
          ['fa-file-alt', 'Proof of Purchase', 'Your order number (sent via email) is required to process any return.'],
          ['fa-ban', 'Non-Returnable Items', 'Items damaged due to improper installation, misuse, or neglect are not eligible for return. Custom-built systems are also non-returnable.'],
          ['fa-truck', 'Return Shipping', 'For manufacturer defects confirmed by our team, we cover the return shipping cost. For change-of-mind returns, the customer bears shipping costs.'],
          ['fa-clock', 'Refund Timeline', 'Approved refunds are processed within 5–7 business days to your original payment method.'],
        ];
        foreach ($returns as $r): ?>
        <li style="display:flex;align-items:flex-start;gap:14px;padding:14px;background:var(--off-white);border-radius:var(--radius)">
          <div style="width:40px;height:40px;border-radius:var(--radius-sm);background:var(--green-light);color:var(--green);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas <?= $r[0] ?>"></i>
          </div>
          <div>
            <strong style="display:block;margin-bottom:3px;font-size:.9rem"><?= $r[1] ?></strong>
            <span style="font-size:.85rem;color:var(--gray-600)"><?= $r[2] ?></span>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="profile-card" style="margin-bottom:28px">
      <h2><i class="fas fa-certificate" style="color:var(--green)"></i> Warranty Coverage</h2>
      <p style="margin-bottom:20px;color:var(--gray-600)">All SteadVolt products come with manufacturer warranties. Below is the standard coverage by category:</p>
      <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Product Category</th><th>Product Warranty</th><th>Performance Warranty</th><th>Coverage</th></tr></thead>
        <tbody>
          <tr><td><i class="fas fa-sun" style="color:var(--green)"></i> Solar Panels</td><td>10 years</td><td>25 years (80% output)</td><td>Manufacturing defects, delamination, frame corrosion</td></tr>
          <tr><td><i class="fas fa-battery-full" style="color:var(--green)"></i> Lithium Batteries</td><td>3 years</td><td>—</td><td>Cell failure, BMS faults, capacity degradation below 70%</td></tr>
          <tr><td><i class="fas fa-battery-full" style="color:var(--green)"></i> AGM / GEL Batteries</td><td>2 years</td><td>—</td><td>Cell failure, terminal corrosion, abnormal discharge</td></tr>
          <tr><td><i class="fas fa-bolt" style="color:var(--green)"></i> Inverters</td><td>1–2 years</td><td>—</td><td>Component failure under normal use conditions</td></tr>
          <tr><td><i class="fas fa-video" style="color:var(--green)"></i> IP / CCTV Cameras</td><td>1–2 years</td><td>—</td><td>Image sensor failure, PCB defects, housing seal failure</td></tr>
          <tr><td><i class="fas fa-plug" style="color:var(--green)"></i> Accessories</td><td>6–12 months</td><td>—</td><td>Manufacturing defects under normal use</td></tr>
        </tbody>
      </table></div>
    </div>

    <div class="profile-card" style="margin-bottom:28px">
      <h2><i class="fas fa-times-circle" style="color:var(--danger)"></i> What Is NOT Covered</h2>
      <ul style="color:var(--gray-600);line-height:2;padding-left:20px;font-size:.9rem">
        <li>Damage from improper installation not following our guide</li>
        <li>Physical damage (dropped, cracked, bent, flooded)</li>
        <li>Damage from power surges or lightning not covered by surge protection</li>
        <li>Normal wear and tear (fading, minor scratches)</li>
        <li>Unauthorised repairs or modifications</li>
        <li>Products used outside their rated specifications</li>
      </ul>
    </div>

    <div style="background:var(--green-light);border-radius:var(--radius-lg);padding:28px;text-align:center">
      <h3 style="margin-bottom:8px">Need to Make a Claim?</h3>
      <p style="color:var(--gray-600);margin-bottom:20px">Contact our support team with your order number and a clear photo/video of the issue.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-primary"><i class="fas fa-envelope"></i> Contact Support</a>
        <a href="https://wa.me/<?= preg_replace('/\D/', '', DB::setting('site_whatsapp')) ?>" target="_blank" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
