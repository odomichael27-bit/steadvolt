<?php
require_once dirname(__DIR__) . '/includes/functions.php';
header('Content-Type: application/json');
$q = trim(get_param('q'));
if (strlen($q) < 2) { json_response(['results' => []]); }
$term = '%' . $q . '%';
$results = DB::all(
    "SELECT p.id, p.name, p.slug, p.price, pi.filename AS image, c.name AS category
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
     WHERE p.is_active = 1 AND (p.name LIKE ? OR p.short_desc LIKE ? OR c.name LIKE ?)
     ORDER BY p.is_featured DESC, p.name ASC LIMIT 8",
    [$term, $term, $term]
);
json_response(['results' => $results]);
