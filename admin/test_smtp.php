<?php
header('Content-Type: application/json; charset=utf-8');

// Basic admin context and DB
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

// Ensure we do not redirect; validate session and return JSON error if not logged in
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    if (!isset($pdo)) {
        throw new Exception('Database not available');
    }
    $row = $pdo->query("SELECT * FROM smtp_settings WHERE is_active=1 LIMIT 1")->fetch();
    if (!$row) {
        throw new Exception('Active SMTP settings not found');
    }

    // Build PHPMailer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        throw new Exception('PHPMailer not found. Install via Composer (composer require phpmailer/phpmailer) or copy complete src folder (PHPMailer.php, SMTP.php, Exception.php).');
    }
    if (!class_exists('PHPMailer\\PHPMailer\\SMTP')) {
        throw new Exception('PHPMailer SMTP class missing. Ensure SMTP.php exists under vendor/phpmailer/phpmailer/src.');
    }
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $row['smtp_host'] ?? '';
    $mail->Port = (int)($row['smtp_port'] ?? 587);
    $username = $row['smtp_username'] ?? '';
    $password = $row['smtp_password'] ?? '';
    if (!empty($username)) {
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
    } else {
        $mail->SMTPAuth = false;
    }
    $enc = strtolower(trim($row['smtp_encryption'] ?? ''));
    if ($enc === 'ssl') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    elseif ($enc === 'tls') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

    $from_email = $row['from_email'] ?? $username;
    $from_name = $row['from_name'] ?? 'Website';
    if (!empty($from_email)) {
        $mail->setFrom($from_email, $from_name);
    } elseif (!empty($username)) {
        $mail->setFrom($username, $from_name);
    }

    // Determine recipient: POST override or configured address
    $to = isset($_POST['to']) && filter_var($_POST['to'], FILTER_VALIDATE_EMAIL)
        ? $_POST['to']
        : ($from_email ?: $username);
    if (!$to) {
        throw new Exception('Recipient address not determined');
    }
    $mail->addAddress($to);

    $mail->Subject = 'SMTP Test Email';
    $mail->isHTML(true);
    $mail->Body = '<p>This is a test email sent at ' . date('Y-m-d H:i:s') . '.</p>';
    $mail->AltBody = 'This is a test email sent at ' . date('Y-m-d H:i:s') . '.';

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Test email sent to ' . $to]);
} catch (Throwable $e) {
    $msg = $e->getMessage();
    echo json_encode(['success' => false, 'message' => $msg]);
}
