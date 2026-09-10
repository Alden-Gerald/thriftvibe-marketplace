<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $productId = (int) ($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($productId) {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT IGNORE INTO wishlists (userid, productid) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            echo json_encode(['success' => true, 'action' => 'added']);
        } elseif ($action === 'remove') {
            $stmt = $db->prepare("DELETE FROM wishlists WHERE userid = ? AND productid = ?");
            $stmt->execute([$userId, $productId]);
            echo json_encode(['success' => true, 'action' => 'removed']);
        }
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}


$stmt = $db->prepare("
    SELECT p.*, s.storename, s.city, s.membershiplevel, w.createdat as addedat
    FROM wishlists w
    JOIN products p ON w.productid = p.productid
    JOIN stores s ON p.storeid = s.storeid
    WHERE w.userid = ?
    ORDER BY w.createdat DESC
");
$stmt->execute([$userId]);
$wishlist = $stmt->fetchAll();

$pageTitle = 'Wishlist Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="section">
        <div class="section-header">
            <h1 class="section-title">Wishlist Saya</h1>
            <span class="result-count"><?= count($wishlist) ?> item</span>
        </div>

        <?php if (empty($wishlist)): ?>
            <div class="empty-state">
                <div class="empty-icon">💔</div>
                <h2>Wishlist Kosong</h2>
                <p>Simpan produk yang kamu suka untuk dilihat lagi nanti!</p>
                <a href="/browse.php" class="btn btn-primary">Jelajahi Produk</a>
            </div>
        <?php else: ?>
            <div class="video-grid grid-4">
                <?php foreach ($wishlist as $item): ?>
                    <div class="video-card" data-product-id="<?= $item['productid'] ?>">
                        <a href="/detail.php?id=<?= $item['productid'] ?>">
                            <div class="video-wrapper">
                                <img src="/assets/uploads/thumbnails/<?= $item['thumbnail'] ?>"
                                    alt="<?= sanitize($item['productname']) ?>" class="video-thumbnail">
                                <?php if ($item['membershiplevel'] === 'premium'): ?>
                                    <span class="video-badge premium-badge">⭐ Star Seller</span>
                                <?php endif; ?>
                            </div>
                            <div class="video-info">
                                <h3 class="video-title"><?= sanitize(truncate($item['productname'], 40)) ?></h3>
                                <span class="video-price"><?= formatRupiah($item['price']) ?></span>
                                <span class="video-store-name"><?= sanitize($item['storename']) ?></span>
                            </div>
                        </a>
                        <button class="wishlist-btn active" onclick="toggleWishlist(<?= $item['productid'] ?>, this)">
                            ❤️ Hapus dari Wishlist
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .wishlist-btn {
        width: 100%;
        padding: 0.75rem;
        background: var(--color-surface-2);
        border: none;
        color: var(--color-text-muted);
        cursor: pointer;
        transition: var(--transition);
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    }

    .wishlist-btn:hover {
        background: var(--color-error);
        color: white;
    }

    .wishlist-btn.active {
        color: var(--color-error);
    }
</style>

<script>
    function toggleWishlist(productId, btn) {
        fetch('/member/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&product_id=${productId}`
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.closest('.video-card').remove();

                    const remaining = document.querySelectorAll('.video-card').length;
                    document.querySelector('.result-count').textContent = remaining + ' item';
                    if (remaining === 0) location.reload();
                }
            });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>