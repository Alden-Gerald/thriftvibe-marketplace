<?php


require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$storeId = (int) ($_GET['id'] ?? 0);

if (!$storeId) {
    header('Location: /');
    exit;
}


$stmt = $db->prepare("SELECT s.*, c.fullname as ownername FROM stores s JOIN users c ON s.userid = c.userid WHERE s.storeid = ?");
$stmt->execute([$storeId]);
$store = $stmt->fetch();

if (!$store) {
    header('Location: /');
    exit;
}

$isPremium = isPremium($storeId);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $userId = getUserId();

    if ($action === 'follow') {
        $stmt = $db->prepare("INSERT IGNORE INTO follows (userid, storeid) VALUES (?, ?)");
        $stmt->execute([$userId, $storeId]);
        $db->prepare("UPDATE stores SET totalfollowers = totalfollowers + 1 WHERE storeid = ?")->execute([$storeId]);
        echo json_encode(['success' => true, 'following' => true]);
    } elseif ($action === 'unfollow') {
        $stmt = $db->prepare("DELETE FROM follows WHERE userid = ? AND storeid = ?");
        $stmt->execute([$userId, $storeId]);
        $db->prepare("UPDATE stores SET totalfollowers = totalfollowers - 1 WHERE storeid = ?")->execute([$storeId]);
        echo json_encode(['success' => true, 'following' => false]);
    }
    exit;
}


$isFollowing = false;
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT 1 FROM follows WHERE userid = ? AND storeid = ?");
    $stmt->execute([getUserId(), $storeId]);
    $isFollowing = (bool) $stmt->fetch();
}


$stmt = $db->prepare("SELECT * FROM products WHERE storeid = ? AND status = 'active' ORDER BY uploadedat DESC");
$stmt->execute([$storeId]);
$products = $stmt->fetchAll();


$stmt = $db->prepare("
    SELECT r.*, c.fullname, c.profilephoto 
    FROM reviews r 
    JOIN users c ON r.userid = c.userid 
    WHERE r.storeid = ? 
    ORDER BY r.createdat DESC 
    LIMIT 10
");
$stmt->execute([$storeId]);
$reviews = $stmt->fetchAll();

$pageTitle = $store['storename'];
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-content">
    <div class="store-profile">
        <div class="store-header">
            <div class="store-avatar large">
                <span><?= strtoupper(substr($store['storename'], 0, 1)) ?></span>
            </div>
            <div class="store-header-info">
                <h1 class="store-name-large">
                    <?= sanitize($store['storename']) ?>
                    <?php if ($isPremium): ?>
                        <span class="verified-icon">✓</span>
                    <?php endif; ?>
                </h1>
                <p class="store-location">📍 <?= sanitize($store['city']) ?></p>
                <div class="store-stats">
                    <div class="store-stat">
                        <span class="stat-value"><?= count($products) ?></span>
                        <span class="stat-label">Produk</span>
                    </div>
                    <div class="store-stat">
                        <span class="stat-value"><?= $store['totalfollowers'] ?? 0 ?></span>
                        <span class="stat-label">Pengikut</span>
                    </div>
                    <div class="store-stat">
                        <span class="stat-value"><?= number_format($store['totalrating'] ?? 0, 1) ?> ⭐</span>
                        <span class="stat-label"><?= $store['totalreviews'] ?? 0 ?> Ulasan</span>
                    </div>
                </div>
                <?php if ($store['description']): ?>
                    <p class="store-desc"><?= sanitize($store['description']) ?></p>
                <?php endif; ?>
            </div>
            <div class="store-header-actions">
                <?php if (isLoggedIn() && getUserId() != $store['userid']): ?>
                    <button class="btn <?= $isFollowing ? 'btn-outline' : 'btn-primary' ?>" id="followBtn"
                        onclick="toggleFollow()">
                        <?= $isFollowing ? '✓ Mengikuti' : '+ Ikuti' ?>
                    </button>
                <?php endif; ?>
                <?php if ($isPremium): ?>
                    <span class="star-seller-badge">⭐ Star Seller</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="store-content">
            <div class="store-tabs">
                <button class="tab-btn active" onclick="showTab('products')">Produk (<?= count($products) ?>)</button>
                <button class="tab-btn" onclick="showTab('reviews')">Ulasan (<?= count($reviews) ?>)</button>
            </div>

            <div id="tab-products" class="tab-content active">
                <?php if (empty($products)): ?>
                    <div class="empty-state small">
                        <p>Belum ada produk</p>
                    </div>
                <?php else: ?>
                    <div class="video-grid grid-4">
                        <?php foreach ($products as $product): ?>
                            <a href="/detail.php?id=<?= $product['productid'] ?>" class="video-card">
                                <div class="video-wrapper">
                                    <img src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>"
                                        alt="<?= sanitize($product['productname']) ?>" class="video-thumbnail">
                                </div>
                                <div class="video-info">
                                    <h3 class="video-title"><?= sanitize(truncate($product['productname'], 40)) ?></h3>
                                    <span class="video-price"><?= formatRupiah($product['price']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-reviews" class="tab-content">
                <?php if (empty($reviews)): ?>
                    <div class="empty-state small">
                        <p>Belum ada ulasan</p>
                    </div>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <img src="/assets/uploads/profile/<?= $review['profilephoto'] ?>" alt=""
                                        class="review-avatar">
                                    <div class="review-meta">
                                        <span class="review-author"><?= sanitize($review['fullname']) ?></span>
                                        <span class="review-date"><?= timeAgo($review['createdat']) ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?= str_repeat('⭐', $review['rating']) ?>
                                    </div>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <p class="review-content"><?= sanitize($review['comment']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
    .store-profile {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .store-header {
        display: flex;
        gap: 2rem;
        padding: 2rem;
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        margin-bottom: 2rem;
    }

    .store-avatar.large {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
    }

    .store-header-info {
        flex: 1;
    }

    .store-name-large {
        font-size: 1.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .store-location {
        color: var(--color-text-muted);
        margin-bottom: 1rem;
    }

    .store-stats {
        display: flex;
        gap: 2rem;
        margin-bottom: 1rem;
    }

    .store-stat {
        text-align: center;
    }

    .store-stat .stat-value {
        display: block;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .store-stat .stat-label {
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }

    .store-desc {
        color: var(--color-text-muted);
        max-width: 500px;
    }

    .store-header-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-end;
    }

    .store-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--color-border);
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 1rem;
        color: var(--color-text-muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
    }

    .tab-btn.active {
        color: var(--color-primary);
        border-bottom-color: var(--color-primary);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .review-card {
        background: var(--color-surface);
        padding: 1.5rem;
        border-radius: var(--radius-md);
    }

    .review-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .review-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .review-meta {
        flex: 1;
    }

    .review-author {
        display: block;
        font-weight: 600;
    }

    .review-date {
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }

    .review-content {
        color: var(--color-text-muted);
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .store-header {
            flex-direction: column;
            text-align: center;
        }

        .store-stats {
            justify-content: center;
        }
    }
</style>

<script>
    function showTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    }

    function toggleFollow() {
        const btn = document.getElementById('followBtn');
        const isFollowing = btn.classList.contains('btn-outline');
        const action = isFollowing ? 'unfollow' : 'follow';

        fetch('/toko.php?id=<?= $storeId ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}`
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.following) {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                        btn.textContent = '✓ Mengikuti';
                    } else {
                        btn.classList.remove('btn-outline');
                        btn.classList.add('btn-primary');
                        btn.textContent = '+ Ikuti';
                    }
                }
            });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>