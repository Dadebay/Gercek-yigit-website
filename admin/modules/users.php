<?php
$message = '';

// Kullanıcı ekleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $image = '';
    $target_dir = "uploads/users/";

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        $image = $target_file;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, image, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $result = $stmt->execute([$username, $email, $hashed_password, $image]);

    $message = $result
        ? "<div class='alert alert-success'>User added successfully.</div>"
        : "<div class='alert alert-danger'>Error adding user.</div>";
}

// Kullanıcı silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // $_SESSION['user_id'] güncel kullanıcının id'si olmalı
    if ($id == $_SESSION['user_id']) {
        header("Location: logout.php");
        exit;
    }

    // delete user image if exists
    $u = $pdo->prepare("SELECT image FROM users WHERE id=?");
    $u->execute([$id]);
    $urow = $u->fetch();
    if ($urow && !empty($urow['image']) && is_file($urow['image'])) { @unlink($urow['image']); }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $result = $stmt->execute([$id]);
    $message = $result
        ? "<div class='alert alert-success'>User deleted.</div>"
        : "<div class='alert alert-danger'>Error deleting user.</div>";
}

// Kullanıcı düzenleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $id = intval($_POST['user_id']);
    $username = trim($_POST["edit_username"] ?? '');
    $email = trim($_POST["edit_email"] ?? '');
    $password = trim($_POST["edit_password"] ?? '');
    $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
    $old_image = $_POST["old_image"] ?? '';
    $target_dir = "uploads/users/";
    $image = $old_image;

    if (isset($_FILES["edit_image"]) && $_FILES["edit_image"]["error"] == UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . "_" . basename($_FILES["edit_image"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
            if (!empty($old_image) && $old_image !== $target_file) {
                if (is_file($old_image)) { @unlink($old_image); }
            }
            $image = $target_file;
        }
    }

    if (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=?, image=?, updated_at=NOW() WHERE id=?");
        $result = $stmt->execute([$username, $email, $hashed_password, $image, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, image=?, updated_at=NOW() WHERE id=?");
        $result = $stmt->execute([$username, $email, $image, $id]);
    }

    $message = $result
        ? "<div class='alert alert-success'>User updated.</div>"
        : "<div class='alert alert-danger'>Error updating user.</div>";
}

// Mevcut kullanıcıları çek
$users = [];
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
while ($row = $stmt->fetch()) $users[] = $row;
?>

<div class="row">
    <div class="col-12">
        <?php if ($message): ?>
            <div id="alert-message" class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add User Button -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
            Add User
        </button>

        <!-- Users List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Users List</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($user['image'])): ?>
                                        <img src="<?php echo $user['image']; ?>" style="max-width:40px;max-height:40px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($user['updated_at']); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-warning"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editUserModal"
                                       data-id="<?php echo $user['id']; ?>"
                                       data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                       data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                       data-image="<?php echo $user['image']; ?>"
                                    >Edit</a>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="mb-3">
                    <label for="edit_username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="edit_username" name="edit_username" required>
                </div>
                <div class="mb-3">
                    <label for="edit_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                </div>
                <div class="mb-3">
                    <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" id="edit_password" name="edit_password">
                </div>
                <div class="mb-3">
                    <label for="edit_image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="edit_image" name="edit_image">
                    <img id="edit_image_preview" src="" style="max-width:40px;max-height:40px;" class="mt-2">
                    <input type="hidden" name="old_image" id="edit_old_image">
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editUserModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_user_id').value = button.getAttribute('data-id');
        document.getElementById('edit_username').value = button.getAttribute('data-username');
        document.getElementById('edit_email').value = button.getAttribute('data-email');
        document.getElementById('edit_old_image').value = button.getAttribute('data-image');
        document.getElementById('edit_image_preview').src = button.getAttribute('data-image');
    });

    var alert = document.getElementById('alert-message');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 3000);
    }
});
</script>