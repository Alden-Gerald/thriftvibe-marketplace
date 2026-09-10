<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStore();

$db = getDB();
$userId = getUserId();
$store = getStore($userId);
$isPremium = isPremium($store['storeid']);

$errors = [];
$success = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = trim($_POST['store_name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($storeName)) {
        $errors['store_name'] = 'Nama toko wajib diisi';
    } elseif (strlen($storeName) < 3) {
        $errors['store_name'] = 'Nama toko minimal 3 karakter';
    } else {
        $stmt = $db->prepare("SELECT storeid FROM stores WHERE storename = ? AND storeid != ?");
        $stmt->execute([$storeName, $store['storeid']]);
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
        $stmt = $db->prepare("UPDATE stores SET storename = ?, city = ?, description = ? WHERE storeid = ?");
        $stmt->execute([$storeName, $city, $description ?: null, $store['storeid']]);
        $success = true;
        $store = getStore($userId);
    }
}

$pageTitle = 'Pengaturan Toko';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="dashboard-main">
    <div class="page-header">
        <h1>⚙️ Pengaturan Toko</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Pengaturan toko berhasil diperbarui!</div>
    <?php endif; ?>

    <div class="settings-page">

        <div class="preview-card">
            <div class="preview-avatar">
                <?= strtoupper(substr($store['storename'], 0, 1)) ?>
            </div>
            <div class="preview-details">
                <h2><?= sanitize($store['storename']) ?></h2>
                <p>📍 <?= sanitize($store['city']) ?></p>
                <span class="badge <?= $isPremium ? 'badge-premium' : 'badge-regular' ?>">
                    <?= $isPremium ? '⭐ Star Seller' : 'Regular' ?>
                </span>
            </div>
        </div>


        <div class="form-card">
            <form action="" method="POST">
                <div class="form-group">
                    <label for="store_name">Nama Toko</label>
                    <input type="text" id="store_name" name="store_name"
                        value="<?= htmlspecialchars($store['storename']) ?>"
                        class="<?= isset($errors['store_name']) ? 'error' : '' ?>">
                    <?php if (isset($errors['store_name'])): ?>
                        <span class="error-msg"><?= $errors['store_name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="city">Kota Asal</label>
                    <select id="city" name="city" class="<?= isset($errors['city']) ? 'error' : '' ?>">
                        <option value="">-- Pilih Kota --</option>
                        <?php foreach (getCities() as $c): ?>
                            <option value="<?= $c ?>" <?= $store['city'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['city'])): ?>
                        <span class="error-msg"><?= $errors['city'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi <span class="hint">(opsional, maks 200 karakter)</span></label>
                    <textarea id="description" name="description"
                        rows="3"><?= htmlspecialchars($store['description'] ?? '') ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <span class="error-msg"><?= $errors['description'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</main>
</div>

<style>
    .settings-page {
        max-width: 500px;
        margin: 0 auto;
    }

    .preview-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }

    .preview-avatar {
        width: 60px;
        height: 60px;
        background: var(--color-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
    }

    .preview-details h2 {
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }

    .preview-details p {
        font-size: 0.85rem;
        color: var(--color-text-muted);
        margin-bottom: 0.5rem;
    }

    .badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .badge-regular {
        background: var(--color-surface-2);
        color: var(--color-text-muted);
    }

    .badge-premium {
        background: rgba(234, 179, 8, 0.2);
        color: #eab308;
    }

    .form-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .form-group .hint {
        font-weight: 400;
        color: var(--color-text-muted);
        font-size: 0.8rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: 8px;
        color: var(--color-text);
        font-size: 0.9rem;
        font-family: inherit;
        resize: none;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--color-primary);
    }

    .form-group input.error,
    .form-group select.error,
    .form-group textarea.error {
        border-color: #ef4444;
    }

    .error-msg {
        display: block;
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.35rem;
    }

    .form-card .btn {
        margin-top: 0.5rem;
    }

    /* Custom Scrollbar for Textarea */
    .form-group textarea {
        overflow-y: auto;
    }

    .form-group textarea::-webkit-scrollbar {
        width: 8px;
    }

    .form-group textarea::-webkit-scrollbar-track {
        background: var(--color-surface-2);
        border-radius: 4px;
    }

    .form-group textarea::-webkit-scrollbar-thumb {
        background: var(--color-primary);
        border-radius: 4px;
    }

    .form-group textarea::-webkit-scrollbar-thumb:hover {
        background: var(--color-primary-hover);
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>