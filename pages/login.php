<?php
// ============================================================
//  STEADVOLT — Login Page
//  File: pages/login.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

// Already logged in
if (is_logged_in()) {
    redirect('/index.php');
}

$redirect = get_param('redirect', '/index.php');
$page_title = 'Sign In';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="auth-page">
  <!-- LEFT VISUAL -->
  <div class="auth-visual">
    <div class="auth-visual-top">
      <a href="<?= BASE_URL ?>/index.php" class="footer-logo" style="color:#fff">
        <div class="logo-mark"><i class="fas fa-bolt"></i></div>
        <span>Stead<em style="color:var(--green)">Volt</em></span>
      </a>
    </div>
    <div class="auth-visual-mid">
      <h2>Welcome back to your clean energy command center.</h2>
      <p>Track orders, manage your installations, and reorder your favourite solar and security products in seconds.</p>
    </div>
    <div class="auth-visual-stats">
      <div><strong>5,000+</strong><span>Happy Customers</span></div>
      <div><strong>36</strong><span>States Covered</span></div>
      <div><strong>24/7</strong><span>Support</span></div>
    </div>
  </div>

  <!-- RIGHT FORM -->
  <div class="auth-form-side">
    <div class="auth-form-card">
      <div class="auth-mobile-logo">
        <div class="logo-mark"><i class="fas fa-bolt"></i></div>
        <span>Volt<em style="color:var(--green)">Peak</em></span>
      </div>

      <?= flash_html('auth') ?>

      <h1>Sign In</h1>
      <p class="auth-sub">Enter your details to access your account.</p>

      <form method="POST" action="<?= BASE_URL ?>/api/auth.php" id="loginForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login"/>
        <input type="hidden" name="redirect" value="<?= clean($redirect) ?>"/>

        <div class="form-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" placeholder="you@example.com" required autocomplete="email"/>
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="loginPw" placeholder="Your password" required autocomplete="current-password"/>
            <button type="button" class="toggle-pw" aria-label="Toggle password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="remember-row">
          <label><input type="checkbox" name="remember" value="1"/> Remember me</label>
          <a href="<?= BASE_URL ?>/pages/forgot-password.php">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary auth-submit-btn">
          Sign In <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <p class="auth-switch">Don't have an account? <a href="<?= BASE_URL ?>/pages/signup.php?redirect=<?= urlencode($redirect) ?>">Create Account</a></p>
      <p class="auth-guest-note">
        <i class="fas fa-info-circle"></i>
        You can <a href="<?= BASE_URL ?>/shop.php">browse and add to cart</a> without an account. Login is only required at checkout.
      </p>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
