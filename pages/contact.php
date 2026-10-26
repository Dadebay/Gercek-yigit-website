<?php
require_once __DIR__ . '/../inc/functions.php';
$lang = get_lang();

// Ensure session and CSRF token for form protection
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = sha1(uniqid('', true));
    }
}

if (!isset($pdo)) {
    @include_once __DIR__ . '/../admin/includes/db.php';
}

$map_embed_src = '';
if (isset($pdo)) {
    try {
        $row = $pdo->query("SELECT map_embed_src FROM contact_settings ORDER BY id ASC LIMIT 1")->fetch();
        if ($row && !empty($row['map_embed_src'])) $map_embed_src = $row['map_embed_src'];
    } catch (Exception $e) { /* ignore */ }
}

$contact_status = null; // ['type'=>'success'|'danger','msg'=>string]
$contact_success = false;
$redirect_after_seconds = 10;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $contact_status = ['type' => 'danger', 'msg' => t('invalid_csrf')];
    } else {
        // sanitize inputs
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $project = trim($_POST['project'] ?? '');
        $subject = trim($_POST['subject'] ?? 'New contact message');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact_status = ['type' => 'danger', 'msg' => t('fill_required_fields')];
        } else {
            // load active smtp settings
            $smtp = isset($pdo) ? $pdo->query("SELECT * FROM smtp_settings WHERE is_active=1 LIMIT 1")->fetch() : false;
            if (!$smtp) {
                $contact_status = ['type' => 'danger', 'msg' => t('smtp_not_configured')];
            } else {
                // try send via PHPMailer
                try {
                    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                        require_once __DIR__ . '/../vendor/autoload.php';
                    }
                       if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') || !class_exists('PHPMailer\\PHPMailer\\SMTP')) {
                        throw new Exception('Email library is incomplete. Please install PHPMailer fully (PHPMailer.php, SMTP.php, Exception.php).');
                    }
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    // SMTP config
                    $mail->isSMTP();
                    $mail->Host = $smtp['smtp_host'] ?? '';
                    $mail->Port = (int)($smtp['smtp_port'] ?? 587);
                    $username = $smtp['smtp_username'] ?? '';
                    $password = $smtp['smtp_password'] ?? '';
                    if (!empty($username)) {
                        $mail->SMTPAuth = true;
                        $mail->Username = $username;
                        $mail->Password = $password;
                    } else {
                        $mail->SMTPAuth = false;
                    }
                    $enc = strtolower(trim($smtp['smtp_encryption'] ?? ''));
                    if ($enc === 'ssl') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    elseif ($enc === 'tls') $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

                    // From
                    $from_email = $smtp['from_email'] ?? $username;
                    $from_name = $smtp['from_name'] ?? ($company_name ?? 'Website');
                    if (!empty($from_email)) {
                        $mail->setFrom($from_email, $from_name);
                    } elseif (!empty($username)) {
                        $mail->setFrom($username, $from_name);
                    }

                    // To: use from_email as recipient or site admin fallback
                    $to = $from_email ?: $username ?: ($common_email ?? null);
                    if (!$to) throw new Exception(t('recipient_address_not_configured'));
                    $mail->addAddress($to);

                    // add CCs
                    for ($i = 1; $i <= 5; $i++) {
                        $col = 'cc_email_' . $i;
                        if (!empty($smtp[$col])) $mail->addCC($smtp[$col]);
                    }

                    // Reply-to user
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) $mail->addReplyTo($email, $name);

                    $mail->Subject = ($subject ?: t('contact_us_header'));
                    $body = "<p><strong>" . htmlspecialchars(t('name')) . ":</strong> " . htmlspecialchars($name) . "</p>";
                    $body .= "<p><strong>" . htmlspecialchars(t('email')) . ":</strong> " . htmlspecialchars($email) . "</p>";
                    if ($phone) $body .= "<p><strong>" . htmlspecialchars(t('phone')) . ":</strong> " . htmlspecialchars($phone) . "</p>";
                    if ($project) $body .= "<p><strong>" . htmlspecialchars(t('project')) . ":</strong> " . htmlspecialchars($project) . "</p>";
                    $body .= "<hr><div>" . nl2br(htmlspecialchars($message)) . "</div>";

                    $mail->isHTML(true);
                    $mail->Body = $body;
                    $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />','</p>','</div>'],'\n',$body));

                    $mail->send();
                    $contact_status = ['type' => 'success', 'msg' => t('email_sent_success')];
                    $contact_success = true;
                } catch (Exception $e) {
                    error_log('Contact form delivery failed: ' . $e->getMessage());
                    $contact_status = ['type' => 'danger', 'msg' => t('email_send_failed')];
                }
            }
        }
    }
}

// include header after processing so we can show messages in page
require_once __DIR__ . '/../header.php';
?><?php page_intro(ui('contact_title'),t('contact'),ui('contact_desc')); ?>
<section class="h-shell p-contact"><div class="p-contact-info">
 <div class="p-contact-detail"><span class="h-eyebrow"><?= e(ui('write_us')) ?></span><a href="mailto:<?= e($common_email) ?>"><?= e($common_email) ?></a><?php if($secondary_email && $secondary_email!==$common_email): ?><a class="p-contact-secondary" href="mailto:<?= e($secondary_email) ?>"><?= e($secondary_email) ?></a><?php endif; ?></div>
 <?php if($common_phone): ?><div class="p-contact-detail"><span class="h-eyebrow"><?= e(ui('call_us')) ?></span><a href="tel:<?= e(preg_replace('/[^+0-9]/','',$common_phone)) ?>"><?= e($common_phone) ?></a><?php if($secondary_phone && $secondary_phone!==$common_phone): ?><a class="p-contact-secondary" href="tel:<?= e(preg_replace('/[^+0-9]/','',$secondary_phone)) ?>"><?= e($secondary_phone) ?></a><?php endif; ?></div><?php endif; ?>
 <div class="p-contact-detail"><span class="h-eyebrow"><?= e(ui('address')) ?></span><p><?= e($company_address) ?></p></div>
 <a class="h-text-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($company_address) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('find_a_location')) ?><span>↗</span></a>
</div><div class="p-contact-form"><h2><?= e(ui('write_us')) ?></h2><p class="p-form-note"><?= e(ui('form_note')) ?></p>
 <?php if($contact_status): ?><div class="p-notice<?= $contact_success?'':' p-notice-error' ?>" role="<?= $contact_success?'status':'alert' ?>"><?= e($contact_status['msg']) ?></div><?php endif; ?>
 <?php if(!$contact_success): ?><form method="post" action="index.php?page=contact#contact-form" id="contact-form"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><div class="p-form-grid">
 <?php foreach(['name'=>'text','email'=>'email','phone'=>'tel'] as $field=>$type): ?><div class="p-field"><label for="contact-<?= $field ?>"><?= e(t($field)) ?><?= in_array($field,['name','email'])?' *':'' ?></label><input id="contact-<?= $field ?>" name="<?= $field ?>" type="<?= $type ?>" value="<?= e($_POST[$field] ?? '') ?>" autocomplete="<?= $field==='phone'?'tel':$field ?>"<?= in_array($field,['name','email'])?' required':'' ?>></div><?php endforeach; ?>
 <div class="p-field"><label for="contact-project"><?= e(t('project')) ?></label><input name="project" id="contact-project" list="contact-sectors" value="<?= e($_POST['project'] ?? '') ?>"><datalist id="contact-sectors"><?php foreach([2,3,4,1] as $sector_id):$sector=sector_data($sector_id); ?><option value="<?= e($sector['title']) ?>"><?php endforeach; ?></datalist></div>
 <div class="p-field p-field-wide"><label for="contact-subject"><?= e(t('subject')) ?></label><input id="contact-subject" name="subject" value="<?= e($_POST['subject'] ?? '') ?>"></div>
 <div class="p-field p-field-wide"><label for="contact-message"><?= e(t('message')) ?> *</label><textarea id="contact-message" name="message" required><?= e($_POST['message'] ?? '') ?></textarea></div></div><button class="h-button p-form-submit" type="submit"><?= e(t('send_message')) ?><span class="h-button-arrow">↗</span></button></form><?php endif; ?>
</div></section>
<section class="p-location"><?php if(filter_var($map_embed_src,FILTER_VALIDATE_URL) && str_starts_with($map_embed_src,'https://')): ?><iframe src="<?= e($map_embed_src) ?>" title="<?= e(t('find_a_location')) ?>" loading="lazy" referrerpolicy="no-referrer"></iframe><?php else: ?><img src="<?= e(site_media_url('','img/generated/sector-construction.webp')) ?>" alt="<?= e(ui('representative')) ?>"><div class="h-shell"><span class="h-eyebrow"><?= e(t('turkmenistan')) ?></span><h2><?= e(ui('visit_us')) ?></h2><a class="h-button h-button-light" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($company_address) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('find_a_location')) ?><span class="h-button-arrow">↗</span></a><p class="p-media-credit"><?= e(ui('representative')) ?></p></div><?php endif; ?></section>
