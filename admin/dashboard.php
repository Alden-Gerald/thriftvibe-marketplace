<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();


$totalusers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();
$totalStores = $db->query("SELECT COUNT(*) FROM stores")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingSubscriptions = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'pending'")->fetchColumn();


$recentOrders = $db->query("
    SELECT o.*, c.fullname 
    FROM orders o 
    JOIN users c ON o.userid = c.userid 
    ORDER BY o.createdat DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - ThriftVibe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body class="dashboard-page admin">
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar admin-sidebar">
            <div class="sidebar-header">
                <span class="brand-text">Admin Dashboard</span>
            </div>

            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item active">Dashboard</a>
                <a href="/admin/langganan.php" class="nav-item">
                    Langganan
                    <?php if ($pendingSubscriptions > 0): ?>
                        <span class="nav-badge"><?= $pendingSubscriptions ?></span>
                    <?php endif; ?>
                </a>
                <a href="/admin/konten.php" class="nav-item">Konten</a>
                <a href="/admin/laporan.php" class="nav-item">Laporan</a>
            </nav>

            <div class="sidebar-footer">
                <a href="/auth/logout.php" class="nav-item logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1>Dashboard Admin</h1>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $totalusers ?></span>
                        <span class="stat-label">Total Members</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏪</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $totalStores ?></span>
                        <span class="stat-label">Total Toko</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $totalProducts ?></span>
                        <span class="stat-label">Total Produk</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $totalOrders ?></span>
                        <span class="stat-label">Total Orders</span>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h2>Pesanan Terbaru</h2>
                </div>
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
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= sanitize($order['full_name']) ?></td>
                                <td><?= formatRupiah($order['total_amount']) ?></td>
                                <td><span
                                        class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>