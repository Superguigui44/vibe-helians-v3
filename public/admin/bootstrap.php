<?php
/**
 * Admin Bootstrap — loaded by every admin page.
 * Starts session, loads config, provides helpers.
 */

// Secure session settings
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}
session_start();

// Load config from outside webroot
$configPath = __DIR__ . '/../../admin-config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    die('Configuration manquante.');
}
$config = require $configPath;

// Load users
function loadUsers(): array {
    global $config;
    $path = $config['users_file'] ?? (__DIR__ . '/../../admin-users.json');
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveUsers(array $users): void {
    global $config;
    $path = $config['users_file'] ?? (__DIR__ . '/../../admin-users.json');
    file_put_contents($path, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Auth helpers
function isLoggedIn(): bool {
    return !empty($_SESSION['admin_user']);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

// CSRF helpers
function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrf()) . '">';
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Rate limiting
function checkRateLimit(): bool {
    $attempts = $_SESSION['login_attempts'] ?? [];
    $recent = array_filter($attempts, fn($t) => $t > time() - 900); // 15 min
    $_SESSION['login_attempts'] = array_values($recent);
    return count($recent) < 5;
}

function recordFailedAttempt(): void {
    $_SESSION['login_attempts'][] = time();
}

function clearAttempts(): void {
    $_SESSION['login_attempts'] = [];
}

// Dot-path helpers — resolve nested keys like "mentions.contenu" or "cabinet.equipe.membres"
function getByPath(array $data, string $path, $default = null) {
    $keys = explode('.', $path);
    $current = $data;
    foreach ($keys as $key) {
        if (!is_array($current) || !array_key_exists($key, $current)) {
            return $default;
        }
        $current = $current[$key];
    }
    return $current;
}

function setByPath(array &$data, string $path, $value): void {
    $keys = explode('.', $path);
    $current = &$data;
    foreach ($keys as $key) {
        if (!isset($current[$key]) || !is_array($current[$key])) {
            $current[$key] = [];
        }
        $current = &$current[$key];
    }
    $current = $value;
}

// Content helpers
function loadContent(): array {
    global $config;
    $path = $config['content_file'] ?? (__DIR__ . '/../../content.json');
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveContent(array $content): void {
    global $config;
    $path = $config['content_file'] ?? (__DIR__ . '/../../content.json');
    // Rotating backup: keep last 5 versions (.bak.1 = most recent, .bak.5 = oldest)
    if (file_exists($path)) {
        for ($i = 4; $i >= 1; $i--) {
            $src = $path . '.bak.' . $i;
            $dst = $path . '.bak.' . ($i + 1);
            if (file_exists($src)) {
                rename($src, $dst);
            }
        }
        copy($path, $path . '.bak.1');
    }
    file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// Flash messages
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// Render helpers
function renderHeader(string $title, string $activePage = ''): string {
    global $config;
    $siteName = htmlspecialchars($config['site_name'] ?? 'Admin');
    $user = htmlspecialchars($_SESSION['admin_user'] ?? '');
    $blogEnabled = !empty($config['blog']['enabled']);

    $nav = '';
    if (isLoggedIn()) {
        $items = [
            ['url' => 'index.php', 'label' => 'Tableau de bord', 'id' => 'dashboard'],
            ['url' => 'blog.php', 'label' => 'Blog', 'id' => 'blog'],
        ];

        foreach ($items as $item) {
            $active = $activePage === $item['id'] ? ' class="active"' : '';
            $nav .= '<a href="' . $item['url'] . '"' . $active . '>' . $item['label'] . '</a>';
        }
    }

    $flash = getFlash();
    $flashHtml = '';
    if ($flash) {
        $cls = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
        $flashHtml = '<div class="flash ' . $cls . '">' . htmlspecialchars($flash['message']) . '</div>';
    }

    return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>' . htmlspecialchars($title) . ' — ' . $siteName . '</title>
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
    <header class="admin-header">
        <div class="admin-header-left">
            <button class="mobile-menu-btn" onclick="document.querySelector(\'.admin-nav\').classList.toggle(\'open\')" aria-label="Menu">&#9776;</button>
            <a href="index.php" class="admin-logo">' . $siteName . '</a>
        </div>
        ' . (isLoggedIn() ? '<div class="admin-header-right"><span class="admin-user">' . $user . '</span><a href="logout.php" class="btn btn-sm">Déconnexion</a></div>' : '') . '
    </header>
    ' . (isLoggedIn() && $nav ? '<nav class="admin-nav">' . $nav . '</nav>' : '') . '
    <main class="admin-main">
        ' . $flashHtml;
}

function renderFooter(): string {
    return '    </main>
</div>
</body>
</html>';
}
