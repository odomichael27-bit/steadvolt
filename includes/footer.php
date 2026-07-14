<?php
// ============================================================
//  STEADVOLT — Footer Template (with chatbot & Smartsupp)
//  File: includes/footer.php
// ============================================================
$smartsupp_key    = DB::setting('smartsupp_key', '');
$whatsapp         = DB::setting('site_whatsapp', '');
$fb               = DB::setting('facebook_url', '');
$ig               = DB::setting('instagram_url', '');
$tw               = DB::setting('twitter_url', '');
$chatbot_welcome  = DB::setting('chatbot_welcome', 'Hi! How can I help you?');
$site_name        = DB::setting('site_name', 'SteadVolt Energy');
$site_email       = DB::setting('site_email', 'hello@steadvolt.ng');
$site_phone       = DB::setting('site_phone', '+234 800 8658732');
$site_address     = DB::setting('site_address', '14 Adeola Odeku, VI, Lagos');
?>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Brand -->
      <div class="footer-brand">
        <a href="<?= BASE_URL ?>/index.php" class="footer-logo">
          <div class="logo-mark"><i class="fas fa-bolt"></i></div>
          <span>Stead<em>Volt</em></span>
        </a>
        <p>Nigeria's trusted source for premium solar energy, batteries, inverters, and smart cameras. Power your future today.</p>
        <div class="footer-social">
          <?php if ($fb): ?><a href="<?= clean($fb) ?>" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
          <?php if ($ig): ?><a href="<?= clean($ig) ?>" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
          <?php if ($tw): ?><a href="<?= clean($tw) ?>" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a><?php endif; ?>
          <?php if ($whatsapp): ?><a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsapp) ?>" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
        </div>
      </div>

      <!-- Products -->
      <div class="footer-col">
        <h4>Products</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>/solar.php">Solar Systems</a></li>
          <li><a href="<?= BASE_URL ?>/batteries.php">Deep Cycle Batteries</a></li>
          <li><a href="<?= BASE_URL ?>/cameras.php">Smart Cameras</a></li>
          <li><a href="<?= BASE_URL ?>/shop.php?cat=inverters">Power Inverters</a></li>
          <li><a href="<?= BASE_URL ?>/shop.php?cat=accessories">Accessories</a></li>
        </ul>
      </div>

      <!-- Support -->
      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>/pages/track-order.php">Track Order</a></li>
          <li><a href="<?= BASE_URL ?>/pages/faq.php">FAQ</a></li>
          <li><a href="<?= BASE_URL ?>/pages/shipping-policy.php">Shipping Policy</a></li>
          <li><a href="<?= BASE_URL ?>/pages/returns-warranty.php">Returns & Warranty</a></li>
          <li><a href="<?= BASE_URL ?>/pages/installation-guide.php">Installation Guide</a></li>
          <li><a href="<?= BASE_URL ?>/pages/contact.php">Contact Us</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="footer-contact-list">
          <li><i class="fas fa-phone"></i> <a href="tel:<?= preg_replace('/\s/', '', $site_phone) ?>"><?= clean($site_phone) ?></a></li>
          <li><i class="fas fa-envelope"></i> <a href="mailto:<?= clean($site_email) ?>"><?= clean($site_email) ?></a></li>
          <li><i class="fas fa-map-marker-alt"></i> <?= clean($site_address) ?></li>
          <li><i class="fas fa-clock"></i> Mon–Sat 8am–7pm | Sun 10am–4pm</li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> DGDev. All rights reserved.</p>
      <div class="footer-bottom-links">
        <a href="<?= BASE_URL ?>/pages/privacy.php">Privacy Policy</a>
        <a href="<?= BASE_URL ?>/pages/terms.php">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<!-- =====================================================
     BUILT-IN CHATBOT
     ===================================================== -->
<div id="chatbotWidget" class="chatbot-widget">
  <button id="chatbotToggle" class="chatbot-toggle" aria-label="Open chat">
    <i class="fas fa-comment-dots chatbot-icon-open"></i>
    <i class="fas fa-times chatbot-icon-close hidden"></i>
    <span class="chatbot-unread hidden" id="chatbotUnread">1</span>
  </button>

  <div id="chatbotBox" class="chatbot-box hidden">
    <div class="chatbot-header">
      <div class="chatbot-header-info">
        <div class="chatbot-avatar"><i class="fas fa-bolt"></i></div>
        <div>
          <strong>SteadVolt Assistant</strong>
          <span class="chatbot-status"><i class="fas fa-circle"></i> Online</span>
        </div>
      </div>
      <button class="chatbot-close-btn" id="chatbotClose"><i class="fas fa-times"></i></button>
    </div>

    <div id="chatbotMessages" class="chatbot-messages">
      <!-- Initial bot message added by JS -->
    </div>

    <div class="chatbot-input-area">
      <input type="text" id="chatbotInput" placeholder="Type your message…" autocomplete="off" maxlength="300"/>
      <button id="chatbotSend" aria-label="Send"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>
</div>

<?php if ($whatsapp): ?>
<a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsapp) ?>?text=Hello+SteadVolt" class="whatsapp-float" target="_blank" aria-label="WhatsApp Chat">
  <i class="fab fa-whatsapp"></i>
</a>
<?php endif; ?>

<!-- Main JS -->
<script>
  const BASE_URL = '<?= BASE_URL ?>';
  const CHATBOT_WELCOME = <?= json_encode($chatbot_welcome) ?>;
  const CART_COUNT = <?= cart_count() ?>;
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<?php if (isset($extra_js)) echo $extra_js; ?>

<!-- Smartsupp Live Chat -->
<?php if ($smartsupp_key): ?>
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = '<?= clean($smartsupp_key) ?>';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
<?php if ($user): ?>
smartsupp('name',  '<?= clean($user['first_name'] . ' ' . $user['last_name']) ?>');
smartsupp('email', '<?= clean($user['email']) ?>');
<?php endif; ?>
</script>
<noscript>Powered by <a href="https://www.smartsupp.com" target="_blank">Smartsupp</a></noscript>
<?php endif; ?>

</body>
</html>
