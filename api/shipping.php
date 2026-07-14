<?php
// ============================================================
//  STEADVOLT — Shipping Fee API
//  File: api/shipping.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

$state = trim(get_param('state', post('state', 'Lagos')));
$fee   = get_shipping_fee($state);

$zone = DB::row(
    "SELECT min_days, max_days FROM shipping_zones WHERE FIND_IN_SET(?, states) > 0 LIMIT 1",
    [$state]
);

json_response([
    'fee'      => $fee,
    'display'  => $fee === 0.0 ? 'Free' : money($fee),
    'min_days' => $zone['min_days'] ?? 1,
    'max_days' => $zone['max_days'] ?? 7,
]);
