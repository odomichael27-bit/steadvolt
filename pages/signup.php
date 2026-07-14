<?php
// ============================================================
//  STEADVOLT — Sign Up Page
//  File: pages/signup.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) redirect('/index.php');

$redirect   = get_param('redirect', '/index.php');
$page_title = 'Create Account';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-visual">
    <div class="auth-visual-top">
      <a href="<?= BASE_URL ?>/index.php" class="footer-logo" style="color:#fff">
        <div class="logo-mark"><i class="fas fa-bolt"></i></div>
        <span>Stead<em style="color:var(--green)">Volt</em></span>
      </a>
    </div>
    <div class="auth-visual-mid">
      <h2>Join thousands of Nigerians who trust SteadVolt for their energy needs.</h2>
      <p>Create an account to track orders, save your wishlist, and enjoy a faster checkout experience.</p>
    </div>
    <div class="auth-visual-features">
      <div><i class="fas fa-check-circle"></i> Track all your orders in one place</div>
      <div><i class="fas fa-check-circle"></i> Save multiple delivery addresses</div>
      <div><i class="fas fa-check-circle"></i> Get exclusive member discounts</div>
      <div><i class="fas fa-check-circle"></i> Faster checkout every time</div>
    </div>
  </div>

  <div class="auth-form-side">
    <div class="auth-form-card">
      <div class="auth-mobile-logo">
        <div class="logo-mark"><i class="fas fa-bolt"></i></div>
        <span>Volt<em style="color:var(--green)">Peak</em></span>
      </div>

      <?= flash_html('auth') ?>

      <h1>Create Account</h1>
      <p class="auth-sub">Fill in your details to get started.</p>

      <form method="POST" action="<?= BASE_URL ?>/api/auth.php" id="signupForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="register"/>
        <input type="hidden" name="redirect" value="<?= clean($redirect) ?>"/>

        <div class="form-row-2">
          <div class="form-group">
            <label>First Name</label>
            <div class="input-wrap">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="first_name" placeholder="John" required autocomplete="given-name" maxlength="80"/>
            </div>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <div class="input-wrap">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="last_name" placeholder="Doe" required autocomplete="family-name" maxlength="80"/>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" placeholder="you@example.com" required autocomplete="email"/>
          </div>
        </div>

        <div class="form-group">
          <label>Phone Number</label>
          <div class="input-wrap">
            <i class="fas fa-phone input-icon"></i>
            <input type="tel" name="phone" placeholder="+234 800 0000000" autocomplete="tel" maxlength="20"/>
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="signupPw" placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password"/>
            <button type="button" class="toggle-pw" aria-label="Toggle password"><i class="fas fa-eye"></i></button>
          </div>
          <div class="pw-strength-bar"><div id="pwBar"></div></div>
          <span id="pwStrengthLabel" class="pw-strength-label"></span>
        </div>

        <div class="form-group">
          <label>Confirm Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password_confirm" id="signupPwConfirm" placeholder="Repeat password" required autocomplete="new-password"/>
            <button type="button" class="toggle-pw" aria-label="Toggle password"><i class="fas fa-eye"></i></button>
          </div>
        </div>

        <label class="checkbox-label">
          <input type="checkbox" name="terms" required value="1"/>
          <p>I agree to the <a href="<?= BASE_URL ?>/pages/terms.php" target="_blank">Terms of Service</a> and <a href="<?= BASE_URL ?>/pages/privacy.php" target="_blank">Privacy Policy</a></p>
        </label>

        <button type="submit" class="btn btn-primary auth-submit-btn">
          Create Account <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <p class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/pages/login.php?redirect=<?= urlencode($redirect) ?>">Sign In</a></p>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
