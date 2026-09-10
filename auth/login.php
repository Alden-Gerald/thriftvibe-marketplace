<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';


if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';



    if (empty($email)) {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!str_ends_with($email, '@gmail.com') && !str_ends_with($email, '@thriftvibe.com')) {
        $errors['email'] = 'Hanya email @gmail.com atau @thriftvibe.com yang dapat login';
    }
    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            setUserSession($user);


            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
                exit;
            }


            $redirect = $_GET['redirect'] ?? '/';
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors['general'] = 'Email atau password salah';
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ThriftVibe Market</title>
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
                <h1>Selamat Datang Kembali</h1>
                <p>Login untuk mulai berbelanja atau berjualan</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error">
                    <?= $errors['general'] ?>
                </div>
            <?php endif; ?>

            <?php $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                        placeholder="email@example.com">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-text"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password"
                            class="form-control <?= isset($errors['password']) ? 'error' : '' ?>"
                            placeholder="••••••••">
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

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-footer">
                <p><a href="/auth/forgot-password.php">Lupa Password?</a></p>
                <p>Belum punya akun? <a href="/auth/register.php">Daftar Sekarang</a></p>
            </div>
        </div>

        <div class="auth-visual">
            <div class="visual-content">
                <div class="visual-icon">🛍️</div>
                <h2>Video-First Shopping</h2>
                <p>Lihat kondisi nyata barang bekas lewat video pendek sebelum beli. Lebih yakin, lebih puas!</p>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>

</html>
