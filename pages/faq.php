<?php
// ============================================================
//  STEADVOLT — FAQ Page
//  File: pages/faq.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Frequently Asked Questions';
require_once dirname(__DIR__) . '/includes/header.php';

$faqs = [
  'Ordering & Payment' => [
    ['Do I need an account to shop?', 'No! You can browse products and add items to your cart without creating an account. However, you will need to sign in or create a free account when you are ready to pay.'],
    ['What payment methods do you accept?', 'We accept all major debit/credit cards, bank transfers, and USSD payments via Paystack. All transactions are 100% secure and encrypted.'],
    ['Is it safe to pay on SteadVolt?', 'Yes. All payments are processed by Paystack — Nigeria\'s leading payment gateway — using bank-grade SSL encryption. We never store your card details.'],
    ['Can I get a bulk/wholesale discount?', 'Yes! Contact us via WhatsApp or our contact form for bulk pricing on orders of 5 or more units of any product.'],
    ['How do I apply a coupon code?', 'On the cart page, scroll to the checkout section, enter your coupon code in the "Coupon Code" field and click Apply.'],
  ],
  'Shipping & Delivery' => [
    ['Where do you deliver?', 'We deliver to all 36 states and the FCT across Nigeria. No location is too far!'],
    ['How long does delivery take?', 'Lagos: 1–2 business days. South West (Ogun, Oyo, Osun, Ekiti, Ondo): 2–4 days. South East/South South: 3–5 days. Northern states: 4–8 days.'],
    ['Is there free shipping?', 'Yes! Orders totalling ₦150,000 or more qualify for free shipping nationwide.'],
    ['How do I track my order?', 'Once your order ships, you will receive a tracking number by email. Use it on our Track Order page or contact us directly.'],
    ['Can I change my delivery address after ordering?', 'Contact us immediately after placing your order. We can update the address before the item ships.'],
  ],
  'Products & Warranty' => [
    ['Are your products genuine?', 'All SteadVolt products are 100% genuine, sourced directly from authorised manufacturers and distributors. We stock only Tier-1 certified brands.'],
    ['What warranty do your products come with?', 'Solar panels: 25-year performance warranty, 10-year product warranty. Batteries: 2–3 years. Inverters: 1–2 years. Cameras: 1–2 years. Accessories: 6–12 months.'],
    ['Do you provide installation services?', 'We provide installation guides with every purchase. On-site installation by certified engineers is available in Lagos, Abuja, and Port Harcourt — contact us to book.'],
    ['Can I return a product?', 'Yes. We accept returns of defective products within 30 days of delivery. The product must be in its original packaging. Contact us first to initiate a return.'],
    ['Are solar panel prices per panel or per set?', 'All prices are displayed clearly per unit (per panel, per battery, etc.) on the product page.'],
  ],
  'Account & Profile' => [
    ['How do I reset my password?', 'Click "Forgot Password" on the sign-in page. Enter your email and we will send a 6-digit OTP. Use the OTP to set a new password instantly.'],
    ['Can I update my profile details?', 'Yes. Go to My Account → Account Settings to update your name, phone number, profile photo, and password.'],
    ['How do I save a delivery address?', 'Go to My Account → Addresses. You can save multiple addresses and set one as your default for faster checkout.'],
    ['How do I cancel or modify an order?', 'Contact us immediately after placing your order. Orders can be modified or cancelled before they are dispatched. Once shipped, cancellation is not possible.'],
  ],
];
?>

<div class="page-hero-sm">
  <div class="container"><h1><i class="fas fa-question-circle"></i> FAQ</h1><p>Answers to our most common questions.</p></div>
</div>

<section class="section-pad">
  <div class="container" style="max-width:860px">
    <?php foreach ($faqs as $section => $items): ?>
    <div style="margin-bottom:48px">
      <h2 style="font-size:1.2rem;color:var(--forest);margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid var(--green-light);display:flex;align-items:center;gap:10px">
        <i class="fas fa-chevron-right" style="color:var(--green);font-size:.9rem"></i> <?= $section ?>
      </h2>
      <div style="display:flex;flex-direction:column;gap:2px">
        <?php foreach ($items as $i => $faq): ?>
        <div class="faq-item" id="faq-<?= md5($faq[0]) ?>">
          <button class="faq-q" onclick="toggleFaq(this)" aria-expanded="false">
            <?= clean($faq[0]) ?>
            <i class="fas fa-chevron-down faq-chevron"></i>
          </button>
          <div class="faq-a"><?= clean($faq[1]) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="background:var(--green-light);border-radius:var(--radius-lg);padding:28px;text-align:center;margin-top:16px">
      <i class="fas fa-headset" style="font-size:2rem;color:var(--green);margin-bottom:12px;display:block"></i>
      <h3 style="margin-bottom:8px">Still have questions?</h3>
      <p style="color:var(--gray-600);margin-bottom:20px">Our support team is available Mon–Sat 8am–7pm.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-primary"><i class="fas fa-envelope"></i> Send a Message</a>
        <a href="https://wa.me/<?= preg_replace('/\D/', '', DB::setting('site_whatsapp')) ?>" target="_blank" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp Chat</a>
      </div>
    </div>
  </div>
</section>

<style>
.faq-item { border:1px solid var(--gray-200); border-radius:var(--radius); margin-bottom:6px; overflow:hidden; }
.faq-q { width:100%; display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:var(--white); font-size:.92rem; font-weight:600; color:var(--gray-900); text-align:left; cursor:pointer; border:none; gap:12px; transition:background var(--transition); }
.faq-q:hover { background:var(--off-white); }
.faq-q[aria-expanded="true"] { background:var(--green-light); color:var(--forest); }
.faq-chevron { flex-shrink:0; color:var(--green); transition:transform .25s; font-size:.8rem; }
.faq-q[aria-expanded="true"] .faq-chevron { transform:rotate(180deg); }
.faq-a { display:none; padding:0 20px 16px; font-size:.9rem; color:var(--gray-600); line-height:1.75; background:var(--off-white); }
.faq-a.open { display:block; }
</style>

<script>
function toggleFaq(btn) {
  var isOpen = btn.getAttribute('aria-expanded') === 'true';
  btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
  var ans = btn.nextElementSibling;
  if (ans) ans.classList.toggle('open', !isOpen);
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
