<?php
require __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? 'request';
$tokensFile = dirname($config['users_file'] ?? (__DIR__ . '/../../admin-users.json')) . '/admin-reset-tokens.json';

// ─── Step 1: Request reset (form + send email) ───

if ($action === 'request') {
    $sent = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            setFlash('error', 'Session expirée, veuillez réessayer.');
            header('Location: reset-password.php');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $users = loadUsers();

        if (isset($users[$email])) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $tokenHash = password_hash($token, PASSWORD_DEFAULT);
            $expires = time() + 3600; // 1 hour

            // Store token
            $tokens = file_exists($tokensFile) ? json_decode(file_get_contents($tokensFile), true) : [];
            $tokens[$email] = ['hash' => $tokenHash, 'expires' => $expires];
            file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT), LOCK_EX);

            // Send email
            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['SCRIPT_NAME'])
                . '/reset-password.php?action=reset&token=' . urlencode($token) . '&email=' . urlencode($email);

            sendResetEmail($email, $resetUrl);
        }

        // Always show same message (anti-enumeration)
        $sent = true;
    }

    echo renderHeader('Mot de passe oublié');
    ?>
    <div class="login-container">
        <h1>Mot de passe oublié</h1>
        <?php if ($sent): ?>
            <p>Si un compte existe avec cette adresse, un email de réinitialisation a été envoyé.</p>
            <p class="mt-2"><a href="index.php" class="btn btn-primary btn-full">Retour à la connexion</a></p>
        <?php else: ?>
            <p class="text-muted mb-2">Saisissez votre email pour recevoir un lien de réinitialisation.</p>
            <form method="POST" class="login-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Envoyer</button>
                <p class="login-link"><a href="index.php">Retour à la connexion</a></p>
            </form>
        <?php endif; ?>
    </div>
    <?php
    echo renderFooter();
    exit;
}

// ─── Step 2: Reset password (validate token + new password form) ───

if ($action === 'reset') {
    $token = $_GET['token'] ?? '';
    $email = $_GET['email'] ?? '';
    $error = '';
    $success = false;

    // Validate token
    $tokens = file_exists($tokensFile) ? json_decode(file_get_contents($tokensFile), true) : [];
    $storedToken = $tokens[$email] ?? null;

    if (!$storedToken || !password_verify($token, $storedToken['hash']) || $storedToken['expires'] < time()) {
        echo renderHeader('Lien invalide');
        ?>
        <div class="login-container">
            <h1>Lien invalide</h1>
            <p>Ce lien de réinitialisation est invalide ou a expiré.</p>
            <p class="mt-2"><a href="reset-password.php" class="btn btn-primary btn-full">Demander un nouveau lien</a></p>
        </div>
        <?php
        echo renderFooter();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            $error = 'Session expirée.';
        } else {
            $newPassword = $_POST['password'] ?? '';
            $confirmPassword = $_POST['password_confirm'] ?? '';

            if (strlen($newPassword) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                // Update password
                $users = loadUsers();
                if (isset($users[$email])) {
                    $users[$email]['hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    saveUsers($users);
                }

                // Delete token
                unset($tokens[$email]);
                file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT), LOCK_EX);

                $success = true;
            }
        }
    }

    echo renderHeader('Nouveau mot de passe');
    ?>
    <div class="login-container">
        <?php if ($success): ?>
            <h1>Mot de passe modifié</h1>
            <p>Votre mot de passe a été mis à jour avec succès.</p>
            <p class="mt-2"><a href="index.php" class="btn btn-primary btn-full">Se connecter</a></p>
        <?php else: ?>
            <h1>Nouveau mot de passe</h1>
            <?php if ($error): ?>
                <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" required minlength="8" autofocus>
                    <span class="text-muted text-sm">Minimum 8 caractères</span>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Mettre à jour</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    echo renderFooter();
    exit;
}

// ─── Helper: Send reset email via PHPMailer ───

function sendResetEmail(string $to, string $resetUrl): void {
    global $config;

    $mailConfigPath = $config['mail_config'] ?? '';
    $vendorPath = $config['vendor_autoload'] ?? '';

    if (!file_exists($mailConfigPath) || !file_exists($vendorPath)) {
        // Can't send email — silently fail (anti-enumeration)
        return;
    }

    require $mailConfigPath;
    require $vendorPath;

    $siteName = $config['site_name'] ?? 'Admin';
    $fromName = $config['reset_from_name'] ?? $siteName;

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_SMTP_USER;
        $mail->Password = MAIL_SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = MAIL_SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(MAIL_FROM, $fromName);
        $mail->addAddress($to);
        $mail->Subject = 'Réinitialisation de mot de passe — ' . $siteName;
        $mail->Body = "Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe.\n\nCliquez sur ce lien pour choisir un nouveau mot de passe :\n$resetUrl\n\nCe lien expire dans 1 heure.\n\nSi vous n'avez pas fait cette demande, ignorez cet email.\n\n— $siteName";

        $mail->send();
    } catch (\Exception $e) {
        // Silently fail
    }
}
