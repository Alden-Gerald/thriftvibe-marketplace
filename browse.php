<?php


require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();


$search = trim($_GET['q'] ?? '');
$kategori = $_GET['kategori'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$filter = $_GET['filter'] ?? '';
$minPrice = (int)($_GET['min_price'] ?? 0);
$maxPrice = (int)($_GET['max_price'] ?? 0);
$ukuran = $_GET['ukuran'] ?? '';
$brand = trim($_GET['brand'] ?? '');


$where = ["p.status = 'active'"];
$params = [];

if ($search) {
    $where[] = "(p.productname LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($kategori && in_array($kategori, ['Hoodie', 'Jaket', 'Kaos', 'Celana'])) {
    $where[] = "p.category = ?";
    $params[] = $kategori;
}

if ($filter === 'premium') {
    $where[] = "s.membershiplevel = 'premium' AND (s.expireddate IS NULL OR s.expireddate >= CURDATE())";
}

if ($minPrice > 0) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}


if ($ukuran && in_array($ukuran, ['XS', 'S', 'M', 'L', 'XL', 'XXL'])) {
    $where[] = "p.size = ?";
    $params[] = $ukuran;
}

if ($brand) {
    $where[] = "p.brand LIKE ?";
    $params[] = "%$brand%";
}

$whereClause = implode(' AND ', $where);


$orderBy = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    default => 'p.uploadedat DESC'
};


$sql = "
    SELECT p.*, s.storename, s.membershiplevel, s.city, s.expireddate
    FROM products p
    JOIN stores s ON p.storeid = s.storeid
    WHERE $whereClause
    ORDER BY $orderBy
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = $search ? "Hasil pencarian: $search" : 'Explore';
require_once __DIR__ . '/includes/header.php';
?>

<div class="browse-page">
    <div class="browse-container">
        
        <aside class="browse-sidebar">
            <form action="" method="GET" class="filter-form">
                <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                
                <div class="filter-section">
                    <h3>Kategori</h3>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="kategori" value="" <?= !$kategori ? 'checked' : '' ?>>
                            <span>Semua</span>
                        </label>
                        <?php foreach (['Hoodie', 'Jaket', 'Kaos', 'Celana'] as $cat): ?>
                            <label class="filter-option">
                                <input type="radio" name="kategori" value="<?= $cat ?>" <?= $kategori === $cat ? 'checked' : '' ?>>
                                <span><?= $cat ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Toko</h3>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="filter" value="" <?= !$filter ? 'checked' : '' ?>>
                            <span>Semua Toko</span>
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="filter" value="premium" <?= $filter === 'premium' ? 'checked' : '' ?>>
                            <span>⭐ Star Seller</span>
                        </label>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Harga</h3>
                    <div class="price-range">
                        <input type="number" name="min_price" placeholder="Min" 
                               value="<?= $minPrice ?: '' ?>" class="form-control">
                        <span>-</span>
                        <input type="number" name="max_price" placeholder="Max" 
                               value="<?= $maxPrice ?: '' ?>" class="form-control">
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Urutkan</h3>
                    <select name="sort" class="form-control">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                    </select>
                </div>
                
                <div class="filter-section">
                    <h3>Ukuran</h3>
                    <select name="ukuran" class="form-control">
                        <option value="">Semua Ukuran</option>
                        <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                            <option value="<?= $size ?>" <?= $ukuran === $size ? 'selected' : '' ?>><?= $size ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-section">
                    <h3>Brand</h3>
                    <input type="text" name="brand" placeholder="Cari brand..." 
                           value="<?= htmlspecialchars($brand) ?>" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Terapkan Filter</button>
            </form>
        </aside>
        
        
        <main class="browse-main">
            <div class="browse-header">
                <h1>
                    <?php if ($search): ?>
                        Hasil pencarian: "<?= sanitize($search) ?>"
                    <?php elseif ($kategori): ?>
                        Kategori: <?= sanitize($kategori) ?>
                    <?php elseif ($filter === 'premium'): ?>
                        ⭐ Star Sellers
                    <?php else: ?>
                        Explore Semua Produk
                    <?php endif; ?>
                </h1>
                <span class="result-count"><?= count($products) ?> produk ditemukan</span>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h2>Tidak ada produk ditemukan</h2>
                    <p>Coba ubah filter atau kata kunci pencarian</p>
                    <a href="/browse.php" class="btn btn-primary">Reset Filter</a>
                </div>
            <?php else: ?>
                <div class="video-grid grid-4">
                    <?php foreach ($products as $product): 
                        $isPremium = $product['membershiplevel'] === 'premium' && 
                            ($product['expireddate'] === null || $product['expireddate'] >= date('Y-m-d'));
                    ?>
                        <a href="/detail.php?id=<?= $product['productid'] ?>" class="video-card">
                            <div class="video-wrapper">
                                <img 
                                    src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>" 
                                    alt="<?= sanitize($product['productname']) ?>"
                                    class="video-thumbnail"
                                    loading="lazy"
                                >
                                <video 
                                    src="/assets/uploads/videos/<?= $product['videofile'] ?>" 
                                    muted
                                    loop
                                    playsinline
                                    class="video-player hidden"
                                    data-hover-play="true"
                                ></video>
                                <div class="video-overlay">
                                    <span class="play-icon">▶</span>
                                </div>
                                <?php if ($isPremium): ?>
                                    <div class="video-badge premium-badge">⭐</div>
                                <?php endif; ?>
                                <span class="video-time"><?= timeAgo($product['uploadedat']) ?></span>
                            </div>
                            <div class="video-info">
                                <div class="video-store">
                                    <span class="store-name"><?= sanitize($product['storename']) ?></span>
                                    <?php if ($isPremium): ?>
                                        <span class="verified-badge">✓</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="video-title"><?= sanitize(truncate($product['productname'], 40)) ?></h3>
                                <span class="video-price"><?= formatRupiah($product['price']) ?></span>
                                <span class="video-location">📍 <?= sanitize($product['city']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>

document.querySelectorAll('.filter-form input[type="radio"], .filter-form select').forEach(el => {
    el.addEventListener('change', function() {
        this.closest('form').submit();
    });
});


document.querySelectorAll('.filter-form input[type="number"], .filter-form input[type="text"]').forEach(input => {
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.closest('form').submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
