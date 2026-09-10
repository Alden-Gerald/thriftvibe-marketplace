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
    $full_name = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';


    if (empty($full_name)) {
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi';
    }


    if (empty($email)) {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    } else {

        $db = getDB();
        $stmt = $db->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email sudah terdaftar';
        }
    }


    if (!empty($phone)) {
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            $errors['no_hp'] = 'Nomor HP tidak valid (10-15 digit)';
        }
    }


    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter';
    }


    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak cocok';
    }


    if (empty($errors)) {
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (fullname, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $hashedPassword, $phone ?: null]);


        $userId = $db->lastInsertId();
        $stmt = $db->prepare("SELECT * FROM users WHERE userid = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        setUserSession($user);
        setFlash('success', 'Registrasi berhasil! Selamat datang di ThriftVibe!');
        header('Location: /');
        exit;
    }
}

$pageTitle = 'Daftar';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - ThriftVibe Market</title>
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
                <h1>Buat Akun Baru</h1>
                <p>Gabung dan temukan thrift terbaik!</p>
            </div>

            <form action="" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap"
                        value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                        class="form-control <?= isset($errors['nama_lengkap']) ? 'error' : '' ?>"
                        placeholder="Nama lengkap kamu">
                    <?php if (isset($errors['nama_lengkap'])): ?>
                        <span class="error-text"><?= $errors['nama_lengkap'] ?></span>
                    <?php endif; ?>
                </div>

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
                    <label for="no_hp">Nomor HP <span class="optional">(opsional)</span></label>
                    <input type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>"
                        class="form-control <?= isset($errors['no_hp']) ? 'error' : '' ?>" placeholder="08xxxxxxxxxx">
                    <?php if (isset($errors['no_hp'])): ?>
                        <span class="error-text"><?= $errors['no_hp'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password"
                            class="form-control <?= isset($errors['password']) ? 'error' : '' ?>"
                            placeholder="Minimal 6 karakter">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
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
                            class="form-control <?= isset($errors['password_confirm']) ? 'error' : '' ?>"
                            placeholder="Ulangi password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="error-text"><?= $errors['password_confirm'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang</button>
            </form>

            <div class="auth-footer">
                <p>Sudah punya akun? <a href="/auth/login.php">Login</a></p>
            </div>
        </div>

        <div class="auth-visual">
            <div class="visual-content">
                <div class="visual-icon">✨</div>
                <h2>Jual & Beli Thrift</h2>
                <p>Buka toko gratis dan jual koleksi pakaian bekasmu lewat video pendek yang menarik!</p>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>

</html>
