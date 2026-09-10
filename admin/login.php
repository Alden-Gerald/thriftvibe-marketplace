<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';


if (isLoggedIn() && getUser()['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors['general'] = 'Email dan password wajib diisi';
    } elseif (!str_ends_with($email, '@thriftvibe.com')) {
        $errors['general'] = 'Hanya email @thriftvibe.com yang dapat mengakses admin panel';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            setUserSession($user);
            header('Location: /admin/dashboard.php');
            exit;
        } else {
            $errors['general'] = 'Email atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ThriftVibe Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body class="auth-page admin-login">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <span class="brand-icon">🔥</span>
                <h1>Admin Panel</h1>
                <p>ThriftVibe Market</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error"><?= $errors['general'] ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <a href="/">← Kembali ke Homepage</a>
            </div>
        </div>
    </div>
</body>

</html>