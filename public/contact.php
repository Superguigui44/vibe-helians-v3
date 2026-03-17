<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Honeypot anti-spam
$honeypot = trim($data['website'] ?? '');
if ($honeypot !== '') {
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès.']);
    exit;
}

// Validate required fields
$required = ['prenom', 'nom', 'email', 'telephone', 'message'];
foreach ($required as $field) {
    if (empty(trim($data[$field] ?? ''))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Le champ $field est requis."]);
        exit;
    }
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
    exit;
}

// Load mail config
$configPath = __DIR__ . '/../mail-config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration email manquante.']);
    exit;
}
require $configPath;

// Load PHPMailer
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_TO);
    $mail->addReplyTo($data['email'], $data['prenom'] . ' ' . $data['nom']);

    $objet = !empty($data['objet']) ? ' — ' . $data['objet'] : '';
    $mail->Subject = 'Contact Hélians' . $objet;

    $mail->isHTML(true);
    $mail->Body = '
    <h2>Nouveau message depuis helians.fr</h2>
    <table style="border-collapse:collapse;width:100%;max-width:600px;">
      <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">Nom</td><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($data['prenom'] . ' ' . $data['nom']) . '</td></tr>
      <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">Email</td><td style="padding:8px;border-bottom:1px solid #eee;"><a href="mailto:' . htmlspecialchars($data['email']) . '">' . htmlspecialchars($data['email']) . '</a></td></tr>
      <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">Téléphone</td><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($data['telephone']) . '</td></tr>
      <tr><td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">Objet</td><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($data['objet'] ?? 'Non précisé') . '</td></tr>
      <tr><td style="padding:8px;font-weight:bold;vertical-align:top;">Message</td><td style="padding:8px;">' . nl2br(htmlspecialchars($data['message'])) . '</td></tr>
    </table>';

    $mail->AltBody = "Nom: {$data['prenom']} {$data['nom']}\nEmail: {$data['email']}\nTéléphone: {$data['telephone']}\nObjet: " . ($data['objet'] ?? 'Non précisé') . "\n\nMessage:\n{$data['message']}";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message.']);
}
