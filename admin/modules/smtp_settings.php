<?php
require_once './includes/functions.php';
$message = '';

// Mevcut SMTP ayarlarını çek
$stmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
$smtp = $stmt->fetch();

if (!$smtp) {
    // Varsayılan değerler
    $smtp = [
        'smtp_host' => 'mail.sanly.tm',
        'smtp_port' => 465,
        'smtp_username' => 'info@gercekyigit.com.tm',
        'smtp_password' => '',
        'smtp_encryption' => 'ssl',
        'from_email' => 'info@gercekyigit.com.tm',
        'from_name' => 'Gerçek Yiğit',
        'cc_email_1' => '',
        'cc_email_2' => '',
        'cc_email_3' => '',
        'cc_email_4' => '',
        'cc_email_5' => '',
        'is_active' => 1
    ];
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $smtp_host = trim($_POST["smtp_host"] ?? 'smtp.gmail.com');
    $smtp_port = intval($_POST["smtp_port"] ?? 587);
    $smtp_username = trim($_POST["smtp_username"] ?? '');
    $smtp_password = trim($_POST["smtp_password"] ?? '');
    $smtp_encryption = trim($_POST["smtp_encryption"] ?? 'tls');
    $from_email = trim($_POST["from_email"] ?? '');
    $from_name = trim($_POST["from_name"] ?? '');
    $cc_email_1 = trim($_POST["cc_email_1"] ?? '');
    $cc_email_2 = trim($_POST["cc_email_2"] ?? '');
    $cc_email_3 = trim($_POST["cc_email_3"] ?? '');
    $cc_email_4 = trim($_POST["cc_email_4"] ?? '');
    $cc_email_5 = trim($_POST["cc_email_5"] ?? '');
    $is_active = isset($_POST["is_active"]) ? 1 : 0;

    if (isset($smtp['id'])) {
        // Güncelle
        $stmt = $pdo->prepare("UPDATE smtp_settings SET smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, smtp_encryption=?, from_email=?, from_name=?, cc_email_1=?, cc_email_2=?, cc_email_3=?, cc_email_4=?, cc_email_5=?, is_active=? WHERE id=?");
        $result = $stmt->execute([$smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $from_email, $from_name, $cc_email_1, $cc_email_2, $cc_email_3, $cc_email_4, $cc_email_5, $is_active, $smtp['id']]);
    } else {
        // Ekle
        $stmt = $pdo->prepare("INSERT INTO smtp_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name, cc_email_1, cc_email_2, cc_email_3, cc_email_4, cc_email_5, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $from_email, $from_name, $cc_email_1, $cc_email_2, $cc_email_3, $cc_email_4, $cc_email_5, $is_active]);
    }

    $message = $result
        ? "<div class='alert alert-success'>SMTP settings saved successfully.</div>"
        : "<div class='alert alert-danger'>Error saving settings.</div>";
    
    // Verileri tekrar çek
    $stmt = $pdo->query("SELECT * FROM smtp_settings LIMIT 1");
    $smtp = $stmt->fetch();
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-envelope-at"></i> SMTP Email Settings</h5>
            </div>
            <div class="card-body">
                <!-- Quick Setup Buttons -->
                <div class="row mb-4">
                    <!-- <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success"><i class="bi bi-google"></i> Gmail SMTP (Recommended for Testing)</h6>
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="setGmailSettings()">
                                    <i class="bi bi-lightning-fill"></i> Quick Setup Gmail
                                </button>
                                <small class="text-muted d-block mt-2">
                                    <strong>Step 1:</strong> <a href="https://myaccount.google.com/apppasswords" target="_blank">Get Gmail App Password</a><br>
                                    <strong>Step 2:</strong> Click "Quick Setup Gmail" button<br>
                                    <strong>Step 3:</strong> Enter your Gmail & App Password below
                                </small>
                            </div>
                        </div>
                    </div> -->
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h6 class="card-title text-primary"><i class="bi bi-envelope"></i> Sanly TM (mail.sanly.tm)</h6>
                                <!-- <button type="button" class="btn btn-primary btn-sm w-100" onclick="setYandexSettings()">
                                    <i class="bi bi-lightning-fill"></i> Quick Setup Yandex
                                </button> -->
                                <small class="text-muted d-block mt-2">
                                    <strong>Recommended:</strong> Host: <code>mail.sanly.tm</code> · Port: <code>465</code> · Encryption: <code>SSL</code><br>
                                    <strong>Alternative:</strong> Port: <code>587</code> · Encryption: <code>TLS</code><br>
                                    <!-- <strong>Step 3:</strong> Click "Quick Setup Yandex" button -->
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                   value="<?php echo htmlspecialchars($smtp['smtp_host']); ?>" required>
                            <small class="text-muted">Example: smtp.gmail.com</small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                   value="<?php echo htmlspecialchars($smtp['smtp_port']); ?>" required>
                            <small class="text-muted">587 (TLS) or 465 (SSL)</small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="smtp_encryption" class="form-label">Encryption <span class="text-danger">*</span></label>
                            <select class="form-select" id="smtp_encryption" name="smtp_encryption" required>
                                <option value="tls" <?php echo $smtp['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $smtp['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_username" class="form-label">SMTP Username (Email) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                   value="<?php echo htmlspecialchars($smtp['smtp_username']); ?>" required>
                            <small class="text-muted">Your email address</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="smtp_password" class="form-label">SMTP Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                   value="<?php echo htmlspecialchars($smtp['smtp_password']); ?>" required>
                            <small class="text-muted">For Gmail: Use App Password, not your Gmail password</small>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="from_email" class="form-label">From Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                   value="<?php echo htmlspecialchars($smtp['from_email']); ?>" required>
                            <small class="text-muted">Email address shown as sender</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="from_name" class="form-label">From Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="from_name" name="from_name" 
                                   value="<?php echo htmlspecialchars($smtp['from_name']); ?>" required>
                            <small class="text-muted">Name shown as sender</small>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="bi bi-people-fill"></i> CC Email Addresses (Optional)</h6>
                    <p class="text-muted small mb-3">Add up to 5 email addresses to receive copies of all messages sent from contact forms.</p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cc_email_1" class="form-label">CC Email 1</label>
                            <input type="email" class="form-control" id="cc_email_1" name="cc_email_1" 
                                   value="<?php echo htmlspecialchars($smtp['cc_email_1'] ?? ''); ?>" 
                                   placeholder="example1@company.com">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="cc_email_2" class="form-label">CC Email 2</label>
                            <input type="email" class="form-control" id="cc_email_2" name="cc_email_2" 
                                   value="<?php echo htmlspecialchars($smtp['cc_email_2'] ?? ''); ?>" 
                                   placeholder="example2@company.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cc_email_3" class="form-label">CC Email 3</label>
                            <input type="email" class="form-control" id="cc_email_3" name="cc_email_3" 
                                   value="<?php echo htmlspecialchars($smtp['cc_email_3'] ?? ''); ?>" 
                                   placeholder="example3@company.com">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="cc_email_4" class="form-label">CC Email 4</label>
                            <input type="email" class="form-control" id="cc_email_4" name="cc_email_4" 
                                   value="<?php echo htmlspecialchars($smtp['cc_email_4'] ?? ''); ?>" 
                                   placeholder="example4@company.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cc_email_5" class="form-label">CC Email 5</label>
                            <input type="email" class="form-control" id="cc_email_5" name="cc_email_5" 
                                   value="<?php echo htmlspecialchars($smtp['cc_email_5'] ?? ''); ?>" 
                                   placeholder="example5@company.com">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                               <?php echo $smtp['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">
                            <strong>Enable SMTP Email Sending</strong>
                        </label>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-save"></i> Save SMTP Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Email Section -->
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="bi bi-envelope-check"></i> Test Email Configuration</h5>
            </div>
            <div class="card-body">
                <p>Send a test email to <strong><?php echo htmlspecialchars($smtp['from_email'] ?: ($smtp['smtp_username'] ?? 'info@gercekyigit.com.tm')); ?></strong> to verify your SMTP settings are working.</p>
                <div id="test-result"></div>
                <form id="testEmailForm">
                    <div class="text-center">
                        <button type="submit" class="btn btn-success btn-lg px-5" id="testBtn">
                            <i class="bi bi-send"></i> Send Test Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alert
    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 5000);
    }

    // Test email form
    const testForm = document.getElementById('testEmailForm');
    if (testForm) {
        testForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const testBtn = document.getElementById('testBtn');
            const resultDiv = document.getElementById('test-result');
            
            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
            
            const toEmail = encodeURIComponent('<?php echo htmlspecialchars($smtp['from_email'] ?: ($smtp['smtp_username'] ?? 'info@gercekyigit.com.tm')); ?>');
            fetch('test_smtp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'to=' + toEmail
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + data.message + '</div>';
                } else {
                    // Hata mesajını pre tag ile göster (debug bilgileri için)
                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <pre style="white-space: pre-wrap; word-wrap: break-word;">' + data.message + '</pre></div>';
                }
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-send"></i> Send Test Email';
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error: ' + error + '</div>';
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-send"></i> Send Test Email';
            });
        });
    }
});

// Quick Setup Functions
function setGmailSettings() {
    document.getElementById('smtp_host').value = 'smtp.gmail.com';
    document.getElementById('smtp_port').value = '587';
    document.getElementById('smtp_encryption').value = 'tls';
    document.getElementById('smtp_username').focus();
    
    // Show alert
    alert('Gmail settings configured!\n\nNow enter:\n1. Your Gmail address in "Username"\n2. Your Gmail App Password in "Password"\n\nGet App Password: https://myaccount.google.com/apppasswords');
}

function setYandexSettings() {
    document.getElementById('smtp_host').value = 'mail.sanly.tm';
    document.getElementById('smtp_port').value = '465';
    document.getElementById('smtp_encryption').value = 'ssl';
    document.getElementById('smtp_username').value = 'info@gercekyigit.com.tm';
    document.getElementById('smtp_password').focus();
    
    // Show alert
    alert('Sanly TM settings prefilled. Enter your mailbox password, then Save.');
}
</script>
