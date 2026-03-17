<?php
require __DIR__ . '/bootstrap.php';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isLoggedIn()) {
    if (!verifyCsrf()) {
        setFlash('error', 'Session expirée, veuillez réessayer.');
        header('Location: index.php');
        exit;
    }

    if (!checkRateLimit()) {
        setFlash('error', 'Trop de tentatives. Réessayez dans 15 minutes.');
        header('Location: index.php');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $users = loadUsers();

    if (isset($users[$email]) && password_verify($password, $users[$email]['hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $email;
        clearAttempts();
        setFlash('success', 'Bienvenue !');
        header('Location: index.php');
        exit;
    }

    recordFailedAttempt();
    setFlash('error', 'Email ou mot de passe incorrect.');
    header('Location: index.php');
    exit;
}

// Login page
if (!isLoggedIn()) {
    echo renderHeader('Connexion');
    ?>
    <div class="login-container">
        <h1>Connexion</h1>
        <form method="POST" action="index.php" class="login-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
            <p class="login-link"><a href="reset-password.php">Mot de passe oublié ?</a></p>
        </form>
    </div>
    <?php
    echo renderFooter();
    exit;
}

// Dashboard
echo renderHeader('Tableau de bord', 'dashboard');

$content = loadContent();
$blogEnabled = !empty($config['blog']['enabled']);
$blogCount = count($content['blog']['articles'] ?? []);
?>

<h1>Tableau de bord</h1>

<div class="dashboard-cards">
    <a href="blog.php" class="dashboard-card">
        <div class="dashboard-card-icon">&#128221;</div>
        <h2>Gérer le blog</h2>
        <p>Créez et modifiez vos articles</p>
        <span class="dashboard-card-meta"><?= $blogCount ?> article<?= $blogCount > 1 ? 's' : '' ?></span>
    </a>
</div>

<?php
echo renderFooter();
