<?php
// ============================================================
//  STEADVOLT — Reviews API
//  File: api/reviews.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

sv_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/shop.php');
if (!csrf_verify()) { flash('review', 'Invalid request.', 'error'); redirect_back('/shop.php'); }
require_login('/pages/login.php');

$product_id = (int)post('product_id');
$rating     = (int)post('rating');
$title      = trim(post('title'));
$body       = trim(post('body'));
$redirect   = post('redirect', '/shop.php');

// Validate
if (!$product_id || $rating < 1 || $rating > 5 || !$body) {
    flash('review', 'Please select a rating and write your review.', 'error');
    redirect($redirect);
}

$product = DB::row("SELECT id FROM products WHERE id=? AND is_active=1", [$product_id]);
if (!$product) { flash('review', 'Product not found.', 'error'); redirect($redirect); }

// One review per user per product
$existing = DB::val(
    "SELECT id FROM reviews WHERE product_id=? AND user_id=?",
    [$product_id, auth_user()['id']]
);
if ($existing) {
    flash('review', 'You have already reviewed this product.', 'info');
    redirect($redirect);
}

DB::query(
    "INSERT INTO reviews (product_id, user_id, rating, title, body, is_approved) VALUES (?,?,?,?,?,0)",
    [$product_id, auth_user()['id'], $rating, $title, $body]
);

flash('review', 'Thank you for your review! It will appear after approval.', 'success');
redirect($redirect);
