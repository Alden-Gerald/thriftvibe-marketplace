<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();
$productId = (int) ($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: /');
    exit;
}


$stmt = $db->prepare("SELECT p.*, s.storename FROM products p JOIN stores s ON p.storeid = s.storeid WHERE p.productid = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $validReasons = ['illegal_content', 'counterfeit', 'fraud', 'spam', 'other'];
    if (!in_array($reason, $validReasons)) {
        $errors['reason'] = 'Pilih alasan laporan';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO reports (reporterid, reportedtype, reportedid, reason) VALUES (?, 'product', ?, ?)");
        $stmt->execute([$userId, $productId, $reason . ($description ? ': ' . $description : '')]);
        $success = true;
    }
}

$pageTitle = 'Laporkan Produk';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="section" style="max-width: 600px; margin: 0 auto;">
        <h1 class="section-title">🚨 Laporkan Produk</h1>

        <?php if ($success): ?>
            <div class="section-card text-center">
                <div class="empty-icon">✅</div>
                <h2>Laporan Terkirim</h2>
                <p>Terima kasih atas laporan Anda. Tim kami akan meninjau produk ini.</p>
                <a href="/detail.php?id=<?= $productId ?>" class="btn btn-outline">Kembali ke Produk</a>
            </div>
        <?php else: ?>
            <div class="section-card">
                <div class="product-summary">
                    <img src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>" alt="" class="product-thumb">
                    <div>
                        <h3><?= sanitize($product['productname']) ?></h3>
                        <span><?= sanitize($product['storename']) ?></span>
                    </div>
                </div>

                <form action="" method="POST" class="report-form">
                    <div class="form-group">
                        <label>Alasan Laporan</label>
                        <div class="radio-options">
                            <label class="radio-option">
                                <input type="radio" name="reason" value="illegal_content" <?= ($_POST['reason'] ?? '') === 'illegal_content' ? 'checked' : '' ?>>
                                <span>Konten Ilegal</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="reason" value="counterfeit" <?= ($_POST['reason'] ?? '') === 'counterfeit' ? 'checked' : '' ?>>
                                <span>Barang Palsu / KW</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="reason" value="fraud" <?= ($_POST['reason'] ?? '') === 'fraud' ? 'checked' : '' ?>>
                                <span>Penipuan</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="reason" value="spam" <?= ($_POST['reason'] ?? '') === 'spam' ? 'checked' : '' ?>>
                                <span>Spam / Duplikat</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="reason" value="other" <?= ($_POST['reason'] ?? '') === 'other' ? 'checked' : '' ?>>
                                <span>Lainnya</span>
                            </label>
                        </div>
                        <?php if (isset($errors['reason'])): ?>
                            <span class="error-text"><?= $errors['reason'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="description">Deskripsi <span class="optional">(opsional)</span></label>
                        <textarea id="description" name="description" rows="4" class="form-control"
                            placeholder="Jelaskan lebih detail masalah dengan produk ini..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="/detail.php?id=<?= $productId ?>" class="btn btn-outline">Batal</a>
                        <button type="submit" class="btn btn-danger">Kirim Laporan</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .product-summary {
        display: flex;
        gap: 1rem;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--color-border);
    }

    .product-thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: var(--radius-md);
    }

    .product-summary h3 {
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .product-summary span {
        color: var(--color-text-muted);
        font-size: 0.9rem;
    }

    .radio-options {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: var(--color-surface-2);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: var(--transition);
    }

    .radio-option:hover {
        background: var(--color-border);
    }

    .radio-option input {
        accent-color: var(--color-primary);
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>