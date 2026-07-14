<?php
// ============================================================
//  STEADVOLT — Privacy Policy
//  File: pages/privacy.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Privacy Policy';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm"><div class="container"><h1><i class="fas fa-user-shield"></i> Privacy Policy</h1><p>Last updated: <?= date('F Y') ?></p></div></div>

<section class="section-pad"><div class="container" style="max-width:800px">
<div class="profile-card" style="line-height:1.85;color:var(--gray-700)">

<h2>1. Information We Collect</h2>
<p>When you register, place an order, or contact us, we collect: your name, email address, phone number, delivery address, and order history. We also collect non-personal data such as browser type and pages visited via standard server logs.</p>

<h2 style="margin-top:28px">2. How We Use Your Information</h2>
<p>We use your information to process and fulfil orders, send order confirmations and delivery updates, respond to your enquiries, send promotional emails (only if you opt in), and improve our website and services.</p>

<h2 style="margin-top:28px">3. Payment Security</h2>
<p>All payments are processed by <strong>Paystack</strong>. SteadVolt Energy does not store, process, or transmit your card or bank details. Paystack is PCI-DSS compliant and uses bank-grade encryption.</p>

<h2 style="margin-top:28px">4. Data Sharing</h2>
<p>We do not sell, rent, or share your personal information with third parties, except: courier/logistics companies (to deliver your order), Paystack (to process payments), and as required by Nigerian law.</p>

<h2 style="margin-top:28px">5. Cookies</h2>
<p>We use session cookies to maintain your shopping cart and login state. We do not use tracking cookies or third-party advertising cookies.</p>

<h2 style="margin-top:28px">6. Your Rights</h2>
<p>You have the right to request access to, correction of, or deletion of your personal data. Contact us at <a href="mailto:<?= clean(DB::setting('site_email')) ?>"><?= clean(DB::setting('site_email')) ?></a> for any data requests.</p>

<h2 style="margin-top:28px">7. Contact</h2>
<p>Questions? Email us at <a href="mailto:<?= clean(DB::setting('site_email')) ?>"><?= clean(DB::setting('site_email')) ?></a> or call <?= clean(DB::setting('site_phone')) ?>.</p>

</div>
</div></section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
