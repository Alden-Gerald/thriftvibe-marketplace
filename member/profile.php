<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];

    if (empty($fullName)) {
        $errors[] = 'Nama lengkap wajib diisi';
    }

    if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Nomor HP tidak valid';
    }


    $newPhoto = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile(
            $_FILES['profile_photo'],
            __DIR__ . '/../assets/uploads/profile',
            ['image/jpeg', 'image/png', 'image/webp'],
            2 * 1024 * 1024
        );

        if ($result['success']) {
            $newPhoto = $result['filename'];
        } else {
            $errors[] = $result['error'];
        }
    }

    if (empty($errors)) {
        if ($newPhoto) {
            $stmt = $db->prepare("UPDATE users SET fullname = ?, phone = ?, profilephoto = ? WHERE userid = ?");
            $stmt->execute([$fullName, $phone ?: null, $newPhoto, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE users SET fullname = ?, phone = ? WHERE userid = ?");
            $stmt->execute([$fullName, $phone ?: null, $userId]);
        }


        $_SESSION['customer']['full_name'] = $fullName;
        $_SESSION['customer']['phone'] = $phone;
        if ($newPhoto)
            $_SESSION['customer']['profile_photo'] = $newPhoto;

        setFlash('success', 'Profil berhasil diperbarui');
        header('Location: /member/profile.php');
        exit;
    } else {
        setFlash('error', implode(', ', $errors));
    }
}


$stmt = $db->prepare("SELECT * FROM users WHERE userid = ?");
$stmt->execute([$userId]);
$customer = $stmt->fetch();


$store = getStore($userId);


$stmtOrders = $db->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM orderdetails WHERE orderid = o.orderid) as itemcount
    FROM orders o
    WHERE o.userid = ?
    ORDER BY o.createdat DESC
    LIMIT 5
");
$stmtOrders->execute([$userId]);
$orders = $stmtOrders->fetchAll();

$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .profile-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .profile-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }


    .profile-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .profile-card {
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        padding: 1.5rem;
        text-align: center;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid var(--color-primary);
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-card h2 {
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
        color: var(--color-text);
    }

    .profile-card p {
        color: var(--color-text-muted);
        font-size: 0.9rem;
        margin-bottom: 0.75rem;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 600;
    }

    .badge-success {
        background: rgba(76, 175, 80, 0.15);
        color: #4caf50;
    }


    .profile-nav {
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .profile-nav .nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem;
        color: var(--color-text);
        text-decoration: none;
        border-bottom: 1px solid var(--color-border);
        transition: all 0.2s;
    }

    .profile-nav .nav-item:last-child {
        border-bottom: none;
    }

    .profile-nav .nav-item:hover {
        background: var(--color-surface-2);
    }

    .profile-nav .nav-item.active {
        background: var(--color-primary);
        color: white;
    }

    .profile-nav .nav-item.highlight {
        background: var(--color-primary);
        color: white;
    }

    .profile-nav .nav-item svg {
        width: 18px;
        height: 18px;
    }


    .profile-main {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .section-card {
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        padding: 1.5rem;
    }

    .section-card h2 {
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
        color: var(--color-text);
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--color-border);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--color-border);
    }

    .section-header h2 {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .view-all {
        color: var(--color-primary);
        font-size: 0.9rem;
        text-decoration: none;
    }

    .view-all:hover {
        text-decoration: underline;
    }


    .profile-form .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    @media (max-width: 600px) {
        .profile-form .form-row {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--color-text);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--color-surface-2);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        color: var(--color-text);
        font-size: 0.95rem;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--color-primary);
    }

    .form-control:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .form-hint {
        display: block;
        margin-top: 0.35rem;
        font-size: 0.8rem;
        color: var(--color-text-muted);
    }

    /* Orders List */
    .orders-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .order-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem;
        background: var(--color-surface-2);
        border-radius: var(--radius-md);
    }

    .order-info {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .order-id {
        font-weight: 600;
        color: var(--color-primary);
    }

    .order-date {
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }

    .order-details {
        text-align: right;
    }

    .order-items {
        display: block;
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }

    .order-total {
        font-weight: 600;
        color: var(--color-text);
    }

    .order-status {
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .order-status.status-pending {
        background: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    .order-status.status-packed {
        background: rgba(33, 150, 243, 0.15);
        color: #2196f3;
    }

    .order-status.status-shipped {
        background: rgba(156, 39, 176, 0.15);
        color: #9c27b0;
    }

    .order-status.status-completed {
        background: rgba(76, 175, 80, 0.15);
        color: #4caf50;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
    }

    .empty-state.small {
        padding: 1.5rem;
    }

    .empty-state p {
        color: var(--color-text-muted);
        margin-bottom: 1rem;
    }
</style>

<div class="profile-page">
    <div class="profile-container">

        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="/assets/uploads/profile/<?= $customer['profile_photo'] ?>" alt="Profile">
                </div>
                <h2><?= sanitize($customer['full_name']) ?></h2>
                <p><?= sanitize($customer['email']) ?></p>
                <?php if ($store): ?>
                    <span class="badge badge-success">Penjual</span>
                <?php endif; ?>
            </div>

            <nav class="profile-nav">
                <a href="/member/profile.php" class="nav-item active">
                    Profil Saya
                </a>
                <a href="/member/orders.php" class="nav-item">
                    Pesanan Saya
                </a>
                <a href="/member/alamat.php" class="nav-item">
                    Alamat
                </a>
                <a href="/member/cart.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Keranjang
                </a>
                <?php if ($store): ?>
                    <a href="/seller/dashboard.php" class="nav-item">
                        Dashboard Toko
                    </a>
                <?php else: ?>
                    <a href="/member/buka-toko.php" class="nav-item highlight">
                        Buka Toko Gratis
                    </a>
                <?php endif; ?>
            </nav>
        </aside>


        <main class="profile-main">
            <div class="section-card">
                <h2>Edit Profil</h2>

                <form action="" method="POST" enctype="multipart/form-data" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Nama Lengkap</label>
                            <input type="text" id="full_name" name="full_name"
                                value="<?= htmlspecialchars($customer['full_name']) ?>" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" value="<?= htmlspecialchars($customer['email']) ?>" class="form-control"
                                disabled>
                            <small class="form-hint">Email tidak dapat diubah</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Nomor HP</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" class="form-control"
                                placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="form-group">
                            <label for="profile_photo">Foto Profil</label>
                            <input type="file" id="profile_photo" name="profile_photo" class="form-control"
                                accept="image/jpeg,image/png,image/webp">
                            <small class="form-hint">Max 2MB, JPG/PNG</small>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        💾 Simpan Perubahan
                    </button>
                </form>
            </div>


            <div class="section-card">
                <div class="section-header">
                    <h2>Pesanan Terbaru</h2>
                    <a href="/member/orders.php" class="view-all">Lihat Semua →</a>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-state small">
                        <p>Belum ada pesanan</p>
                        <a href="/browse.php" class="btn btn-outline">Mulai Belanja</a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <span class="order-id">#<?= $order['order_id'] ?></span>
                                    <span class="order-date"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                                </div>
                                <div class="order-details">
                                    <span class="order-items"><?= $order['item_count'] ?> item</span>
                                    <span class="order-total"><?= formatRupiah($order['total_amount']) ?></span>
                                </div>
                                <span class="order-status status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>