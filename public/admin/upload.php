<?php
require __DIR__ . '/bootstrap.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

// Check file
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucun fichier valide reçu']);
    exit;
}

$file = $_FILES['file'];
$maxSize = $config['max_upload_size'] ?? (5 * 1024 * 1024);

// Validate size
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max ' . round($maxSize / 1024 / 1024) . ' Mo)']);
    exit;
}

// Validate extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowedExts)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Extension de fichier non autorisée. Formats acceptés : JPG, PNG, WebP, GIF']);
    exit;
}

// Validate MIME type (use finfo if available, fallback to extension only)
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, WebP, GIF']);
        exit;
    }
}

// Determine upload directory
$subdir = preg_replace('/[^a-z0-9_-]/', '', $_POST['subdir'] ?? '');
$uploadDir = $config['upload_dir'] ?? (__DIR__ . '/../images');
$uploadUrl = $config['upload_url'] ?? '/images';

if ($subdir) {
    $uploadDir .= '/' . $subdir;
    $uploadUrl .= '/' . $subdir;
}

// Create directory if needed
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Sanitize filename
$baseName = pathinfo($file['name'], PATHINFO_FILENAME);
$baseName = preg_replace('/[^a-z0-9._-]/', '', strtolower($baseName));
if (empty($baseName)) $baseName = 'image';
$filename = time() . '-' . $baseName . '.' . $ext;

$destPath = $uploadDir . '/' . $filename;

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode([
        'success' => true,
        'url' => $uploadUrl . '/' . $filename,
        'filename' => $filename,
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier']);
}
