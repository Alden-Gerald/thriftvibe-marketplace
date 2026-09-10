<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStore();

$db = getDB();
$userId = getUserId();
$store = getStore($userId);
$isPremium = isPremium($store['storeid']);


$stmtProducts = $db->prepare("SELECT COUNT(*) as total FROM products WHERE storeid = ? AND status = 'active'");
$stmtProducts->execute([$store['storeid']]);
$totalProducts = $stmtProducts->fetch()['total'];

$stmtPending = $db->prepare("
    SELECT COUNT(DISTINCT o.orderid) as total
    FROM orders o
    JOIN orderdetails d ON o.orderid = d.orderid
    JOIN products p ON d.productid = p.productid
    WHERE p.storeid = ? AND o.status IN ('pending', 'packed')
");
$stmtPending->execute([$store['storeid']]);
$pendingOrders = $stmtPending->fetch()['total'];


$stmtRecent = $db->prepare("
    SELECT o.*, c.fullname as buyername,
           (SELECT SUM(d.price * d.quantity) FROM orderdetails d 
            JOIN products p ON d.productid = p.productid 
            WHERE d.orderid = o.orderid AND p.storeid = ?) as storetotal
    FROM orders o
    JOIN users c ON o.userid = c.userid
    WHERE o.orderid IN (
        SELECT DISTINCT d.orderid 
        FROM orderdetails d
        JOIN products p ON d.productid = p.productid
        WHERE p.storeid = ?
    )
    ORDER BY o.createdat DESC
    LIMIT 5
");
$stmtRecent->execute([$store['storeid'], $store['storeid']]);
$recentOrders = $stmtRecent->fetchAll();

$pageTitle = 'Dashboard Toko';
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
                    <img src="/assets/img/logo.svg" alt="ThriftVibe" class="brand-logo">
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
                <a href="/seller/dashboard.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Dashboard
                </a>
                <a href="/seller/produk.php" class="nav-item">
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
                    <?php if ($pendingOrders > 0): ?>
                        <span class="nav-badge"><?= $pendingOrders ?></span>
                    <?php endif; ?>
                </a>
                <a href="/seller/langganan.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                        </polygon>
                    </svg>
                    Star Seller
                </a>
                <a href="/seller/pengaturan.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                        </path>
                    </svg>
                    Pengaturan
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
                <h1>Dashboard</h1>
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


            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $totalProducts ?></span>
                        <span class="stat-label">Produk Aktif</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $pendingOrders ?></span>
                        <span class="stat-label">Pesanan Menunggu</span>
                    </div>
                </div>

                <div class="stat-card <?= $isPremium ? 'premium' : '' ?>">
                    <div class="stat-icon"><?= $isPremium ? '⭐' : '📌' ?></div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $isPremium ? 'PREMIUM' : 'REGULAR' ?></span>
                        <span class="stat-label">Status Toko</span>
                    </div>
                    <?php if (!$isPremium): ?>
                        <a href="/seller/langganan.php" class="upgrade-btn">Upgrade</a>
                    <?php endif; ?>
                </div>
            </div>


            <div class="section-card">
                <div class="section-header">
                    <h2>Pesanan Terbaru</h2>
                    <a href="/seller/pesanan.php" class="view-all">Lihat Semua →</a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state small">
                        <p>Belum ada pesanan masuk</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pembeli</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= $order['orderid'] ?></td>
                                    <td><?= sanitize($order['buyername']) ?></td>
                                    <td><?= formatRupiah($order['storetotal']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($order['createdat'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
</body>

</html>
