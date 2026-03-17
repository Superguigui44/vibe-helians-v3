<?php
require __DIR__ . '/bootstrap.php';

// All API calls require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Verify CSRF
if (!verifyCsrf()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'save_article':
        handleSaveArticle($input);
        break;

    case 'delete_article':
        handleDeleteArticle($input);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}

// ─── Handlers ────────────────────────────────────────────

function handleSaveArticle(array $input): void {
    global $config;

    if (empty($config['blog']['enabled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Blog désactivé']);
        return;
    }

    $article = $input['article'] ?? [];
    $id = $input['id'] ?? null;

    if (empty($article['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le titre est obligatoire']);
        return;
    }

    // Generate slug if not present
    if (empty($article['slug'])) {
        $article['slug'] = slugify($article['title']);
    }

    // Set date if not provided
    if (empty($article['date'])) {
        $article['date'] = date('Y-m-d');
    }

    $content = loadContent();
    if (!isset($content['blog']) || !is_array($content['blog'])) {
        $content['blog'] = ['articles' => []];
    }
    if (!isset($content['blog']['articles'])) {
        $content['blog']['articles'] = [];
    }

    if ($id !== null && isset($content['blog']['articles'][$id])) {
        $content['blog']['articles'][$id] = $article;
    } else {
        $content['blog']['articles'][] = $article;
    }

    saveContent($content);
    echo json_encode(['success' => true, 'message' => 'Article enregistré']);
}

function handleDeleteArticle(array $input): void {
    $id = $input['id'] ?? null;

    if ($id === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        return;
    }

    $content = loadContent();
    if (!isset($content['blog']['articles'][$id])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
        return;
    }

    array_splice($content['blog']['articles'], $id, 1);
    saveContent($content);
    echo json_encode(['success' => true, 'message' => 'Article supprimé']);
}

function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $chars = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a','å'=>'a',
              'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ẽ'=>'e',
              'ì'=>'i','î'=>'i','ï'=>'i','í'=>'i',
              'ò'=>'o','ô'=>'o','ö'=>'o','ó'=>'o','õ'=>'o','ø'=>'o',
              'ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u',
              'ý'=>'y','ÿ'=>'y','ñ'=>'n','ç'=>'c','ß'=>'ss','œ'=>'oe','æ'=>'ae'];
    $text = strtr($text, $chars);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}
