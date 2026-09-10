<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStore();

$db = getDB();
$userId = getUserId();
$store = getStore($userId);
$isPremium = isPremium($store['storeid']);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $productId = (int) $_POST['product_id'];
    $stmt = $db->prepare("DELETE FROM products WHERE productid = ? AND storeid = ?");
    $stmt->execute([$productId, $store['storeid']]);
    setFlash('success', 'Produk berhasil dihapus');
    header('Location: /seller/produk.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $productId = (int) $_POST['product_id'];
    $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
    $stmt = $db->prepare("UPDATE products SET status = ? WHERE productid = ? AND storeid = ?");
    $stmt->execute([$newStatus, $productId, $store['storeid']]);
    setFlash('success', 'Status produk diperbarui');
    header('Location: /seller/produk.php');
    exit;
}


$stmt = $db->prepare("SELECT * FROM products WHERE storeid = ? ORDER BY uploadedat DESC");
$stmt->execute([$store['storeid']]);
$products = $stmt->fetchAll();

$pageTitle = 'Produk Saya';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - ThriftVibe Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="dashboard-page">
    <div class="dashboard-layout">

        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="/" class="sidebar-logo">
                    <span class="brand-icon">🔥</span>
                    <span class="brand-text">ThriftVibe</span>
                </a>
            </div>

            <div class="store-info">
                <div class="store-avatar">
                    <span><?= strtoupper(substr($store['storename'], 0, 1)) ?></span>
                </div>
                <div class="store-details">
                    <h3><?= sanitize($store['storename']) ?></h3>
                    <span class="store-badge <?= $isPremium ? 'premium' : 'regular' ?>">
                        <?= $isPremium ? '⭐ Star Seller' : 'Regular' ?>
                    </span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="/seller/dashboard.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Dashboard
                </a>
                <a href="/seller/produk.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                    </svg>
                    Produk Saya
                </a>
                <a href="/seller/tambah-produk.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah Produk
                </a>
                <a href="/seller/pesanan.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                    </svg>
                    Pesanan Masuk
                </a>
                <a href="/seller/langganan.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                        </polygon>
                    </svg>
                    Star Seller
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="/" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    </svg>
                    Kembali ke Store
                </a>
                <a href="/auth/logout.php" class="nav-item logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16,17 21,12 16,7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </aside>


        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1>Produk Saya</h1>
                <a href="/seller/tambah-produk.php" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah Produk
                </a>
            </div>

            <?php $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h2>Belum Ada Produk</h2>
                    <p>Mulai upload produk pertamamu sekarang!</p>
                    <a href="/seller/tambah-produk.php" class="btn btn-primary">Tambah Produk</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card <?= $product['status'] === 'inactive' ? 'inactive' : '' ?>">
                            <div class="product-media">
                                <img src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>"
                                    alt="<?= sanitize($product['productname']) ?>">
                                <span class="product-status status-<?= $product['status'] ?>">
                                    <?= ucfirst($product['status']) ?>
                                </span>
                            </div>
                            <div class="product-info">
                                <h3><?= sanitize(truncate($product['productname'], 30)) ?></h3>
                                <span class="product-category"><?= $product['category'] ?></span>
                                <span class="product-price"><?= formatRupiah($product['price']) ?></span>
                                <span class="product-stock">Stok: <?= $product['stock'] ?></span>
                            </div>
                            <div class="product-actions">
                                <a href="/detail.php?id=<?= $product['productid'] ?>" class="btn btn-outline btn-sm"
                                    target="_blank">
                                    Lihat
                                </a>
                                <a href="/seller/edit-produk.php?id=<?= $product['productid'] ?>"
                                    class="btn btn-outline btn-sm">
                                    Edit
                                </a>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['productid'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $product['status'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-outline btn-sm">
                                        <?= $product['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <form action="" method="POST" style="display:inline;"
                                    onsubmit="return confirm('Yakin hapus produk ini?')">
                                    <input type="hidden" name="product_id" value="<?= $product['productid'] ?>">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
</body>

</html>