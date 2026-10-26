<?php
require_once __DIR__ . '/../includes/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    $loginUrl = 'login.php';
    if (!headers_sent()) {
        header("Location: " . $loginUrl);
        exit;
    } else {
        echo '<script>window.location.href = "' . $loginUrl . '";</script>';
        exit;
    }
}

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT username, email, image FROM users WHERE id=? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $old_image = $user['image'] ?? '';
    $target_dir = "uploads/users/";
    $image = $old_image;

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $saved = save_uploaded_file($_FILES["image"], $target_dir, $allowed, 5*1024*1024);
        if ($saved) {
            if (!empty($old_image) && $old_image !== $saved && is_file($old_image)) { @unlink($old_image); }
            $image = $saved; 
        }
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=?, image=?, updated_at=NOW() WHERE id=?");
        $result = $stmt->execute([$username, $email, $hashed_password, $image, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, image=?, updated_at=NOW() WHERE id=?");
        $result = $stmt->execute([$username, $email, $image, $user_id]);
    }

    if ($result) {
        session_destroy();
        $redir = './login.php';
        if (!headers_sent()) {
            header("Location: " . $redir);
            exit;
        } else {
            echo '<script>window.location.href = "' . $redir . '";</script>';
            exit;
        }
    } else {
        $message = "<div class='alert alert-danger'>Error updating profile.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Update</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/icons/bootstrap-icons.min.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-circle"></i> Update Profile
                </div>
                <div class="card-body">
                    <?php if ($message) echo $message; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <?php if (!empty("../".$user['image'])): ?>
                                <img src="<?php echo "../".$user['image']; ?>" class="rounded-circle mb-2" style="width:64px;height:64px;object-fit:cover;">
                            <?php else: ?>
                                <span class="bi bi-person-circle fs-1 text-muted mb-2"></span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="image" name="image">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary w-100">Update & Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>