<?php
// ============================================================
//  STEADVOLT — Google Reviews Sync
//  File: api/google_reviews.php
//  Called via cron OR admin panel to refresh review cache
//  Cron example: 0 */12 * * * php /path/to/steadvolt/api/google_reviews.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

// Allow CLI cron or admin-triggered refresh
$is_admin_ajax = isset($_GET['admin_refresh']) && is_admin();
$is_cli        = php_sapi_name() === 'cli';

if (!$is_cli && !$is_admin_ajax) {
    http_response_code(403);
    exit('Forbidden');
}

$api_key  = DB::setting('google_places_api_key');
$place_id = DB::setting('google_place_id');

if (!$api_key || !$place_id) {
    $msg = 'Google Places API key or Place ID not configured in admin settings.';
    if ($is_admin_ajax) json_response(['ok' => false, 'msg' => $msg]);
    echo $msg . PHP_EOL;
    exit;
}

// Fetch from Google Places API
$url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
    'place_id' => $place_id,
    'fields'   => 'name,rating,reviews,user_ratings_total',
    'language' => 'en',
    'key'      => $api_key,
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'SteadyVoltEnergy/1.0',
]);
$raw  = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

if ($err) {
    $msg = 'cURL error: ' . $err;
    if ($is_admin_ajax) json_response(['ok' => false, 'msg' => $msg]);
    echo $msg . PHP_EOL;
    exit;
}

$data = json_decode($raw, true);

if (($data['status'] ?? '') !== 'OK') {
    $msg = 'Google API error: ' . ($data['status'] ?? 'unknown') . ' — ' . ($data['error_message'] ?? '');
    if ($is_admin_ajax) json_response(['ok' => false, 'msg' => $msg]);
    echo $msg . PHP_EOL;
    exit;
}

$reviews = $data['result']['reviews'] ?? [];

// Sort by time desc (most recent first)
usort($reviews, fn($a, $b) => ($b['time'] ?? 0) - ($a['time'] ?? 0));

// Cache in DB (delete old, insert new)
DB::query("DELETE FROM google_reviews_cache");
DB::query(
    "INSERT INTO google_reviews_cache (review_data) VALUES (?)",
    [json_encode($reviews)]
);

$count = count($reviews);
$msg   = "Synced {$count} Google reviews successfully.";

if ($is_admin_ajax) {
    json_response(['ok' => true, 'msg' => $msg, 'count' => $count]);
}
echo $msg . PHP_EOL;
