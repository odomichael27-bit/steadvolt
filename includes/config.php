<?php
// ============================================================
//  STEADVOLT ENERGY — Core Configuration
//  File: includes/config.php
//  ⚠️  Fill in YOUR real credentials before going live!
// ============================================================

// ---- Database -----------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'steadyvolt');
define('DB_USER', 'root');          // ← change to your DB user
define('DB_PASS', '');              // ← change to your DB password
define('DB_CHARSET', 'utf8mb4');

// ---- Application --------------------------------------------
// BASE_URL is auto-detected so this works instantly on localhost
// (XAMPP/WAMP/MAMP/php -S) AND on a live domain — no editing needed.
// If you ever want to force a fixed URL (e.g. behind a proxy), set
// $force_base_url below instead of leaving it null.
$force_base_url = null; // e.g. 'https://www.example.com';

if ($force_base_url) {
    define('BASE_URL', rtrim($force_base_url, '/'));
} else {
    $scheme = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') == 443
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ) ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    // Detect the sub-folder the app lives in (e.g. /steadvolt when running
    // on localhost/steadvolt/...). On a domain root deployment this is ''.
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Normalise: routed requests (admin/, pages/, api/) must not leak
    // their sub-folder into BASE_URL, so we strip any trailing
    // /admin, /pages, /api, /includes segment.
    $script_dir = preg_replace('#/(admin|pages|api|includes)$#', '', $script_dir);
    $script_dir = rtrim($script_dir, '/');

    define('BASE_URL', $scheme . '://' . $host . $script_dir);
}

define('BASE_PATH', dirname(__DIR__));            // absolute path to project root
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('UPLOADS_URL',  BASE_URL  . '/uploads');

// ---- Session ------------------------------------------------
define('SESSION_NAME', 'sv_session');
define('SESSION_LIFETIME', 86400 * 30); // 30 days

// ---- Security -----------------------------------------------
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_NAME', 'sv_csrf');

// ---- Environment --------------------------------------------
// Auto-detects localhost (127.0.0.1, ::1, localhost, *.local, *.test)
// and turns on verbose error display automatically — no manual toggle
// needed while you're developing. Switches off automatically once you
// deploy to a real domain.
$__host_for_debug = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
$__is_local = (bool) preg_match('/^(localhost|127\.0\.0\.1|::1|.*\.local|.*\.test)(:\d+)?$/i', $__host_for_debug);

define('APP_DEBUG', $__is_local);
define('APP_ENV', $__is_local ? 'local' : 'production');

// ---- Error handling -----------------------------------------
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ---- Timezone -----------------------------------------------
date_default_timezone_set('Africa/Lagos');
