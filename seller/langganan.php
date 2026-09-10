<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStore();

$db = getDB();
$userId = getUserId();
$store = getStore($userId);
$isPremium = isPremium($store['storeid']);

$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package = $_POST['package'] ?? '';
    $amount = $package === '1_month' ? 50000 : ($package === '3_months' ? 120000 : 0);

    if (!in_array($package, ['1_month', '3_months'])) {
        $errors['package'] = 'Pilih paket langganan';
    }


    $transferProof = null;
    if (isset($_FILES['transfer_proof']) && $_FILES['transfer_proof']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile(
            $_FILES['transfer_proof'],
            __DIR__ . '/../assets/uploads/bukti-bayar',
            ['image/jpeg', 'image/png'],
            5 * 1024 * 1024
        );
        if ($result['success']) {
            $transferProof = $result['filename'];
        } else {
            $errors['transfer_proof'] = $result['error'];
        }
    } else {
        $errors['transfer_proof'] = 'Upload bukti transfer';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO subscriptions (storeid, package, amount, transferproof) VALUES (?, ?, ?, ?)");
        $stmt->execute([$store['storeid'], $package, $amount, $transferProof]);

        setFlash('success', 'Pengajuan Star Seller berhasil! Menunggu verifikasi admin.');
        header('Location: /seller/langganan.php');
        exit;
    }
}


$stmt = $db->prepare("SELECT * FROM subscriptions WHERE storeid = ? ORDER BY createdat DESC");
$stmt->execute([$store['storeid']]);
$subscriptions = $stmt->fetchAll();

$pageTitle = 'Star Seller';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Star Seller ⭐</h1>
    </div>

    <?php $flash = getFlash();
    if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <?php if ($isPremium): ?>
        <div class="section-card premium-status">
            <div class="premium-badge-large">⭐</div>
            <h2>Anda Adalah Star Seller!</h2>
            <p>Berlaku sampai: <?= date('d M Y', strtotime($store['expireddate'])) ?></p>
        </div>
    <?php else: ?>
        <div class="section-card">
            <h2>Upgrade ke Star Seller</h2>
            <div class="benefits">
                <div class="benefit">✓ Produk tampil di Spotlight utama</div>
                <div class="benefit">✓ Badge centang biru verified</div>
                <div class="benefit">✓ Prioritas di hasil pencarian</div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="subscription-form">
                <div class="form-group">
                    <label>Pilih Paket</label>
                    <div class="package-options">
                        <label class="package-option">
                            <input type="radio" name="package" value="1_month" required>
                            <span class="package-card">
                                <span class="package-name">1 Bulan</span>
                                <span class="package-price">Rp 50.000</span>
                            </span>
                        </label>
                        <label class="package-option">
                            <input type="radio" name="package" value="3_months">
                            <span class="package-card recommended">
                                <span class="package-badge">Hemat 20%</span>
                                <span class="package-name">3 Bulan</span>
                                <span class="package-price">Rp 120.000</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="payment-info">
                    <h4>Transfer ke:</h4>
                    <p>Bank BCA - 1234567890<br>a.n ThriftVibe Market</p>
                </div>

                <div class="form-group">
                    <label>Upload Bukti Transfer</label>
                    <input type="file" name="transfer_proof" class="form-control" accept="image/*" required>
                    <?php if (isset($errors['transfer_proof'])): ?>
                        <span class="error-text"><?= $errors['transfer_proof'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Ajukan Star Seller</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (!empty($subscriptions)): ?>
        <div class="section-card">
            <h2>Riwayat Pengajuan</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Paket</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($sub['created_at'])) ?></td>
                            <td><?= $sub['package'] === '1_month' ? '1 Bulan' : '3 Bulan' ?></td>
                            <td><?= formatRupiah($sub['amount']) ?></td>
                            <td><span class="status-badge status-<?= $sub['status'] ?>"><?= ucfirst($sub['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</div>
<script src="/assets/js/main.js"></script>
</body>

</html>