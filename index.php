<?php


$pageTitle = 'Home';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$db = getDB();


$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$totalStores = $db->query("SELECT COUNT(*) FROM stores")->fetchColumn();
$totalStarSellers = $db->query("SELECT COUNT(*) FROM stores WHERE membershiplevel = 'premium' AND (expireddate IS NULL OR expireddate >= CURDATE())")->fetchColumn();


$stmtPremium = $db->query("
    SELECT p.*, s.storename, s.membershiplevel, s.city
    FROM products p
    JOIN stores s ON p.storeid = s.storeid
    WHERE p.status = 'active' 
    AND s.membershiplevel = 'premium'
    AND (s.expireddate IS NULL OR s.expireddate >= CURDATE())
    ORDER BY p.uploadedat DESC
    LIMIT 6
");
$premiumProducts = $stmtPremium->fetchAll();


$stmtNew = $db->query("
    SELECT p.*, s.storename, s.membershiplevel, s.city
    FROM products p
    JOIN stores s ON p.storeid = s.storeid
    WHERE p.status = 'active'
    ORDER BY p.uploadedat DESC
    LIMIT 12
");
$newProducts = $stmtNew->fetchAll();
?>


<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="gradient-text">ThriftVibe</span>
        </h1>
        <p class="hero-subtitle">Platform membeli barang thrift yang terpercaya</p>
        <div class="hero-actions">
            <a href="/browse.php" class="btn btn-primary btn-lg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Mulai Explore
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="/auth/register.php" class="btn btn-outline btn-lg">
                    Daftar Gratis
                </a>
            <?php elseif (!hasStore(getUserId())): ?>
                <a href="/member/buka-toko.php" class="btn btn-outline btn-lg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                    </svg>
                    Buka Toko Gratis
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-stats">
        <div class="stat-item">
            <span class="stat-icon">📦</span>
            <span class="stat-value"><?= $totalProducts ?></span>
            <span class="stat-label">Produk</span>
        </div>
        <div class="stat-item">
            <span class="stat-icon">🏪</span>
            <span class="stat-value"><?= $totalStores ?></span>
            <span class="stat-label">Toko</span>
        </div>
        <div class="stat-item">
            <span class="stat-icon">⭐</span>
            <span class="stat-value"><?= $totalStarSellers ?></span>
            <span class="stat-label">Star Seller</span>
        </div>
    </div>
</section>


<?php if (!empty($premiumProducts)): ?>
    <section class="section premium-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="fire-icon">🔥</span>
                Star Sellers
                <span class="badge badge-premium">Rekomendasi</span>
            </h2>
            <a href="/browse.php?filter=premium" class="view-all">Lihat Semua →</a>
        </div>

        <div class="video-scroll">
            <?php foreach ($premiumProducts as $product): ?>
                <a href="/detail.php?id=<?= $product['productid'] ?>" class="video-card video-card-large">
                    <div class="video-wrapper">
                        <video src="/assets/uploads/videos/<?= $product['videofile'] ?>"
                            poster="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>" muted loop playsinline
                            class="video-player" data-autoplay-hover="true"></video>
                        <div class="video-overlay">
                            <span class="play-icon">▶</span>
                        </div>
                        <div class="video-badge premium-badge">⭐ Star Seller</div>
                    </div>
                    <div class="video-info">
                        <div class="video-store">
                            <span class="store-name"><?= sanitize($product['storename']) ?></span>
                            <span class="verified-badge">✓</span>
                            <span class="store-location"><?= sanitize($product['city']) ?></span>
                        </div>
                        <h3 class="video-title"><?= sanitize($product['productname']) ?></h3>
                        <span class="video-price"><?= formatRupiah($product['price']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>


<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="sparkle-icon">✨</span>
            Baru Diupload
        </h2>
        <a href="/browse.php?sort=newest" class="view-all">Lihat Semua →</a>
    </div>

    <div class="video-grid grid-4">
        <?php foreach ($newProducts as $product): ?>
            <a href="/detail.php?id=<?= $product['productid'] ?>" class="video-card">
                <div class="video-wrapper">
                    <img src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>"
                        alt="<?= sanitize($product['productname']) ?>" class="video-thumbnail" loading="lazy">
                    <video src="/assets/uploads/videos/<?= $product['videofile'] ?>" muted loop playsinline
                        class="video-player hidden" data-hover-play="true"></video>
                    <div class="video-overlay">
                        <span class="play-icon">▶</span>
                    </div>
                    <?php if ($product['membershiplevel'] === 'premium'): ?>
                        <div class="video-badge premium-badge">⭐</div>
                    <?php endif; ?>
                    <span class="video-time"><?= timeAgo($product['uploadedat']) ?></span>
                </div>
                <div class="video-info">
                    <h3 class="video-title"><?= sanitize(truncate($product['productname'], 40)) ?></h3>
                    <span class="video-price"><?= formatRupiah($product['price']) ?></span>
                    <span class="video-store-name"><?= sanitize($product['storename']) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>


<section class="section categories-section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="category-icon">🏷️</span>
            Kategori
        </h2>
    </div>

    <div class="category-grid">
        <a href="/browse.php?kategori=Hoodie" class="category-card">
            <span class="category-emoji">🧥</span>
            <span class="category-name">Hoodie</span>
        </a>
        <a href="/browse.php?kategori=Jaket" class="category-card">
            <span class="category-emoji">🧥</span>
            <span class="category-name">Jaket</span>
        </a>
        <a href="/browse.php?kategori=Kaos" class="category-card">
            <span class="category-emoji">👕</span>
            <span class="category-name">Kaos</span>
        </a>
        <a href="/browse.php?kategori=Celana" class="category-card">
            <span class="category-emoji">👖</span>
            <span class="category-name">Celana</span>
        </a>
    </div>
</section>


<section class="section cta-section">
    <div class="cta-card">
        <div class="cta-content">
            <h2>Punya Koleksi Thrift?</h2>
            <p>Buka toko gratis dan mulai jual lewat video pendek. Jangkau ribuan pembeli yang mencari barang unik!
            </p>
            <?php if (!isLoggedIn()): ?>
                <a href="/auth/register.php" class="btn btn-primary btn-lg">Mulai Jualan</a>
            <?php elseif (!hasStore(getUserId())): ?>
                <a href="/member/buka-toko.php" class="btn btn-primary btn-lg">Buka Toko Gratis</a>
            <?php else: ?>
                <a href="/seller/dashboard.php" class="btn btn-primary btn-lg">Kelola Toko</a>
            <?php endif; ?>
        </div>
        <div class="cta-visual">
            <div class="cta-icon">🏪</div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>