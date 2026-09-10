<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();


if (hasStore($userId)) {
    setFlash('info', 'Anda sudah memiliki toko');
    header('Location: /seller/dashboard.php');
    exit;
}

$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = trim($_POST['store_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $description = trim($_POST['description'] ?? '');


    if (empty($storeName)) {
        $errors['store_name'] = 'Nama toko wajib diisi';
    } elseif (strlen($storeName) < 5) {
        $errors['store_name'] = 'Nama toko minimal 5 karakter';
    } else {

        $stmt = $db->prepare("SELECT storeid FROM stores WHERE storename = ?");
        $stmt->execute([$storeName]);
        if ($stmt->fetch()) {
            $errors['store_name'] = 'Nama toko sudah digunakan';
        }
    }


    if (empty($city)) {
        $errors['city'] = 'Pilih kota asal';
    }


    if (strlen($description) > 200) {
        $errors['description'] = 'Deskripsi maksimal 200 karakter';
    }

    if (empty($errors)) {

        $stmt = $db->prepare("INSERT INTO stores (userid, storename, city, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $storeName, $city, $description ?: null]);


        $stmt = $db->prepare("UPDATE users SET role = 'seller' WHERE userid = ?");
        $stmt->execute([$userId]);
        $_SESSION['user']['role'] = 'seller';

        setFlash('success', 'Selamat! Toko Anda berhasil dibuat. Mulai upload produk sekarang!');
        header('Location: /seller/dashboard.php');
        exit;
    }
}

$pageTitle = 'Buka Toko Gratis';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .buka-toko-page {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .buka-toko-container {
        max-width: 500px;
        width: 100%;
    }

    .buka-toko-card {
        background: var(--color-surface);
        border-radius: var(--radius-xl);
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .card-header {
        background: var(--color-primary);
        padding: 2rem;
        text-align: center;
        color: white;
    }

    .card-header h1 {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }

    .card-header p {
        opacity: 0.9;
        font-size: 0.95rem;
    }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        padding: 1.5rem;
        background: var(--color-surface-2);
        border-bottom: 1px solid var(--color-border);
    }

    .benefit-item {
        text-align: center;
    }

    .benefit-icon {
        display: block;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .benefit-item h4 {
        font-size: 0.85rem;
        color: var(--color-text);
        margin-bottom: 0.25rem;
    }

    .benefit-item p {
        font-size: 0.75rem;
        color: var(--color-text-muted);
        line-height: 1.4;
    }

    .buka-toko-form {
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--color-text);
    }

    .form-group label .optional {
        font-weight: 400;
        color: var(--color-text-muted);
        font-size: 0.85rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--color-surface-2);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        color: var(--color-text);
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(38, 166, 154, 0.15);
    }

    .form-control.error {
        border-color: #ef5350;
    }

    .form-hint {
        display: block;
        margin-top: 0.35rem;
        font-size: 0.8rem;
        color: var(--color-text-muted);
    }

    .error-text {
        display: block;
        margin-top: 0.35rem;
        font-size: 0.8rem;
        color: #ef5350;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }

    .form-notice {
        display: flex;
        gap: 0.75rem;
        padding: 1rem;
        background: rgba(38, 166, 154, 0.1);
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
    }

    .form-notice svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        color: var(--color-primary);
    }

    .form-notice p {
        font-size: 0.85rem;
        color: var(--color-text-muted);
        line-height: 1.5;
    }

    .btn-block {
        width: 100%;
        padding: 0.875rem 1.5rem;
        font-size: 1rem;
        font-weight: 600;
    }

    @media (max-width: 500px) {
        .benefits-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-align: left;
        }

        .benefit-icon {
            margin-bottom: 0;
        }
    }
</style>

<div class="buka-toko-page">
    <div class="buka-toko-container">
        <div class="buka-toko-card">
            <div class="card-header">
                <h1>Buka Toko Gratis</h1>
                <p>Mulai jual koleksi thrift-mu di ThriftVibe</p>
            </div>

            <div class="benefits-grid">
                <div class="benefit-item">
                    <span class="benefit-icon">🏪</span>
                    <div>
                        <h4>Gratis Selamanya</h4>
                        <p>Tidak ada biaya untuk membuka toko</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">📦</span>
                    <div>
                        <h4>Upload Mudah</h4>
                        <p>Tambah produk dengan cepat</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <span class="benefit-icon">⭐</span>
                    <div>
                        <h4>Star Seller</h4>
                        <p>Upgrade untuk fitur lebih</p>
                    </div>
                </div>
            </div>

            <form action="" method="POST" class="buka-toko-form">
                <div class="form-group">
                    <label for="store_name">Nama Toko</label>
                    <input type="text" id="store_name" name="store_name"
                        value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>"
                        class="form-control <?= isset($errors['store_name']) ? 'error' : '' ?>"
                        placeholder="Contoh: Thrift Vintage Jakarta" minlength="5" required>
                    <small class="form-hint">Minimal 5 karakter, harus unik</small>
                    <?php if (isset($errors['store_name'])): ?>
                        <span class="error-text"><?= $errors['store_name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="city">Kota Asal</label>
                    <select id="city" name="city" class="form-control <?= isset($errors['city']) ? 'error' : '' ?>"
                        required>
                        <option value="">Pilih Kota</option>
                        <?php foreach (getCities() as $cityOption): ?>
                            <option value="<?= $cityOption ?>" <?= ($_POST['city'] ?? '') === $cityOption ? 'selected' : '' ?>>
                                <?= $cityOption ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Untuk estimasi ongkir pembeli</small>
                    <?php if (isset($errors['city'])): ?>
                        <span class="error-text"><?= $errors['city'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi Toko <span class="optional">(opsional)</span></label>
                    <textarea id="description" name="description" rows="3"
                        class="form-control <?= isset($errors['description']) ? 'error' : '' ?>"
                        placeholder="Ceritakan tentang toko kamu..."
                        maxlength="200"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <small class="form-hint">Maksimal 200 karakter</small>
                    <?php if (isset($errors['description'])): ?>
                        <span class="error-text"><?= $errors['description'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-notice">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <p>Dengan membuat toko, Anda menyetujui ketentuan layanan ThriftVibe. Satu akun hanya dapat memiliki
                        satu toko.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Buat Toko Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>