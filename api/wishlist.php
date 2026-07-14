<?php
require_once dirname(__DIR__) . '/includes/functions.php';
sv_session_start();
header('Content-Type: application/json');
$action = post('action'); $product_id = (int)post('product_id');
if ($action !== 'toggle' || !$product_id) { json_response(['ok' => false, 'msg' => 'Invalid.'], 400); }
if (!is_logged_in()) { json_response(['ok' => false, 'login' => true, 'msg' => 'Please sign in to use your wishlist.']); }
$uid = auth_user()['id'];
$existing = DB::val("SELECT id FROM wishlist WHERE user_id=? AND product_id=?", [$uid, $product_id]);
if ($existing) {
    DB::query("DELETE FROM wishlist WHERE user_id=? AND product_id=?", [$uid, $product_id]);
    json_response(['ok' => true, 'action' => 'removed']);
} else {
    if (!DB::val("SELECT id FROM products WHERE id=? AND is_active=1", [$product_id])) { json_response(['ok' => false, 'msg' => 'Product not found.'], 404); }
    DB::query("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?,?)", [$uid, $product_id]);
    json_response(['ok' => true, 'action' => 'added']);
}
