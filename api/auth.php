<?php
// ============================================================
//  STEADVOLT — Auth API Handler
//  File: api/auth.php
//  Handles: login, register, logout, fp_send_otp, fp_verify_otp, fp_reset
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

sv_session_start();

$action = post('action') ?: get_param('action');

switch ($action) {

    // ---- LOGIN -----------------------------------------------
    case 'login':
        if (!csrf_verify()) { flash('auth', 'Invalid request.', 'error'); redirect('/pages/login.php'); }

        $email    = strtolower(trim(post('email')));
        $password = post('password');
        $redirect = post('redirect', '/index.php');

        if (!$email || !$password) {
            flash('auth', 'Please enter your email and password.', 'error');
            redirect('/pages/login.php?redirect=' . urlencode($redirect));
        }

        $user = DB::row("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1", [$email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('auth', 'Incorrect email or password.', 'error');
            redirect('/pages/login.php?redirect=' . urlencode($redirect));
        }

        login_user($user);

        // Redirect cleanly — ensure it starts with /
        $safe_redirect = preg_match('#^/#', $redirect) ? $redirect : '/index.php';
        redirect($safe_redirect);

    // ---- REGISTER --------------------------------------------
    case 'register':
        if (!csrf_verify()) { flash('auth', 'Invalid request.', 'error'); redirect('/pages/signup.php'); }

        $first   = ucfirst(strtolower(trim(post('first_name'))));
        $last    = ucfirst(strtolower(trim(post('last_name'))));
        $email   = strtolower(trim(post('email')));
        $phone   = trim(post('phone'));
        $pw      = post('password');
        $pw2     = post('password_confirm');
        $terms   = post('terms');
        $redirect = post('redirect', '/index.php');

        $errors = [];
        if (!$first || !$last)     $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (strlen($pw) < 8)       $errors[] = 'Password must be at least 8 characters.';
        if ($pw !== $pw2)          $errors[] = 'Passwords do not match.';
        if (!$terms)               $errors[] = 'You must agree to the Terms of Service.';

        if ($errors) {
            flash('auth', implode(' ', $errors), 'error');
            redirect('/pages/signup.php?redirect=' . urlencode($redirect));
        }

        // Check duplicate email
        if (DB::val("SELECT id FROM users WHERE email = ?", [$email])) {
            flash('auth', 'An account with this email already exists. <a href="/pages/login.php">Sign in here</a>.', 'error');
            redirect('/pages/signup.php?redirect=' . urlencode($redirect));
        }

        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $id   = DB::insert(
            "INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES (?,?,?,?,?,'customer')",
            [$first, $last, $email, $phone, $hash]
        );

        $user = DB::row("SELECT * FROM users WHERE id = ?", [$id]);
        login_user($user);

        // Send welcome email (non-blocking)
        Mailer::sendWelcome($email, $first);

        $safe_redirect = preg_match('#^/#', $redirect) ? $redirect : '/index.php';
        redirect($safe_redirect);

    // ---- LOGOUT -----------------------------------------------
    case 'logout':
        logout_user();
        redirect('/index.php');

    // ---- FORGOT PASSWORD: SEND OTP ---------------------------
    case 'fp_send_otp':
        if (!csrf_verify()) { flash('fp', 'Invalid request.', 'error'); redirect('/pages/forgot-password.php'); }

        $email = strtolower(trim(post('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('fp', 'Please enter a valid email address.', 'error');
            redirect('/pages/forgot-password.php');
        }

        $user = DB::row("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1", [$email]);

        // Always show success to prevent email enumeration
        $_SESSION['fp_step']  = 2;
        $_SESSION['fp_email'] = $email;

        if ($user) {
            $result = Mailer::sendOtp($email, $user['first_name']);

            // DEV CONVENIENCE: on localhost, if SMTP isn't configured yet,
            // surface the OTP directly on screen so you can still test the
            // full forgot-password flow without setting up real email.
            // This block is automatically disabled on a live domain.
            if (APP_DEBUG && !$result['sent']) {
                flash('fp', 'SMTP not configured — dev mode is showing your OTP directly: <strong style="letter-spacing:.15em;font-size:1.1em">' . $result['otp'] . '</strong> (set up SMTP in Admin → Store Settings to send real emails)', 'warning');
                redirect('/pages/forgot-password.php');
            }
        }

        flash('fp', 'A 6-digit OTP has been sent to your email address.', 'success');
        redirect('/pages/forgot-password.php');

    // ---- FORGOT PASSWORD: VERIFY OTP -------------------------
    case 'fp_verify_otp':
        if (!csrf_verify()) { flash('fp', 'Invalid request.', 'error'); redirect('/pages/forgot-password.php'); }

        $otp   = preg_replace('/\D/', '', trim(post('otp')));
        $email = $_SESSION['fp_email'] ?? '';

        if (!$email || strlen($otp) !== 6) {
            flash('fp', 'Invalid OTP. Please try again.', 'error');
            redirect('/pages/forgot-password.php');
        }

        if (!Mailer::verifyOtp($email, $otp)) {
            flash('fp', 'Incorrect or expired OTP. Please try again or request a new one.', 'error');
            redirect('/pages/forgot-password.php');
        }

        $_SESSION['fp_step']     = 3;
        $_SESSION['fp_verified'] = true;
        redirect('/pages/forgot-password.php');

    // ---- FORGOT PASSWORD: RESET ------------------------------
    case 'fp_reset':
        if (!csrf_verify() || empty($_SESSION['fp_verified'])) {
            flash('fp', 'Session expired. Please start again.', 'error');
            $_SESSION['fp_step'] = 1; $_SESSION['fp_verified'] = false;
            redirect('/pages/forgot-password.php');
        }

        $pw  = post('password');
        $pw2 = post('password_confirm');
        $email = $_SESSION['fp_email'] ?? '';

        if (strlen($pw) < 8) {
            flash('fp', 'Password must be at least 8 characters.', 'error');
            redirect('/pages/forgot-password.php');
        }
        if ($pw !== $pw2) {
            flash('fp', 'Passwords do not match.', 'error');
            redirect('/pages/forgot-password.php');
        }

        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        DB::query("UPDATE users SET password_hash = ? WHERE email = ?", [$hash, $email]);

        // Clean up session
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_verified']);
        $_SESSION['fp_step'] = 4;
        redirect('/pages/forgot-password.php');

    // ---- RESEND OTP ------------------------------------------
    case 'fp_resend_otp':
        $email = $_SESSION['fp_email'] ?? '';
        if ($email) {
            $user = DB::row("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($user) Mailer::sendOtp($email, $user['first_name']);
        }
        json_response(['ok' => true]);

    default:
        redirect('/index.php');
}
