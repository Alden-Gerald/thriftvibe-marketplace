<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriptionId = (int) ($_POST['subscription_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($subscriptionId) {

        $stmt = $db->prepare("SELECT * FROM subscriptions WHERE subscriptionid = ?");
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch();

        if ($subscription) {
            if ($action === 'approve') {

                $stmt = $db->prepare("UPDATE subscriptions SET status = 'active' WHERE subscriptionid = ?");
                $stmt->execute([$subscriptionId]);


                $days = $subscription['package'] === '1_month' ? 30 : 90;


                $stmt = $db->prepare("UPDATE stores SET membershiplevel = 'premium', expireddate = DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE storeid = ?");
                $stmt->execute([$days, $subscription['storeid']]);

                setFlash('success', 'Langganan berhasil diaktifkan!');
            } elseif ($action === 'reject') {
                $stmt = $db->prepare("UPDATE subscriptions SET status = 'rejected' WHERE subscriptionid = ?");
                $stmt->execute([$subscriptionId]);
                setFlash('success', 'Langganan ditolak.');
            }
        }
    }
    header('Location: /admin/langganan.php');
    exit;
}


$pendingSubscriptions = $db->query("
    SELECT s.*, st.storename, c.fullname, c.email
    FROM subscriptions s
    JOIN stores st ON s.storeid = st.storeid
    JOIN users c ON st.userid = c.userid
    WHERE s.status = 'pending'
    ORDER BY s.createdat DESC
")->fetchAll();


$allSubscriptions = $db->query("
    SELECT s.*, st.storename
    FROM subscriptions s
    JOIN stores st ON s.storeid = st.storeid
    ORDER BY s.createdat DESC
")->fetchAll();

$pageTitle = 'Validasi Langganan';
$pendingCount = count($pendingSubscriptions);
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
                <a href="/admin/langganan.php" class="nav-item active">Langganan</a>
                <a href="/admin/konten.php" class="nav-item">Konten</a>
                <a href="/admin/laporan.php" class="nav-item">Laporan</a>
            </nav>
            <div class="sidebar-footer">
                <a href="/auth/logout.php" class="nav-item logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <h1>Validasi Langganan Star Seller</h1>

            <?php $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if (!empty($pendingSubscriptions)): ?>
                <div class="section-card">
                    <h2>Menunggu Verifikasi (<?= $pendingCount ?>)</h2>

                    <?php foreach ($pendingSubscriptions as $sub): ?>
                        <div class="subscription-card pending">
                            <div class="sub-header">
                                <div class="sub-info">
                                    <h3><?= sanitize($sub['store_name']) ?></h3>
                                    <p><?= sanitize($sub['full_name']) ?> (<?= sanitize($sub['email']) ?>)</p>
                                </div>
                                <span class="sub-date"><?= date('d M Y, H:i', strtotime($sub['created_at'])) ?></span>
                            </div>
                            <div class="sub-details">
                                <span>Paket: <?= $sub['package'] === '1_month' ? '1 Bulan' : '3 Bulan' ?></span>
                                <span>Nominal: <?= formatRupiah($sub['amount']) ?></span>
                            </div>
                            <div class="sub-proof">
                                <a href="/assets/uploads/bukti-bayar/<?= $sub['transfer_proof'] ?>" target="_blank">
                                    <img src="/assets/uploads/bukti-bayar/<?= $sub['transfer_proof'] ?>" alt="Bukti Transfer">
                                </a>
                            </div>
                            <div class="sub-actions">
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="subscription_id" value="<?= $sub['subscription_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-primary">✓ ACC</button>
                                </form>
                                <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Yakin tolak?')">
                                    <input type="hidden" name="subscription_id" value="<?= $sub['subscription_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger">✕ Tolak</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="empty-state small">
                        <p>Tidak ada pengajuan menunggu verifikasi</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="section-card">
                <h2>Semua Riwayat Langganan</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Toko</th>
                            <th>Paket</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allSubscriptions as $sub): ?>
                            <tr>
                                <td><?= sanitize($sub['store_name']) ?></td>
                                <td><?= $sub['package'] === '1_month' ? '1 Bulan' : '3 Bulan' ?></td>
                                <td><?= formatRupiah($sub['amount']) ?></td>
                                <td><span
                                        class="status-badge status-<?= $sub['status'] ?>"><?= ucfirst($sub['status']) ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($sub['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>