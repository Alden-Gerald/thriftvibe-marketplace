<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';


if (isLoggedIn()) {
    header('Location: /');
    exit;
}


if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_token']) || !isset($_SESSION['reset_expires'])) {
    header('Location: /auth/forgot-password.php');
    exit;
}


if (time() > $_SESSION['reset_expires']) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_token'], $_SESSION['reset_expires'], $_SESSION['reset_email'], $_SESSION['reset_name']);
    setFlash('error', 'Link reset password sudah kadaluarsa. Silakan coba lagi.');
    header('Location: /auth/forgot-password.php');
    exit;
}

$errors = [];
$userId = $_SESSION['reset_user_id'];
$userName = $_SESSION['reset_name'] ?? 'User';
$userEmail = $_SESSION['reset_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';


    if (empty($password)) {
        $errors['password'] = 'Password baru wajib diisi';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter';
    }

    if (empty($passwordConfirm)) {
        $errors['password_confirm'] = 'Konfirmasi password wajib diisi';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak cocok';
    }

    if (empty($errors)) {
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE userid = ?");
        $stmt->execute([$hashedPassword, $userId]);


        unset($_SESSION['reset_user_id'], $_SESSION['reset_token'], $_SESSION['reset_expires'], $_SESSION['reset_email'], $_SESSION['reset_name']);

        setFlash('success', 'Password berhasil direset! Silakan login dengan password baru.');
        header('Location: /auth/login.php');
        exit;
    }
}

$pageTitle = 'Reset Password';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ThriftVibe Market</title>
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
                <h1>Reset Password</h1>
                <p>Buat password baru untuk akun <strong><?= sanitize($userEmail) ?></strong></p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error"><?= $errors['general'] ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter"
                            class="form-control <?= isset($errors['password']) ? 'error' : '' ?>">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" style="display:none;">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                </path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-text"><?= $errors['password'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm"
                            placeholder="Ulangi password baru"
                            class="form-control <?= isset($errors['password_confirm']) ? 'error' : '' ?>">
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" style="display:none;">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24">
                                </path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="error-text"><?= $errors['password_confirm'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Simpan Password Baru</button>

                <div class="auth-footer">
                    <p>Ingat password? <a href="/auth/login.php">Login di sini</a></p>
                </div>
            </form>
        </div>

        <div class="auth-visual">
            <div class="visual-content">
                <div class="visual-icon">🔒</div>
                <h2>Password Aman</h2>
                <p>Buat password yang kuat untuk melindungi akun Anda. Gunakan minimal 6 karakter dengan kombinasi huruf
                    dan angka.</p>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>

</html>
