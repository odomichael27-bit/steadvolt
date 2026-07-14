<?php
// ============================================================
//  STEADVOLT — About Us Page
//  File: pages/about.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'About Us';
$meta_desc  = 'Learn about SteadVolt Energy — Nigeria\'s trusted source for solar panels, batteries, inverters and smart cameras.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm">
  <div class="container">
    <h1 style="flex-direction:column;gap:8px;text-align:center">
      <i class="fas fa-bolt"></i><br>About SteadVolt Energy
    </h1>
    <p>Powering homes and businesses across Nigeria with clean, reliable energy solutions.</p>
  </div>
</div>

<!-- STORY -->
<section class="section-pad">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center">
      <div>
        <span class="tag">Our Story</span>
        <h2 class="section-title">Born from Nigeria's Energy Challenge</h2>
        <p style="color:var(--gray-600);line-height:1.8;margin-bottom:16px">
          SteadVolt Energy was founded in 2025 with one mission: to make premium, reliable energy products accessible to every Nigerian home and business — at fair prices.
        </p>
        <p style="color:var(--gray-600);line-height:1.8;margin-bottom:16px">
          We grew from a small engineering workshop in Enugu into a nationwide e-commerce platform, trusted by over 5,000 customers across all 36 states and the FCT.
        </p>
        <p style="color:var(--gray-600);line-height:1.8">
          Every product in our catalogue — from Tier-1 solar panels and lithium batteries to AI-powered CCTV cameras — is carefully selected, quality-tested, and backed by a manufacturer warranty.
        </p>
      </div>
      <div style="background:linear-gradient(135deg,var(--forest),#0e3028);border-radius:var(--radius-xl);padding:48px;color:#fff;text-align:center">
        <div style="font-size:4rem;margin-bottom:16px;color:var(--green)"><i class="fas fa-bolt"></i></div>
        <h3 style="font-family:var(--font-display);font-size:1.6rem;margin-bottom:8px">Our Mission</h3>
        <p style="color:rgba(255,255,255,.75);line-height:1.75">To end energy poverty in Nigeria by delivering world-class solar, battery, inverter and security technology — sustainably and affordably.</p>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="section-pad bg-off-white">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center">
      <?php
      $stats = [
        ['5,000+', 'Systems Installed', 'fa-solar-panel'],
        ['36', 'States Covered', 'fa-map-marker-alt'],
        ['98%', 'Satisfaction Rate', 'fa-star'],
        ['2–5 yr', 'Product Warranty', 'fa-shield-alt'],
      ];
      foreach ($stats as $s): ?>
      <div style="background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:32px 20px">
        <div style="width:56px;height:56px;border-radius:var(--radius);background:var(--green-light);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin:0 auto 16px">
          <i class="fas <?= $s[2] ?>"></i>
        </div>
        <div style="font-family:var(--font-display);font-size:2rem;font-weight:800;color:var(--forest);margin-bottom:4px"><?= $s[0] ?></div>
        <div style="font-size:.85rem;color:var(--gray-500)"><?= $s[1] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- WHY US -->
<section class="why-us section-pad">
  <div class="container">
    <div class="section-header centered">
      <span class="tag">Why Choose Us</span>
      <h2 class="section-title">What Sets SteadVolt Apart</h2>
    </div>
    <div class="why-grid">
      <div class="why-card"><div class="why-icon"><i class="fas fa-certificate"></i></div><h3>Tier-1 Certified Products</h3><p>We only stock products certified by international quality bodies — no knock-offs, no compromises.</p></div>
      <div class="why-card"><div class="why-icon"><i class="fas fa-headset"></i></div><h3>Expert Support Team</h3><p>Our certified engineers are available 24/7 to guide you from purchase to installation and beyond.</p></div>
      <div class="why-card"><div class="why-icon"><i class="fas fa-truck"></i></div><h3>Nationwide Delivery</h3><p>We deliver to all 36 states. Lagos in 1–2 days. Free shipping on orders above ₦150,000.</p></div>
      <div class="why-card"><div class="why-icon"><i class="fas fa-lock"></i></div><h3>Secure Payments</h3><p>Pay safely via Paystack — card, bank transfer, or USSD. Every transaction is encrypted end-to-end.</p></div>
      <div class="why-card"><div class="why-icon"><i class="fas fa-undo"></i></div><h3>30-Day Returns</h3><p>Not satisfied? Return defective products within 30 days of delivery — no hassle, no argument.</p></div>
      <div class="why-card"><div class="why-icon"><i class="fas fa-leaf"></i></div><h3>Eco Commitment</h3><p>We are proud to contribute to Nigeria's clean energy future, one installation at a time.</p></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-banner">
  <div class="container">
    <div class="cta-content">
      <h2>Ready to Power Your Future?</h2>
      <p>Join thousands of Nigerians who have already made the switch to clean, reliable energy.</p>
      <div class="cta-btns">
        <a href="<?= BASE_URL ?>/shop.php" class="btn btn-white"><i class="fas fa-store"></i> Shop Now</a>
        <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-outline btn-outline-white"><i class="fas fa-envelope"></i> Contact Us</a>
      </div>
    </div>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
