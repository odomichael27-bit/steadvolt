<?php
// ============================================================
//  STEADVOLT — Terms of Service
//  File: pages/terms.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Terms of Service';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm"><div class="container"><h1><i class="fas fa-file-contract"></i> Terms of Service</h1><p>Last updated: <?= date('F Y') ?></p></div></div>

<section class="section-pad"><div class="container" style="max-width:800px">
<div class="profile-card" style="line-height:1.85;color:var(--gray-700)">

<h2>1. Acceptance of Terms</h2>
<p>By accessing and using the SteadVolt Energy website, you agree to be bound by these Terms of Service. If you do not agree, please do not use our website.</p>

<h2 style="margin-top:28px">2. Product Information</h2>
<p>We make every effort to display accurate product descriptions, images, and prices. However, we reserve the right to correct any errors and to cancel orders placed based on inaccurate information, with full refunds issued.</p>

<h2 style="margin-top:28px">3. Orders & Payment</h2>
<p>All orders are subject to availability and confirmation. We reserve the right to refuse or cancel any order at our discretion. Payment must be completed in full before goods are dispatched. Prices are in Nigerian Naira (₦) and include VAT where applicable.</p>

<h2 style="margin-top:28px">4. Delivery</h2>
<p>Delivery timelines are estimates and may vary due to factors outside our control (courier delays, weather, public holidays). SteadVolt Energy is not liable for delays once goods are handed to the courier.</p>

<h2 style="margin-top:28px">5. Returns & Warranty</h2>
<p>Our returns and warranty policy is detailed separately on our <a href="<?= BASE_URL ?>/pages/returns-warranty.php">Returns & Warranty page</a>. That policy forms part of these terms.</p>

<h2 style="margin-top:28px">6. Intellectual Property</h2>
<p>All content on this website — including text, images, logos, and code — is owned by SteadVolt Energy and may not be copied, reproduced, or distributed without written permission.</p>

<h2 style="margin-top:28px">7. Limitation of Liability</h2>
<p>SteadVolt Energy shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or website, beyond the purchase price of the relevant product.</p>

<h2 style="margin-top:28px">8. Governing Law</h2>
<p>These terms are governed by the laws of the Federal Republic of Nigeria. Any disputes shall be subject to the exclusive jurisdiction of Nigerian courts.</p>

<h2 style="margin-top:28px">9. Changes to Terms</h2>
<p>We reserve the right to update these terms at any time. Continued use of the website after changes are posted constitutes acceptance of the new terms.</p>

<h2 style="margin-top:28px">10. Contact</h2>
<p>Questions about these terms? Contact us at <a href="mailto:<?= clean(DB::setting('site_email')) ?>"><?= clean(DB::setting('site_email')) ?></a>.</p>

</div>
</div></section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
