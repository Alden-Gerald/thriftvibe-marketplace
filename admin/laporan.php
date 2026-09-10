<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();


$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');


$stmt = $db->prepare("
    SELECT SUM(amount) as total, COUNT(*) as count
    FROM subscriptions 
    WHERE status = 'active' 
    AND DATE(createdat) BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$subscriptionRevenue = $stmt->fetch();


$stmt = $db->prepare("
    SELECT DATE_FORMAT(createdat, '%Y-%m') as month, 
           SUM(amount) as total, 
           COUNT(*) as count
    FROM subscriptions 
    WHERE status = 'active'
    AND DATE(createdat) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(createdat, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute([$startDate, $endDate]);
$monthlyData = $stmt->fetchAll();


$stmt = $db->prepare("
    SELECT s.*, st.storename
    FROM subscriptions s
    JOIN stores st ON s.storeid = st.storeid
    WHERE s.status = 'active'
    AND DATE(s.createdat) BETWEEN ? AND ?
    ORDER BY s.createdat DESC
");
$stmt->execute([$startDate, $endDate]);
$details = $stmt->fetchAll();

$pageTitle = 'Laporan Pendapatan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin ThriftVibe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        @media print {

            .dashboard-sidebar,
            .no-print {
                display: none !important;
            }

            .dashboard-main {
                margin: 0 !important;
                padding: 20px !important;
            }
        }
    </style>
</head>

<body class="dashboard-page admin">
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar admin-sidebar">
            <div class="sidebar-header">
                <span class="brand-text">Admin Dashboard</span>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="nav-item">Dashboard</a>
                <a href="/admin/langganan.php" class="nav-item">Langganan</a>
                <a href="/admin/konten.php" class="nav-item">Konten</a>
                <a href="/admin/laporan.php" class="nav-item active">Laporan</a>
            </nav>
            <div class="sidebar-footer">
                <a href="/auth/logout.php" class="nav-item logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header no-print">
                <h1>Laporan Pendapatan</h1>
                <button onclick="window.print()" class="btn btn-outline">🖨️ Cetak</button>
            </div>

            <form action="" method="GET" class="filter-form-inline no-print">
                <div class="form-group">
                    <label>Dari:</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Sampai:</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= formatRupiah($subscriptionRevenue['total'] ?? 0) ?></span>
                        <span class="stat-label">Total Pendapatan Langganan</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <span class="stat-value"><?= $subscriptionRevenue['count'] ?? 0 ?></span>
                        <span class="stat-label">Jumlah Transaksi</span>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h2>Ringkasan Bulanan</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Jumlah Transaksi</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyData as $row): ?>
                            <tr>
                                <td><?= date('F Y', strtotime($row['month'] . '-01')) ?></td>
                                <td><?= $row['count'] ?></td>
                                <td><?= formatRupiah($row['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="section-card">
                <h2>Detail Transaksi</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Toko</th>
                            <th>Paket</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                                <td><?= sanitize($d['store_name']) ?></td>
                                <td><?= $d['package'] === '1_month' ? '1 Bulan' : '3 Bulan' ?></td>
                                <td><?= formatRupiah($d['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>