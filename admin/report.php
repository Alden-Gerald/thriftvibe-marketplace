<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reportId && in_array($action, ['pending', 'resolved', 'rejected'])) {
        $db->prepare("UPDATE reports SET status = ? WHERE reportid = ?")->execute([$action, $reportId]);


        if ($action === 'resolved' && isset($_POST['delete_item'])) {
            $stmt = $db->prepare("SELECT reportedid, reportedtype FROM reports WHERE reportid = ?");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch();
            if ($report && $report['reportedtype'] === 'product') {
                $db->prepare("UPDATE products SET status = 'inactive' WHERE productid = ?")->execute([$report['reportedid']]);
            }
        }

        setFlash('success', 'Status laporan diperbarui');
    }

    header('Location: /admin/report.php');
    exit;
}


$reports = $db->query("
    SELECT r.*, u.fullname as reportername, 
           p.productname, p.thumbnail, s.storename
    FROM reports r
    JOIN users u ON r.reporterid = u.userid
    LEFT JOIN products p ON r.reportedtype = 'product' AND r.reportedid = p.productid
    LEFT JOIN stores s ON p.storeid = s.storeid
    ORDER BY FIELD(r.status, 'pending', 'resolved', 'rejected'), r.createdat DESC
")->fetchAll();

$pendingCount = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));

$pageTitle = 'Laporan Konten';
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
                <a href="/admin/report.php" class="nav-item active">Laporan Konten</a>
                <a href="/admin/laporan.php" class="nav-item">Laporan Keuangan</a>
            </nav>
            <div class="sidebar-footer">
                <a href="/auth/logout.php" class="nav-item logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1>🚨 Laporan Konten <?php if ($pendingCount > 0): ?><span
                        class="nav-badge"><?= $pendingCount ?></span><?php endif; ?></h1>

            <?php $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if (empty($reports)): ?>
                <div class="section-card">
                    <div class="empty-state small">
                        <p>Tidak ada laporan</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="reports-list">
                    <?php foreach ($reports as $r): ?>
                        <div class="report-card status-<?= $r['status'] ?>">
                            <div class="report-product">
                                <?php if ($r['thumbnail']): ?>
                                    <img src="/assets/uploads/thumbnails/<?= $r['thumbnail'] ?>" alt="">
                                <?php else: ?>
                                    <div class="no-image">📦</div>
                                <?php endif; ?>
                                <div>
                                    <h3><?= sanitize($r['product_name'] ?? 'Produk tidak ditemukan') ?></h3>
                                    <span>Toko: <?= sanitize($r['store_name'] ?? '-') ?></span>
                                </div>
                                <?php if ($r['reported_id']): ?>
                                    <a href="/detail.php?id=<?= $r['reported_id'] ?>" target="_blank"
                                        class="btn btn-outline btn-sm">Lihat</a>
                                <?php endif; ?>
                            </div>
                            <div class="report-info">
                                <div class="report-reason">
                                    <strong>Alasan:</strong> <?= sanitize($r['reason']) ?>
                                </div>
                                <div class="report-meta">
                                    <span>Dilaporkan oleh: <?= sanitize($r['reporter_name']) ?></span>
                                    <span><?= timeAgo($r['created_at']) ?></span>
                                </div>
                            </div>
                            <div class="report-actions">
                                <span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form action="" method="POST">
                                        <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                        <input type="hidden" name="action" value="resolved">
                                        <label><input type="checkbox" name="delete_item" value="1"> Nonaktifkan produk</label>
                                        <button type="submit" class="btn btn-danger btn-sm">Selesaikan</button>
                                    </form>
                                    <form action="" method="POST">
                                        <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                        <input type="hidden" name="action" value="rejected">
                                        <button type="submit" class="btn btn-outline btn-sm">Tolak</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .reports-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .report-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .report-product {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .report-product img,
        .no-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .no-image {
            background: var(--color-surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .report-product h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .report-product span {
            color: var(--color-text-muted);
            font-size: 0.85rem;
        }

        .report-reason {
            margin-bottom: 0.5rem;
        }

        .report-meta {
            font-size: 0.85rem;
            color: var(--color-text-muted);
            display: flex;
            gap: 1rem;
        }

        .report-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .report-actions label {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        @media (max-width: 1024px) {
            .report-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>

</html>