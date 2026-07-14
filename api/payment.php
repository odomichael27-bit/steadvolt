<?php
// ============================================================
//  STEADVOLT — Paystack Payment API
//  File: api/payment.php
//  Handles: initialize, verify, webhook
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

sv_session_start();

$action = get_param('action') ?: post('action');

switch ($action) {

    // ---- INITIALIZE PAYMENT ----------------------------------
    case 'initialize':
        require_login('/pages/login.php?redirect=/cart.php');
        if (!csrf_verify()) json_response(['ok' => false, 'msg' => 'Invalid request.'], 403);
        if (is_admin()) json_response(['ok' => false, 'msg' => 'Admin accounts can\'t place orders.'], 403);

        $user       = auth_user();
        $items      = cart_items();
        $coupon_code= strtoupper(trim(post('coupon_code')));
        $state      = trim(post('ship_state', 'Lagos'));

        if (empty($items)) json_response(['ok' => false, 'msg' => 'Your cart is empty.'], 400);

        $subtotal   = cart_total();
        $ship_fee   = get_shipping_fee($state);
        $discount   = 0;
        $coupon_row = null;

        // Validate coupon
        if ($coupon_code) {
            $coupon_row = DB::row(
                "SELECT * FROM coupons WHERE code=? AND is_active=1
                 AND (expires_at IS NULL OR expires_at > NOW())
                 AND (max_uses=0 OR used_count < max_uses)
                 AND min_order <= ?",
                [$coupon_code, $subtotal]
            );
            if ($coupon_row) {
                $discount = $coupon_row['type'] === 'percent'
                    ? $subtotal * ($coupon_row['value'] / 100)
                    : min($coupon_row['value'], $subtotal);
            }
        }

        $total = max(0, $subtotal + $ship_fee - $discount);

        // Build order record
        $order_number = generate_order_number();
        $order_id = DB::insert(
            "INSERT INTO orders (order_number, user_id, status, subtotal, shipping_fee, discount_amount,
             total, coupon_code, ship_name, ship_phone, ship_address, ship_city, ship_state,
             payment_method, payment_status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $order_number,
                $user['id'],
                'pending',
                $subtotal,
                $ship_fee,
                $discount,
                $total,
                $coupon_code ?: null,
                trim(post('ship_name')),
                trim(post('ship_phone')),
                trim(post('ship_address')),
                trim(post('ship_city')),
                $state,
                'paystack',
                'unpaid',
            ]
        );

        // Save order items
        foreach ($items as $item) {
            DB::query(
                "INSERT INTO order_items (order_id, product_id, product_name, product_sku, product_img, qty, unit_price, total_price)
                 VALUES (?,?,?,?,?,?,?,?)",
                [
                    $order_id,
                    $item['id'],
                    $item['name'],
                    '',
                    $item['image'] ?? '',
                    $item['qty'],
                    $item['price'],
                    $item['total'],
                ]
            );
            // Decrement stock
            DB::query("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
                [$item['qty'], $item['id'], $item['qty']]);
        }

        // Apply coupon usage
        if ($coupon_row) {
            DB::query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$coupon_row['id']]);
        }

        // Store order in session for verification
        $_SESSION['pending_order_id'] = $order_id;

        // Initialize Paystack
        $secret_key = DB::setting('paystack_secret_key');
        if (!$secret_key) json_response(['ok' => false, 'msg' => 'Payment not configured. Contact support.'], 500);

        $payload = [
            'email'     => $user['email'],
            'amount'    => (int)($total * 100), // Paystack uses kobo
            'reference' => 'VP_' . $order_number . '_' . time(),
            'callback_url' => BASE_URL . '/api/payment.php?action=verify',
            'metadata'  => [
                'order_id'     => $order_id,
                'order_number' => $order_number,
                'custom_fields' => [
                    ['display_name' => 'Order Number', 'variable_name' => 'order_number', 'value' => $order_number],
                    ['display_name' => 'Customer', 'variable_name' => 'customer', 'value' => $user['first_name'] . ' ' . $user['last_name']],
                ]
            ],
        ];

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $secret_key,
                'Content-Type: application/json',
            ],
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!empty($response['status']) && $response['status'] === true) {
            // Save payment reference
            DB::query("UPDATE orders SET payment_ref=? WHERE id=?",
                [$response['data']['reference'], $order_id]);

            json_response(['ok' => true, 'authorization_url' => $response['data']['authorization_url']]);
        } else {
            json_response(['ok' => false, 'msg' => 'Could not initialize payment. Please try again.'], 500);
        }
        break;

    // ---- VERIFY PAYMENT (Paystack callback) ------------------
    case 'verify':
        $ref = get_param('reference') ?: post('reference');
        if (!$ref) redirect('/cart.php');

        $secret_key = DB::setting('paystack_secret_key');
        $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($ref));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $secret_key],
        ]);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!empty($res['data']['status']) && $res['data']['status'] === 'success') {
            $order_id = $res['data']['metadata']['order_id'] ?? null;
            if ($order_id) {
                DB::query(
                    "UPDATE orders SET status='paid', payment_status='paid', paid_at=NOW() WHERE id=? AND payment_status='unpaid'",
                    [$order_id]
                );
                // Send confirmation email
                $order = DB::row(
                    "SELECT o.*, u.email AS user_email, CONCAT(u.first_name,' ',u.last_name) AS user_name
                     FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?",
                    [$order_id]
                );
                $items = DB::all("SELECT * FROM order_items WHERE order_id=?", [$order_id]);
                if ($order) Mailer::sendOrderConfirmation($order, $items);

                cart_clear();
                flash('order', 'Payment successful! Your order <strong>#' . $order['order_number'] . '</strong> has been confirmed. A receipt has been sent to your email.', 'success');
                redirect('/pages/profile-orders.php');
            }
        }

        flash('order', 'Payment verification failed. If funds were deducted, please contact us with reference: ' . clean($ref), 'error');
        redirect('/cart.php');

    // ---- PAYSTACK WEBHOOK ------------------------------------
    case 'webhook':
        // Validate Paystack signature
        $input     = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
        $secret    = DB::setting('paystack_secret_key');
        $computed  = hash_hmac('sha512', $input, $secret);

        if ($signature !== $computed) {
            http_response_code(401);
            exit('Unauthorized');
        }

        $event = json_decode($input, true);
        if ($event['event'] === 'charge.success') {
            $ref      = $event['data']['reference'];
            $order    = DB::row("SELECT * FROM orders WHERE payment_ref=? AND payment_status='unpaid'", [$ref]);
            if ($order) {
                DB::query(
                    "UPDATE orders SET status='paid', payment_status='paid', paid_at=NOW() WHERE id=?",
                    [$order['id']]
                );
                $items = DB::all("SELECT * FROM order_items WHERE order_id=?", [$order['id']]);
                $full  = DB::row(
                    "SELECT o.*, u.email AS user_email, CONCAT(u.first_name,' ',u.last_name) AS user_name
                     FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?",
                    [$order['id']]
                );
                if ($full) Mailer::sendOrderConfirmation($full, $items);
            }
        }
        http_response_code(200);
        echo 'OK';
        break;

    // ---- GET SHIPPING FEE (AJAX, on state change) -------------
    case 'get_shipping':
        header('Content-Type: application/json');
        $state = trim(post('state'));
        $fee   = get_shipping_fee($state);
        json_response(['ok' => true, 'fee' => $fee, 'display' => money($fee)]);
        break;

    // ---- APPLY COUPON (AJAX check) ---------------------------
    case 'check_coupon':
        header('Content-Type: application/json');
        $code    = strtoupper(trim(post('code')));
        $subtotal = (float)post('subtotal', cart_total());
        $coupon  = DB::row(
            "SELECT * FROM coupons WHERE code=? AND is_active=1
             AND (expires_at IS NULL OR expires_at > NOW())
             AND (max_uses=0 OR used_count < max_uses)
             AND min_order <= ?",
            [$code, $subtotal]
        );
        if (!$coupon) {
            json_response(['ok' => false, 'msg' => 'Invalid or expired coupon code.']);
        }
        $discount = $coupon['type'] === 'percent'
            ? $subtotal * ($coupon['value'] / 100)
            : min($coupon['value'], $subtotal);
        json_response([
            'ok'       => true,
            'msg'      => 'Coupon applied! You save ' . money($discount),
            'discount' => $discount,
            'display'  => money($discount),
        ]);
        break;

    default:
        redirect('/index.php');
}
