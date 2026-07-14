<?php
// ============================================================
//  STEADVOLT — Chatbot API
//  File: api/chatbot.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

$message = strtolower(trim(post('message')));

if (!$message) {
    json_response(['reply' => 'Please type a message.']);
}

// Load all active responses ordered by priority desc
$responses = DB::all(
    "SELECT keywords, response FROM chatbot_responses WHERE is_active = 1 ORDER BY priority DESC"
);

$reply = null;
foreach ($responses as $row) {
    $keywords = array_map('trim', explode(',', strtolower($row['keywords'])));
    foreach ($keywords as $kw) {
        if ($kw && str_contains($message, $kw)) {
            $reply = $row['response'];
            break 2;
        }
    }
}

if (!$reply) {
    // Product search fallback
    $term    = '%' . $message . '%';
    $product = DB::row(
        "SELECT name, slug FROM products WHERE (LOWER(name) LIKE ? OR LOWER(short_desc) LIKE ?) AND is_active=1 LIMIT 1",
        [$term, $term]
    );
    if ($product) {
        $url   = BASE_URL . '/pages/product-detail.php?slug=' . $product['slug'];
        $reply = "I found a product that might interest you: <a href=\"{$url}\">" . clean($product['name']) . "</a>. Would you like more information?";
    } else {
        $reply = "I'm not sure about that, but our team would love to help! 😊 Please contact us via:<br>
                  📞 <strong>" . DB::setting('site_phone') . "</strong><br>
                  📧 <a href='mailto:" . DB::setting('site_email') . "'>" . DB::setting('site_email') . "</a><br>
                  Or visit our <a href='" . BASE_URL . "/pages/contact.php'>Contact page</a>.";
    }
}

json_response(['reply' => $reply]);
