<?php
// ============================================================
//  STEADVOLT — Admin Store Settings
//  File: admin/settings.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Store Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/settings.php'); }

    $settings = [
        'site_name','site_tagline','site_email','site_phone','site_address','site_whatsapp',
        'currency_symbol','currency_code','free_shipping_min',
        'paystack_public_key','paystack_secret_key',
        'google_places_api_key','google_place_id',
        'smartsupp_key',
        'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from_name','smtp_from_email',
        'otp_expiry_minutes',
        'announcement_bar',
        'facebook_url','instagram_url','twitter_url','whatsapp_url',
        'chatbot_welcome',
        'tax_rate','maintenance_mode',
        'google_reviews_cache_hours',
    ];

    foreach ($settings as $key) {
        $val = post($key);
        DB::setSetting($key, $val);
    }

    flash('admin', 'Settings saved successfully!', 'success');
    redirect('/admin/settings.php');
}

require_once dirname(__DIR__) . '/includes/admin-header.php';

// Helper to get current setting
function sv(string $key): string { return clean(DB::setting($key)); }
?>

<div class="admin-content">
  <div class="admin-page-header">
    <h1>Store Settings</h1>
    <p>All configuration for your SteadVolt store in one place.</p>
  </div>

  <?= flash_html('admin') ?>

  <form method="POST" novalidate>
    <?= csrf_field() ?>

    <!-- ── GENERAL ──────────────────────────────────── -->
    <div class="settings-section">
      <h2><i class="fas fa-store"></i> General</h2>
      <div class="settings-grid">
        <div class="form-group">
          <label>Store Name *</label>
          <div class="input-wrap"><i class="fas fa-store input-icon"></i><input type="text" name="site_name" value="<?= sv('site_name') ?>" required/></div>
        </div>
        <div class="form-group">
          <label>Tagline</label>
          <div class="input-wrap"><i class="fas fa-quote-left input-icon"></i><input type="text" name="site_tagline" value="<?= sv('site_tagline') ?>"/></div>
        </div>
        <div class="form-group">
          <label>Contact Email</label>
          <div class="input-wrap"><i class="fas fa-envelope input-icon"></i><input type="email" name="site_email" value="<?= sv('site_email') ?>"/></div>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <div class="input-wrap"><i class="fas fa-phone input-icon"></i><input type="text" name="site_phone" value="<?= sv('site_phone') ?>"/></div>
        </div>
        <div class="form-group">
          <label>WhatsApp Number (with country code)</label>
          <div class="input-wrap"><i class="fab fa-whatsapp input-icon"></i><input type="text" name="site_whatsapp" value="<?= sv('site_whatsapp') ?>" placeholder="+2348001234567"/></div>
        </div>
        <div class="form-group">
          <label>Address</label>
          <div class="input-wrap"><i class="fas fa-map-marker-alt input-icon"></i><input type="text" name="site_address" value="<?= sv('site_address') ?>"/></div>
        </div>
        <div class="form-group">
          <label>Announcement Bar Text</label>
          <div class="input-wrap"><i class="fas fa-bullhorn input-icon"></i><input type="text" name="announcement_bar" value="<?= sv('announcement_bar') ?>" placeholder="Leave blank to hide"/></div>
        </div>
        <div class="form-group">
          <label>Maintenance Mode</label>
          <select name="maintenance_mode">
            <option value="0" <?= DB::setting('maintenance_mode')==='0'?'selected':'' ?>>Off (Store is live)</option>
            <option value="1" <?= DB::setting('maintenance_mode')==='1'?'selected':'' ?>>On (Show maintenance page)</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ── CURRENCY & PRICING ──────────────────────── -->
    <div class="settings-section">
      <h2><i class="fas fa-naira-sign"></i> Currency & Pricing</h2>
      <div class="settings-grid">
        <div class="form-group">
          <label>Currency Symbol</label>
          <div class="input-wrap"><input type="text" name="currency_symbol" value="<?= sv('currency_symbol') ?>" maxlength="5"/></div>
        </div>
        <div class="form-group">
          <label>Currency Code</label>
          <div class="input-wrap"><input type="text" name="currency_code" value="<?= sv('currency_code') ?>" maxlength="5" placeholder="NGN"/></div>
        </div>
        <div class="form-group">
          <label>Free Shipping Minimum (₦)</label>
          <div class="input-wrap"><i class="fas fa-truck input-icon"></i><input type="number" name="free_shipping_min" value="<?= sv('free_shipping_min') ?>" min="0"/></div>
        </div>
        <div class="form-group">
          <label>Tax Rate (%)</label>
          <div class="input-wrap"><input type="number" name="tax_rate" value="<?= sv('tax_rate') ?>" min="0" max="100" step="0.01"/></div>
          <p class="hint">Set 0 to disable tax.</p>
        </div>
      </div>
    </div>

    <!-- ── PAYSTACK ─────────────────────────────────── -->
    <div class="settings-section">
      <h2><i class="fas fa-credit-card"></i> Paystack Payment</h2>
      <div class="settings-hint">
        <i class="fas fa-info-circle"></i>
        Get your keys from <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank">Paystack Dashboard → Settings → API Keys</a>.
        Use <strong>Test keys</strong> for staging, <strong>Live keys</strong> for production.
      </div>
      <div class="settings-grid">
        <div class="form-group">
          <label>Paystack Public Key</label>
          <div class="input-wrap"><i class="fas fa-key input-icon"></i><input type="text" name="paystack_public_key" value="<?= sv('paystack_public_key') ?>" placeholder="pk_live_…"/></div>
        </div>
        <div class="form-group">
          <label>Paystack Secret Key</label>
          <div class="input-wrap"><i class="fas fa-lock input-icon"></i><input type="password" name="paystack_secret_key" value="<?= sv('paystack_secret_key') ?>" placeholder="sk_live_…"/></div>
          <p class="hint"><i class="fas fa-shield-alt"></i> This key is stored securely and never exposed to the front end.</p>
        </div>
      </div>
      <div class="settings-hint settings-hint-warn">
        <i class="fas fa-exclamation-triangle"></i>
        Set your Paystack Webhook URL to: <code><?= BASE_URL ?>/api/payment.php?action=webhook</code>
      </div>
    </div>

    <!-- ── GOOGLE ────────────────────────────────────── -->
    <div class="settings-section">
      <h2><i class="fab fa-google"></i> Google Reviews</h2>
      <div class="settings-hint">
        <i class="fas fa-info-circle"></i>
        Enable live Google reviews on your homepage. Get your API key from
        <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> (enable Places API).
        Find your Place ID at <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Google's Place ID Finder</a>.
      </div>
      <div class="settings-grid">
        <div class="form-group">
          <label>Google Places API Key</label>
          <div class="input-wrap"><i class="fas fa-key input-icon"></i><input type="text" name="google_places_api_key" value="<?= sv('google_places_api_key') ?>" placeholder="AIzaSy…"/></div>
        </div>
        <div class="form-group">
          <label>Google Place ID (your business)</label>
          <div class="input-wrap"><i class="fas fa-map-pin input-icon"></i><input type="text" name="google_place_id" value="<?= sv('google_place_id') ?>" placeholder="ChIJ…"/></div>
        </div>
        <div class="form-group">
          <label>Cache Duration (hours)</label>
          <div class="input-wrap"><i class="fas fa-clock input-icon"></i><input type="number" name="google_reviews_cache_hours" value="<?= sv('google_reviews_cache_hours') ?>" min="1" max="168"/></div>
          <p class="hint">Reviews are cached to avoid excessive API calls. Refresh manually from the dashboard.</p>
        </div>
      </div>
    </div>

    <!-- ── SMARTSUPP ────────────────────────────────── -->
    <div class="settings-section">
      <h2><i class="fas fa-headset"></i> Smartsupp Live Chat</h2>
      <div class="settings-hint">
        <i class="fas fa-info-circle"></i>
        Get your Smartsupp key from <a href="https://app.smartsupp.com/settings/account/chat-code" target="_blank">Smartsupp → Settings → Chat Code</a>.
        Leave blank to disable Smartsupp (the built-in chatbot will still work).
      </div>
      <div class="settings-grid">
        <div class="form-group">
          <label>Smartsupp Key</label>
          <div class="input-wrap"><i class="fas fa-key input-icon"></i><input type="text" name="smartsupp_key" value="<?= sv('smartsupp_key') ?>" placeholder="Your Smartsupp key"/></div>
        </div>
        <div class="form-group">
          <label>Chatbot Welcome Message</label>
          <div class="input-wrap"><i class="fas fa-comment input-icon"></i><input type="text" name="chatbot_welcome" value="<?= sv('chatbot_welcome') ?>"/></div>
        </div>
      </div>
    </div>

    <!-- ── EMAIL / SMTP ──────────────────────────────── -->
    <div class="settings-section">
      <h2><i class="fas fa-mail-bulk"></i> Email / SMTP</h2>
      <div class="settings-hint">
        <i class="fas fa-info-circle"></i>
        Used to send OTP emails, order confirmations, and welcome emails.
        For Gmail: use <code>smtp.gmail.com</code>, port <code>587</code>, and an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a>.
      </div>
      <div class="settings-grid">
        <div class="form-group">
          <label>SMTP Host</label>
          <div class="input-wrap"><i class="fas fa-server input-icon"></i><input type="text" name="smtp_host" value="<?= sv('smtp_host') ?>" placeholder="smtp.gmail.com"/></div>
        </div>
        <div class="form-group">
          <label>SMTP Port</label>
          <div class="input-wrap"><input type="number" name="smtp_port" value="<?= sv('smtp_port') ?>" placeholder="587"/></div>
        </div>
        <div class="form-group">
          <label>SMTP Username (Email)</label>
          <div class="input-wrap"><i class="fas fa-envelope input-icon"></i><input type="email" name="smtp_user" value="<?= sv('smtp_user') ?>"/></div>
        </div>
        <div class="form-group">
          <label>SMTP Password / App Password</label>
          <div class="input-wrap"><i class="fas fa-lock input-icon"></i><input type="password" name="smtp_pass" value="<?= sv('smtp_pass') ?>"/></div>
        </div>
        <div class="form-group">
          <label>From Name</label>
          <div class="input-wrap"><i class="fas fa-user input-icon"></i><input type="text" name="smtp_from_name" value="<?= sv('smtp_from_name') ?>"/></div>
        </div>
        <div class="form-group">
          <label>From Email</label>
          <div class="input-wrap"><i class="fas fa-envelope input-icon"></i><input type="email" name="smtp_from_email" value="<?= sv('smtp_from_email') ?>"/></div>
        </div>
        <div class="form-group">
          <label>OTP Expiry (minutes)</label>
          <div class="input-wrap"><i class="fas fa-clock input-icon"></i><input type="number" name="otp_expiry_minutes" value="<?= sv('otp_expiry_minutes') ?>" min="5" max="60"/></div>
        </div>
      </div>
    </div>

    <!-- ── SOCIAL MEDIA ─────────────────────────────── -->
    <div class="settings-section">
      <h2><i class="fas fa-share-alt"></i> Social Media</h2>
      <div class="settings-grid">
        <div class="form-group">
          <label>Facebook URL</label>
          <div class="input-wrap"><i class="fab fa-facebook input-icon"></i><input type="url" name="facebook_url" value="<?= sv('facebook_url') ?>" placeholder="https://facebook.com/steadvolt"/></div>
        </div>
        <div class="form-group">
          <label>Instagram URL</label>
          <div class="input-wrap"><i class="fab fa-instagram input-icon"></i><input type="url" name="instagram_url" value="<?= sv('instagram_url') ?>" placeholder="https://instagram.com/steadvolt"/></div>
        </div>
        <div class="form-group">
          <label>Twitter / X URL</label>
          <div class="input-wrap"><i class="fab fa-twitter input-icon"></i><input type="url" name="twitter_url" value="<?= sv('twitter_url') ?>" placeholder="https://twitter.com/steadvolt"/></div>
        </div>
      </div>
    </div>

    <div class="form-actions sticky-actions">
      <button type="submit" class="btn btn-primary btn-lg">
        <i class="fas fa-save"></i> Save All Settings
      </button>
    </div>
  </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
