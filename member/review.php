<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();
$orderId = (int) ($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: /member/orders.php');
    exit;
}


$stmt = $db->prepare("
    SELECT o.*, s.storeid, s.storename
    FROM orders o
    JOIN orderdetails od ON o.orderid = od.orderid
    JOIN products p ON od.productid = p.productid
    JOIN stores s ON p.storeid = s.storeid
    WHERE o.orderid = ? AND o.userid = ? AND o.status = 'completed'
    LIMIT 1
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Pesanan tidak ditemukan atau belum selesai');
    header('Location: /member/orders.php');
    exit;
}


$stmt = $db->prepare("SELECT 1 FROM reviews WHERE userid = ? AND storeid = ? AND orderid = ?");
$stmt->execute([$userId, $order['storeid'], $orderId]);
if ($stmt->fetch()) {
    setFlash('error', 'Anda sudah memberikan ulasan untuk pesanan ini');
    header('Location: /member/orders.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Pilih rating 1-5';
    }

    if (empty($errors)) {

        $stmt = $db->prepare("INSERT INTO reviews (userid, storeid, orderid, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $order['storeid'], $orderId, $rating, $comment ?: null]);

        setFlash('success', 'Terima kasih atas ulasan Anda!');
        header('Location: /member/orders.php');
        exit;
    }
}

$pageTitle = 'Beri Ulasan';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="section" style="max-width: 600px; margin: 0 auto;">
        <h1 class="section-title">Beri Ulasan</h1>

        <div class="section-card">
            <div class="store-badge">
                <div class="store-avatar">
                    <span><?= strtoupper(substr($order['store_name'], 0, 1)) ?></span>
                </div>
                <div class="store-details">
                    <span class="store-name"><?= sanitize($order['store_name']) ?></span>
                    <span class="store-location">Pesanan #<?= $orderId ?></span>
                </div>
            </div>

            <form action="" method="POST" class="review-form">
                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-picker">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" <?= (($_POST['rating'] ?? 0) == $i) ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>" class="star-label">⭐</label>
                        <?php endfor; ?>
                    </div>
                    <?php if (isset($errors['rating'])): ?>
                        <span class="error-text"><?= $errors['rating'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="comment">Komentar <span class="optional">(opsional)</span></label>
                    <textarea id="comment" name="comment" rows="4" class="form-control"
                        placeholder="Bagikan pengalamanmu belanja di toko ini..."><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="/member/orders.php" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
    .review-form {
        margin-top: 1.5rem;
    }

    .rating-picker {
        display: flex;
        gap: 0.5rem;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }

    .rating-picker input {
        display: none;
    }

    .star-label {
        font-size: 2rem;
        cursor: pointer;
        filter: grayscale(100%);
        transition: var(--transition);
    }

    .star-label:hover,
    .star-label:hover~.star-label {
        filter: grayscale(0);
    }

    .rating-picker input:checked~.star-label {
        filter: grayscale(0);
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>