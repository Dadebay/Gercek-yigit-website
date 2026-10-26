<?php
require_once './includes/functions.php';
$message = '';

// Ortak alanları çek
$stmt = $pdo->query("SELECT * FROM company_info_common LIMIT 1");
$common = $stmt->fetch();
if (!$common) {
    $common = [
        'id' => null,
        'phone'=>'', 'phone1'=>'', 'phone2'=>'',
        'email'=>'', 'email1'=>'', 'email2'=>'',
        'logo_bent_media'=>'', 'logo_bent_tech'=>'', 'logo_bent_vision'=>'', 'download_pdf'=>''
    ];
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $langs = ['en', 'tm', 'ru'];
    $success = true;

    // Ortak alanlar
    $phone = trim($_POST["phone"] ?? '');
    $phone1 = trim($_POST["phone1"] ?? '');
    $phone2 = trim($_POST["phone2"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $email1 = trim($_POST["email1"] ?? '');
    $email2 = trim($_POST["email2"] ?? '');
    $logo_bent_media = $_POST["old_logo_bent_media"] ?? '';
    $logo_bent_tech = $_POST["old_logo_bent_tech"] ?? '';
    $logo_bent_vision = $_POST["old_logo_bent_vision"] ?? '';
    $download_pdf = $_POST["old_download_pdf"] ?? '';
    $target_dir = "uploads/company_infos/";

    if (isset($_FILES["logo_bent_media"]) && $_FILES["logo_bent_media"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_bent_media_" . basename($_FILES["logo_bent_media"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["logo_bent_media"]["tmp_name"], $target_file)) {
        	// delete old if different
            if (!empty($_POST["old_logo_bent_media"]) && $_POST["old_logo_bent_media"] !== $target_file) {
                $old = $_POST["old_logo_bent_media"];
                if (is_file($old)) { @unlink($old); }
            }
            $logo_bent_media = $target_file;
        }
    }
    if (isset($_FILES["logo_bent_tech"]) && $_FILES["logo_bent_tech"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_bent_tech_" . basename($_FILES["logo_bent_tech"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["logo_bent_tech"]["tmp_name"], $target_file)) {
            if (!empty($_POST["old_logo_bent_tech"]) && $_POST["old_logo_bent_tech"] !== $target_file) {
                $old = $_POST["old_logo_bent_tech"];
                if (is_file($old)) { @unlink($old); }
            }
            $logo_bent_tech = $target_file;
        }
    }
    if (isset($_FILES["logo_bent_vision"]) && $_FILES["logo_bent_vision"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_bent_vision_" . basename($_FILES["logo_bent_vision"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["logo_bent_vision"]["tmp_name"], $target_file)) {
            if (!empty($_POST["old_logo_bent_vision"]) && $_POST["old_logo_bent_vision"] !== $target_file) {
                $old = $_POST["old_logo_bent_vision"];
                if (is_file($old)) { @unlink($old); }
            }
            $logo_bent_vision = $target_file;
        }
    }
    if (isset($_FILES["download_pdf"]) && $_FILES["download_pdf"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_download_" . basename($_FILES["download_pdf"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["download_pdf"]["tmp_name"], $target_file)) {
            if (!empty($_POST["old_download_pdf"]) && $_POST["old_download_pdf"] !== $target_file) {
                $old = $_POST["old_download_pdf"];
                if (is_file($old)) { @unlink($old); }
            }
            $download_pdf = $target_file;
        }
    }

    // Sütunun var olup olmadığını kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM company_info_common LIKE 'download_pdf'");
    $column_exists = $stmt->fetch();

    // Ortak alan güncelle/ekle
    if ($common['id']) {
        if ($column_exists) {
            $stmt = $pdo->prepare("UPDATE company_info_common SET phone=?, phone1=?, phone2=?, email=?, email1=?, email2=?, logo_bent_media=?, logo_bent_tech=?, logo_bent_vision=?, download_pdf=? WHERE id=?");
            $result = $stmt->execute([$phone, $phone1, $phone2, $email, $email1, $email2, $logo_bent_media, $logo_bent_tech, $logo_bent_vision, $download_pdf, $common['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE company_info_common SET phone=?, phone1=?, phone2=?, email=?, email1=?, email2=?, logo_bent_media=?, logo_bent_tech=?, logo_bent_vision=? WHERE id=?");
            $result = $stmt->execute([$phone, $phone1, $phone2, $email, $email1, $email2, $logo_bent_media, $logo_bent_tech, $logo_bent_vision, $common['id']]);
        }
    } else {
        if ($column_exists) {
            $stmt = $pdo->prepare("INSERT INTO company_info_common (phone, phone1, phone2, email, email1, email2, logo_bent_media, logo_bent_tech, logo_bent_vision, download_pdf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$phone, $phone1, $phone2, $email, $email1, $email2, $logo_bent_media, $logo_bent_tech, $logo_bent_vision, $download_pdf]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO company_info_common (phone, phone1, phone2, email, email1, email2, logo_bent_media, logo_bent_tech, logo_bent_vision) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$phone, $phone1, $phone2, $email, $email1, $email2, $logo_bent_media, $logo_bent_tech, $logo_bent_vision]);
        }
    }
    if (!$result) $success = false;

    // Her dil için name/address/meta güncelle/ekle
    foreach ($langs as $lang) {
        $name = trim($_POST["name_$lang"] ?? '');
        $address = trim($_POST["address_$lang"] ?? '');
        $meta_keywords = trim($_POST["meta_keywords_$lang"] ?? '');
        $meta_description = trim($_POST["meta_description_$lang"] ?? '');

        $stmt = $pdo->prepare("SELECT id FROM company_infos WHERE lang_code = ?");
        $stmt->execute([$lang]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE company_infos SET name=?, address=?, meta_keywords=?, meta_description=? WHERE lang_code=?");
            $result = $stmt->execute([$name, $address, $meta_keywords, $meta_description, $lang]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO company_infos (lang_code, name, address, meta_keywords, meta_description) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$lang, $name, $address, $meta_keywords, $meta_description]);
        }
        if (!$result) $success = false;
    }

    $message = $success
        ? "<div class='alert alert-success'>All company infos updated successfully.</div>"
        : "<div class='alert alert-danger'>Error updating records.</div>";
}

// Mevcut verileri çek
$company_infos = [];
$stmt = $pdo->query("SELECT * FROM company_infos");
while ($row = $stmt->fetch()) {
    $company_infos[$row['lang_code']] = $row;
}
// Ortak alanları tekrar çek
$stmt = $pdo->query("SELECT * FROM company_info_common LIMIT 1");
$common = $stmt->fetch() ?: ['phone'=>'', 'phone1'=>'', 'phone2'=>'', 'email'=>'', 'email1'=>'', 'email2'=>'', 'logo_bent_media'=>'', 'logo_bent_tech'=>'', 'logo_bent_vision'=>'', 'download_pdf'=>''];
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message" class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Company Infos Management (All Languages)</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php foreach (['en'=>'English', 'tm'=>'Türkmençe', 'ru'=>'Русский'] as $lang => $label): ?>
                        <div class="mb-3 border-bottom pb-2">
                            <h6><?php echo $label; ?> (<?php echo strtoupper($lang); ?>)</h6>
                            <label for="name_<?php echo $lang; ?>" class="form-label">Company Name</label>
                            <input type="text" class="form-control mb-2" id="name_<?php echo $lang; ?>" name="name_<?php echo $lang; ?>"
                                   value="<?php echo htmlspecialchars($company_infos[$lang]['name'] ?? ''); ?>" required>
                            <label for="address_<?php echo $lang; ?>" class="form-label">Address</label>
                            <textarea class="form-control mb-2" id="address_<?php echo $lang; ?>" name="address_<?php echo $lang; ?>" rows="3" required><?php echo htmlspecialchars($company_infos[$lang]['address'] ?? ''); ?></textarea>
                            
                            <label for="meta_keywords_<?php echo $lang; ?>" class="form-label">Meta Keywords</label>
                            <textarea class="form-control mb-2" id="meta_keywords_<?php echo $lang; ?>" name="meta_keywords_<?php echo $lang; ?>" rows="2"><?php echo htmlspecialchars($company_infos[$lang]['meta_keywords'] ?? ''); ?></textarea>
                            
                            <label for="meta_description_<?php echo $lang; ?>" class="form-label">Meta Description</label>
                            <textarea class="form-control mb-2" id="meta_description_<?php echo $lang; ?>" name="meta_description_<?php echo $lang; ?>" rows="2"><?php echo htmlspecialchars($company_infos[$lang]['meta_description'] ?? ''); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    <div class="mb-3 border-bottom pb-2">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control mb-2" id="phone" name="phone" value="<?php echo htmlspecialchars($common['phone']); ?>" required>
                        <label for="phone1" class="form-label">Phone 1</label>
                        <input type="text" class="form-control mb-2" id="phone1" name="phone1" value="<?php echo htmlspecialchars($common['phone1'] ?? ''); ?>" >
                        <label for="phone2" class="form-label">Phone 2</label>
                        <input type="text" class="form-control mb-2" id="phone2" name="phone2" value="<?php echo htmlspecialchars($common['phone2'] ?? ''); ?>">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control mb-2" id="email" name="email" value="<?php echo htmlspecialchars($common['email']); ?>" required>
                        <label for="email1" class="form-label">Email 1</label>
                        <input type="email" class="form-control mb-2" id="email1" name="email1" value="<?php echo htmlspecialchars($common['email1'] ?? ''); ?>" >
                        <label for="email2" class="form-label">Email 2</label>
                        <input type="email" class="form-control mb-2" id="email2" name="email2" value="<?php echo htmlspecialchars($common['email2'] ?? ''); ?>">
                        <br>
                        <label for="logo_bent_media" class="form-label"><i class="bi bi-badge-tm text-danger"></i> Bent Media Logo</label>
                        <input type="file" class="form-control mb-2" id="logo_bent_media" name="logo_bent_media">
                        <?php if (!empty($common['logo_bent_media'])): ?>
                            <img src="<?php echo $common['logo_bent_media']; ?>" style="max-width:150px;max-height:60px;" class="mt-2 border p-2">
                            <input type="hidden" name="old_logo_bent_media" value="<?php echo $common['logo_bent_media']; ?>">
                        <?php endif; ?>
                        <br><br>
                        <label for="logo_bent_tech" class="form-label"><i class="bi bi-gear text-primary"></i> Bent Tech Logo</label>
                        <input type="file" class="form-control mb-2" id="logo_bent_tech" name="logo_bent_tech">
                        <?php if (!empty($common['logo_bent_tech'])): ?>
                            <img src="<?php echo $common['logo_bent_tech']; ?>" style="max-width:150px;max-height:60px;" class="mt-2 border p-2">
                            <input type="hidden" name="old_logo_bent_tech" value="<?php echo $common['logo_bent_tech']; ?>">
                        <?php endif; ?>
                        <br><br>
                        <label for="logo_bent_vision" class="form-label"><i class="bi bi-camera-video text-success"></i> Bent Vision Logo</label>
                        <input type="file" class="form-control mb-2" id="logo_bent_vision" name="logo_bent_vision">
                        <?php if (!empty($common['logo_bent_vision'])): ?>
                            <img src="<?php echo $common['logo_bent_vision']; ?>" style="max-width:150px;max-height:60px;" class="mt-2 border p-2">
                            <input type="hidden" name="old_logo_bent_vision" value="<?php echo $common['logo_bent_vision']; ?>">
                        <?php endif; ?>
                        <br><br><br>
                        <label for="download_pdf" class="form-label">Catalog PDF (Navbar Download Button)</label>
                        <input type="file" class="form-control mb-2" id="download_pdf" name="download_pdf" accept=".pdf">
                        <?php if (!empty($common['download_pdf'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo $common['download_pdf']; ?>" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-file-pdf"></i> View Current PDF</a>
                                <input type="hidden" name="old_download_pdf" value="<?php echo $common['download_pdf']; ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Save All</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});
</script>