<?php
// ============================================================
//  STEADVOLT — Forgot Password (4-step OTP flow)
//  File: pages/forgot-password.php
//  Steps: 1=email → 2=OTP → 3=new password → 4=success
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

sv_session_start();

if (is_logged_in()) redirect('/index.php');

$page_title = 'Forgot Password';
$step = (int)($_SESSION['fp_step'] ?? 1);
// Reset if hitting page fresh with no session step
if (!isset($_SESSION['fp_step'])) {
    $_SESSION['fp_step']  = 1;
    $_SESSION['fp_email'] = '';
    $step = 1;
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-visual">
    <div class="auth-visual-top">
      <a href="<?= BASE_URL ?>/index.php" class="footer-logo" style="color:#fff">
        <div class="logo-mark"><i class="fas fa-bolt"></i></div>
        <span>Volt<em style="color:var(--green)">Peak</em></span>
      </a>
    </div>
    <div class="auth-visual-mid">
      <h2>Forgot your password? No problem.</h2>
      <p>We'll send a one-time code to your email address to verify it's you — it only takes a minute.</p>
    </div>
    <div class="auth-visual-steps">
      <div class="vstep <?= $step >= 1 ? 'active' : '' ?>"><span>1</span> Enter Email</div>
      <div class="vstep-line"></div>
      <div class="vstep <?= $step >= 2 ? 'active' : '' ?>"><span>2</span> Enter OTP</div>
      <div class="vstep-line"></div>
      <div class="vstep <?= $step >= 3 ? 'active' : '' ?>"><span>3</span> New Password</div>
      <div class="vstep-line"></div>
      <div class="vstep <?= $step >= 4 ? 'active' : '' ?>"><span>4</span> Done!</div>
    </div>
  </div>

  <div class="auth-form-side">
    <div class="auth-form-card">
      <?= flash_html('fp') ?>

      <!-- STEP 1: Email -->
      <?php if ($step === 1): ?>
      <h1><i class="fas fa-envelope"></i> Reset Password</h1>
      <p class="auth-sub">Enter the email address linked to your account.</p>

      <form method="POST" action="<?= BASE_URL ?>/api/auth.php" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="fp_send_otp"/>
        <div class="form-group">
          <label>Email Address</label>
          <div class="input-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" placeholder="you@example.com" required autofocus/>
          </div>
        </div>
        <button type="submit" class="btn btn-primary auth-submit-btn">
          Send OTP <i class="fas fa-paper-plane"></i>
        </button>
      </form>

      <!-- STEP 2: OTP -->
      <?php elseif ($step === 2): ?>
      <h1><i class="fas fa-key"></i> Enter OTP</h1>
      <p class="auth-sub">A 6-digit code was sent to <strong><?= clean($_SESSION['fp_email'] ?? '') ?></strong>. Check your inbox (and spam folder).</p>

      <form method="POST" action="<?= BASE_URL ?>/api/auth.php" id="otpForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="fp_verify_otp"/>

        <div class="otp-inputs">
          <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" class="otp-digit" maxlength="1" pattern="\d" inputmode="numeric" autocomplete="one-time-code" <?= $i===0?'autofocus':'' ?>/>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="otp" id="otpHidden"/>

        <div class="otp-resend">
          Didn't get the code? <button type="button" id="resendOtp" class="link-btn">Resend OTP</button>
          <span id="resendTimer" class="otp-timer"></span>
        </div>

        <button type="submit" class="btn btn-primary auth-submit-btn">
          Verify OTP <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <!-- STEP 3: New Password -->
      <?php elseif ($step === 3): ?>
      <h1><i class="fas fa-lock"></i> New Password</h1>
      <p class="auth-sub">Choose a strong new password for your account.</p>

      <form method="POST" action="<?= BASE_URL ?>/api/auth.php" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="fp_reset"/>

        <div class="form-group">
          <label>New Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="newPw" placeholder="Min. 8 characters" required minlength="8" autofocus/>
            <button type="button" class="toggle-pw"><i class="fas fa-eye"></i></button>
          </div>
          <div class="pw-strength-bar"><div id="pwBar"></div></div>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password_confirm" placeholder="Repeat password" required/>
            <button type="button" class="toggle-pw"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary auth-submit-btn">
          Update Password <i class="fas fa-check"></i>
        </button>
      </form>

      <!-- STEP 4: Success -->
      <?php elseif ($step === 4): ?>
      <div class="auth-success">
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h1>Password Updated!</h1>
        <p>Your password has been reset successfully. You can now sign in with your new password.</p>
        <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary auth-submit-btn">
          Sign In <i class="fas fa-sign-in-alt"></i>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($step < 4): ?>
      <p class="auth-switch"><a href="<?= BASE_URL ?>/pages/login.php"><i class="fas fa-arrow-left"></i> Back to Sign In</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
