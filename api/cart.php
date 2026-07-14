<?php
// ============================================================
//  STEADVOLT — Cart API
//  File: api/cart.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

sv_session_start();
header('Content-Type: application/json');

$action = post('action') ?: get_param('action');

switch ($action) {

    case 'add':
        if (is_admin()) {
            json_response(['ok' => false, 'msg' => 'Admin accounts are for managing the store and can\'t place orders. Browse and view products freely, but shopping is disabled for this account.'], 403);
        }
        $pid = (int)post('product_id');
        $qty = max(1, (int)post('qty', 1));
        if (!$pid) json_response(['ok' => false, 'msg' => 'Invalid product.'], 400);
        $ok = cart_add($pid, $qty);
        json_response(['ok' => $ok, 'count' => cart_count(), 'msg' => $ok ? 'Added to cart!' : 'Product not available.']);

    case 'update':
        $pid = (int)post('product_id');
        $qty = (int)post('qty');
        cart_update($pid, $qty);
        $items = cart_items();
        $total = cart_total();
        json_response(['ok' => true, 'count' => cart_count(), 'subtotal' => money($total), 'items' => $items]);

    case 'remove':
        $pid = (int)post('product_id');
        cart_remove($pid);
        json_response(['ok' => true, 'count' => cart_count(), 'subtotal' => money(cart_total())]);

    case 'count':
        json_response(['count' => cart_count()]);

    case 'clear':
        cart_clear();
        json_response(['ok' => true]);

    default:
        json_response(['ok' => false, 'msg' => 'Unknown action.'], 400);
}
