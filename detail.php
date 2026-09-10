<?php


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();


$productId = (int) ($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: /');
    exit;
}


$stmt = $db->prepare("
    SELECT p.*, s.storeid, s.storename, s.city, s.membershiplevel, s.expireddate,
           c.fullname as ownername
    FROM products p
    JOIN stores s ON p.storeid = s.storeid
    JOIN users c ON s.userid = c.userid
    WHERE p.productid = ? AND p.status = 'active'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan');
    header('Location: /');
    exit;
}


$isPremiumStore = $product['membershiplevel'] === 'premium' &&
    ($product['expireddate'] === null || $product['expireddate'] >= date('Y-m-d'));


$isInWishlist = false;
if (isLoggedIn()) {
    $stmtWish = $db->prepare("SELECT wishlistid FROM wishlists WHERE userid = ? AND productid = ?");
    $stmtWish->execute([getUserId(), $productId]);
    $isInWishlist = $stmtWish->fetch() !== false;
}


$stmtRelated = $db->prepare("
    SELECT p.*, s.storename
    FROM products p
    JOIN stores s ON p.storeid = s.storeid
    WHERE p.storeid = ? AND p.productid != ? AND p.status = 'active'
    ORDER BY p.uploadedat DESC
    LIMIT 4
");
$stmtRelated->execute([$product['storeid'], $productId]);
$relatedProducts = $stmtRelated->fetchAll();

$pageTitle = $product['productname'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-page">
    <div class="detail-container">

        <div class="detail-video">
            <div class="video-player-wrapper">
                <video id="mainVideo" src="/assets/uploads/videos/<?= $product['videofile'] ?>"
                    poster="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>" controls playsinline
                    class="main-video"></video>
                <div class="video-controls">
                    <button class="control-btn" id="muteBtn" onclick="toggleMute()">
                        <svg class="unmuted-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                        </svg>
                        <svg class="muted-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            style="display:none;">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <line x1="23" y1="9" x2="17" y2="15"></line>
                            <line x1="17" y1="9" x2="23" y2="15"></line>
                        </svg>
                    </button>
                    <button class="control-btn" onclick="toggleFullscreen()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <polyline points="9 21 3 21 3 15"></polyline>
                            <line x1="21" y1="3" x2="14" y2="10"></line>
                            <line x1="3" y1="21" x2="10" y2="14"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>


        <div class="detail-info">

            <div class="store-badge">
                <div class="store-avatar">
                    <span><?= strtoupper(substr($product['storename'], 0, 1)) ?></span>
                </div>
                <div class="store-details">
                    <span class="store-name">
                        <?= sanitize($product['storename']) ?>
                        <?php if ($isPremiumStore): ?>
                            <span class="verified-icon" title="Star Seller">✓</span>
                        <?php endif; ?>
                    </span>
                    <span class="store-location">📍 <?= sanitize($product['city']) ?></span>
                </div>
                <?php if ($isPremiumStore): ?>
                    <span class="star-seller-badge">⭐ Star Seller</span>
                <?php endif; ?>
            </div>


            <h1 class="product-title"><?= sanitize($product['productname']) ?></h1>
            <div class="product-price"><?= formatRupiah($product['price']) ?></div>


            <div class="product-meta">
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    <?= sanitize($product['category']) ?>
                </span>
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?= timeAgo($product['uploadedat']) ?>
                </span>
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                    </svg>
                    Stok: <?= $product['stock'] ?>
                </span>
            </div>


            <div class="product-description">
                <h3>Deskripsi</h3>
                <p><?= nl2br(sanitize($product['description'] ?? 'Tidak ada deskripsi')) ?></p>
            </div>


            <div class="product-actions">
                <?php if (isLoggedIn()): ?>
                    <?php if ($product['stock'] > 0): ?>
                        <form action="/member/cart.php" method="POST" class="add-cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['productid'] ?>">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                Beli Sekarang
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg btn-block" disabled>
                            Stok Habis
                        </button>
                    <?php endif; ?>
                    <a href="#" class="btn btn-outline btn-lg btn-block" onclick="alert('Fitur chat belum tersedia')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Chat Penjual
                    </a>
                    <button class="btn btn-outline btn-lg btn-block wishlist-toggle <?= $isInWishlist ? 'active' : '' ?>"
                        onclick="toggleWishlist(<?= $product['productid'] ?>, this)">
                        <svg viewBox="0 0 24 24" fill="<?= $isInWishlist ? 'currentColor' : 'none' ?>" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                            </path>
                        </svg>
                        <?= $isInWishlist ? 'Hapus dari Wishlist' : 'Tambah ke Wishlist' ?>
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary btn-lg btn-block" onclick="showLoginModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Beli Sekarang
                    </button>
                    <button class="btn btn-outline btn-lg btn-block" onclick="showLoginModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Chat Penjual
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <?php if (!empty($relatedProducts)): ?>
        <section class="section related-section">
            <div class="section-header">
                <h2 class="section-title">Produk Lain dari Toko Ini</h2>
            </div>

            <div class="video-grid grid-4">
                <?php foreach ($relatedProducts as $related): ?>
                    <a href="/detail.php?id=<?= $related['productid'] ?>" class="video-card">
                        <div class="video-wrapper">
                            <img src="/assets/uploads/thumbnails/<?= $related['thumbnail'] ?>"
                                alt="<?= sanitize($related['productname']) ?>" class="video-thumbnail" loading="lazy">
                            <div class="video-overlay">
                                <span class="play-icon">▶</span>
                            </div>
                        </div>
                        <div class="video-info">
                            <h3 class="video-title"><?= sanitize(truncate($related['productname'], 40)) ?></h3>
                            <span class="video-price"><?= formatRupiah($related['price']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>

    function toggleMute() {
        const video = document.getElementById('mainVideo');
        const mutedIcon = document.querySelector('.muted-icon');
        const unmutedIcon = document.querySelector('.unmuted-icon');

        video.muted = !video.muted;
        mutedIcon.style.display = video.muted ? 'block' : 'none';
        unmutedIcon.style.display = video.muted ? 'none' : 'block';
    }

    function toggleFullscreen() {
        const video = document.getElementById('mainVideo');
        if (video.requestFullscreen) {
            video.requestFullscreen();
        } else if (video.webkitRequestFullscreen) {
            video.webkitRequestFullscreen();
        }
    }

    function showLoginModal() {
        document.getElementById('loginModal').classList.add('active');
    }

    function toggleWishlist(productId, btn) {
        const isActive = btn.classList.contains('active');
        const action = isActive ? 'remove' : 'add';

        fetch('/member/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&product_id=${productId}`
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('active');
                    const svg = btn.querySelector('svg');
                    const text = btn.childNodes[btn.childNodes.length - 1];

                    if (data.action === 'added') {
                        svg.setAttribute('fill', 'currentColor');
                        text.textContent = ' Hapus dari Wishlist';
                    } else {
                        svg.setAttribute('fill', 'none');
                        text.textContent = ' Tambah ke Wishlist';
                    }
                }
            });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>