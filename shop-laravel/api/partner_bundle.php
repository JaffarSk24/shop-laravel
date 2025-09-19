<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Models\Partner;

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : null;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid id']);
    exit;
}

try {
    $partner = new Partner(['id' => $id]);
    $bundle = $partner->getBundle($lang);
    echo json_encode(['ok' => true, 'data' => $bundle], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('partner_bundle error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Internal error']);
}