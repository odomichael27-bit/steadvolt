<?php
// ============================================================
//  STEADVOLT — Functions & Helpers
//  File: includes/functions.php
// ============================================================
require_once __DIR__ . '/db.php';

// ---- Session bootstrap -------------------------------------
function sv_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        // Only mark the cookie "secure" when actually served over HTTPS.
        // isset($_SERVER['HTTPS']) is unreliable — some local servers set
        // it to an empty string, which still satisfies isset() and was
        // silently breaking login/session persistence on plain http://localhost.
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? '') == 443
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => $is_https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ---- CSRF ---------------------------------------------------
function csrf_token(): string {
    sv_session_start();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return hash_equals(csrf_token(), $token);
}

// ---- Auth ---------------------------------------------------
function auth_user(): ?array {
    sv_session_start();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return auth_user() !== null;
}

function is_admin(): bool {
    $u = auth_user();
    return $u && $u['role'] === 'admin';
}

function login_user(array $user): void {
    sv_session_start();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'         => $user['id'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'avatar'     => $user['avatar'],
        'created_at' => $user['created_at'],
        'role'       => $user['role'],
    ];
}

function logout_user(): void {
    sv_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(string $redirect = '/pages/login.php'): void {
    if (!is_logged_in()) {
        $back = urlencode($_SERVER['REQUEST_URI']);
        redirect($redirect . '?redirect=' . $back);
    }
}

function require_admin(): void {
    require_login('/pages/login.php');
    if (!is_admin()) {
        redirect('/index.php');
    }
}

// ---- Redirect -----------------------------------------------
function redirect(string $url): never {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function redirect_back(string $fallback = '/'): never {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    header('Location: ' . ($ref ?: BASE_URL . $fallback));
    exit;
}

// ---- Flash messages -----------------------------------------
function flash(string $key, string $msg = '', string $type = 'info'): ?array {
    sv_session_start();
    if ($msg) {
        $_SESSION['flash'][$key] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $f = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $f;
    }
    return null;
}

function flash_html(string $key): string {
    $f = flash($key);
    if (!$f) return '';
    $icons = ['success'=>'fa-check-circle','error'=>'fa-exclamation-circle','info'=>'fa-info-circle','warning'=>'fa-exclamation-triangle'];
    $icon  = $icons[$f['type']] ?? 'fa-info-circle';
    return "<div class=\"alert alert-{$f['type']}\"><i class=\"fas {$icon}\"></i> {$f['msg']}</div>";
}

// ---- Input & sanitisation -----------------------------------
function clean(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function post(string $key, mixed $default = ''): mixed {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get_param(string $key, mixed $default = ''): mixed {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function json_response(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ---- Money formatting ---------------------------------------
function money(float $amount): string {
    $sym = DB::setting('currency_symbol', '₦');
    return $sym . number_format($amount, 2);
}

// ---- Slug generation ----------------------------------------
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// ---- Order number -------------------------------------------
function generate_order_number(): string {
    return 'SV-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');
}

// ---- OTP generation ----------------------------------------
function generate_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ---- Product image helper -----------------------------------
function product_img_url(string $filename, bool $thumb = false): string {
    if (!$filename) return BASE_URL . '/assets/img/placeholder.svg';
    return UPLOADS_URL . '/products/' . rawurlencode($filename);
}

// ---- Pagination ---------------------------------------------
function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = max(1, min($total_pages, $current_page));
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'current'     => $current_page,
        'total_pages' => $total_pages,
        'offset'      => ($current_page - 1) * $per_page,
        'has_prev'    => $current_page > 1,
        'has_next'    => $current_page < $total_pages,
    ];
}

// ---- Star rating HTML --------------------------------------
function star_rating(float $rating, bool $show_number = true): string {
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html  = '<span class="stars">';
    for ($i = 0; $i < $full;  $i++) $html .= '<i class="fas fa-star"></i>';
    if ($half)                       $html .= '<i class="fas fa-star-half-alt"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="far fa-star"></i>';
    $html .= '</span>';
    if ($show_number) $html .= ' <span class="rating-num">(' . number_format($rating, 1) . ')</span>';
    return $html;
}

// ---- Discount badge ----------------------------------------
function discount_pct(float $orig, float $sale): string {
    if ($orig <= 0 || $sale >= $orig) return '';
    $pct = round((($orig - $sale) / $orig) * 100);
    return '<span class="badge badge-sale">-' . $pct . '%</span>';
}

// ---- Cart (session-based for guests) -----------------------
function cart_get(): array {
    sv_session_start();
    return $_SESSION['cart'] ?? [];
}

function cart_add(int $product_id, int $qty = 1): bool {
    sv_session_start();
    $p = DB::row("SELECT id,name,price,stock FROM products WHERE id=? AND is_active=1", [$product_id]);
    if (!$p) return false;
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = ['qty' => 0, 'product_id' => $product_id];
    }
    $new_qty = $_SESSION['cart'][$product_id]['qty'] + $qty;
    if ($new_qty > $p['stock']) $new_qty = $p['stock'];
    $_SESSION['cart'][$product_id]['qty'] = $new_qty;
    return true;
}

function cart_update(int $product_id, int $qty): void {
    sv_session_start();
    if ($qty <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id]['qty'] = $qty;
    }
}

function cart_remove(int $product_id): void {
    sv_session_start();
    unset($_SESSION['cart'][$product_id]);
}

function cart_clear(): void {
    sv_session_start();
    unset($_SESSION['cart']);
}

function cart_count(): int {
    $cart = cart_get();
    return array_sum(array_column($cart, 'qty'));
}

function cart_items(): array {
    $cart = cart_get();
    if (empty($cart)) return [];
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $products = DB::all(
        "SELECT p.id, p.name, p.price, p.compare_price, p.slug,
                pi.filename as image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
         WHERE p.id IN ($ids)"
    );
    $result = [];
    foreach ($products as $p) {
        $qty = $cart[$p['id']]['qty'] ?? 1;
        $p['qty']   = $qty;
        $p['total'] = $p['price'] * $qty;
        $result[]   = $p;
    }
    return $result;
}

function cart_total(): float {
    $total = 0;
    foreach (cart_items() as $item) $total += $item['total'];
    return $total;
}

// ---- Shipping fee ------------------------------------------
function get_shipping_fee(string $state): float {
    $zone = DB::row(
        "SELECT fee FROM shipping_zones WHERE FIND_IN_SET(?, states) > 0 LIMIT 1",
        [$state]
    );
    if (!$zone) {
        $zone = DB::row("SELECT fee FROM shipping_zones ORDER BY fee DESC LIMIT 1");
    }
    $fee  = $zone ? (float)$zone['fee'] : 6000;
    $min  = (float)DB::setting('free_shipping_min', '150000');
    return cart_total() >= $min ? 0 : $fee;
}

// ---- User avatar URL ----------------------------------------
function avatar_url(?string $avatar): string {
    if ($avatar && file_exists(UPLOADS_PATH . '/avatars/' . $avatar)) {
        return UPLOADS_URL . '/avatars/' . rawurlencode($avatar);
    }
    return BASE_URL . '/assets/img/default-avatar.svg';
}

// ---- Time ago -----------------------------------------------
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
