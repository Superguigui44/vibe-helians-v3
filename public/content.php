<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$contentFile = __DIR__ . '/../content.json';

if (!file_exists($contentFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Content file not found']);
    exit;
}

echo file_get_contents($contentFile);
