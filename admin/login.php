<?php
session_start();
require_once 'includes/db.php';

// If user is already logged in, redirect to the admin index page
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Login by username only
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verify password using password_verify
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user['id'];
            $_SESSION["username"] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid login credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body>
    <main class="login-form">
        <form action="login.php" method="post">
            <h1 class="h3 mb-3 fw-normal text-center">Admin Panel Login</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
