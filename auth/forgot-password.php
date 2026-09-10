<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';


if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');


    if (empty($email)) {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!str_ends_with($email, '@gmail.com') && !str_ends_with($email, '@thriftvibe.com')) {
        $errors['email'] = 'Hanya email @gmail.com atau @thriftvibe.com yang dapat direset';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT userid, fullname, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {

            $_SESSION['reset_user_id'] = $user['userid'];
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_name'] = $user['fullname'];
            $_SESSION['reset_token'] = bin2hex(random_bytes(32));
            $_SESSION['reset_expires'] = time() + 3600; // 1 hour

            header('Location: /auth/reset-password.php');
            exit;
        } else {
            $errors['email'] = 'Email tidak ditemukan';
        }
    }
}

$pageTitle = 'Lupa Password';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - ThriftVibe Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="auth-logo">
                    <img src="/assets/img/logo.svg" alt="ThriftVibe" class="brand-logo">
                    <span class="brand-text">ThriftVibe</span>
                </a>
                <h1>Lupa Password?</h1>
                <p>Jangan khawatir! Masukkan email Anda dan kami akan membantu reset password</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error"><?= $errors['general'] ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="contoh@gmail.com"
                        value="<?= sanitize($_POST['email'] ?? '') ?>"
                        class="form-control <?= isset($errors['email']) ? 'error' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-text"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                    <small
                        style="color: var(--text-secondary); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                        Email @gmail.com atau @thriftvibe.com
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>

                <div style="text-align: center; margin: 1.5rem 0; color: var(--text-secondary); font-size: 0.875rem;">
                    <span>atau</span>
                </div>

                <a href="/auth/login.php" class="btn btn-outline btn-block">Kembali ke Login</a>
            </form>
        </div>

        <div class="auth-visual">
            <div class="visual-content">
                <div class="visual-icon">🔐</div>
                <h2>Keamanan Akun</h2>
                <p>Password Anda akan di-enkripsi dengan aman. Reset password berlaku selama 1 jam.</p>
            </div>
        </div>
    </div>
</body>

</html>
