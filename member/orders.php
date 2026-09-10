<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();


$stmt = $db->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.userid = ?
    ORDER BY o.createdat DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();


foreach ($orders as &$order) {
    $stmtDetails = $db->prepare("
        SELECT d.*, p.productname, p.thumbnail, s.storename
        FROM orderdetails d
        JOIN products p ON d.productid = p.productid
        JOIN stores s ON p.storeid = s.storeid
        WHERE d.orderid = ?
    ");
    $stmtDetails->execute([$order['orderid']]);
    $order['items'] = $stmtDetails->fetchAll();
}
unset($order);

$pageTitle = 'Pesanan Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .orders-page {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .orders-page h1 {
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        color: var(--color-text);
    }

    .orders-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .order-card {
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        overflow: hidden;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        background: var(--color-surface-2);
        border-bottom: 1px solid var(--color-border);
    }

    .order-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .order-id {
        font-weight: 700;
        color: var(--color-primary);
    }

    .order-date {
        color: var(--color-text-muted);
        font-size: 0.9rem;
    }

    .order-status {
        padding: 0.35rem 0.75rem;
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 600;
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

    .order-items {
        padding: 1rem 1.25rem;
    }

    .order-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--color-border);
        color: var(--color-text);
        text-decoration: none;
        transition: background 0.2s;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .order-item:hover {
        opacity: 0.8;
    }

    .order-item img {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-md);
        object-fit: cover;
        background: var(--color-surface-2);
    }

    .order-item .item-info {
        flex: 1;
    }

    .order-item .item-info h4 {
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
        color: var(--color-text);
    }

    .order-item .item-store {
        display: block;
        font-size: 0.85rem;
        color: var(--color-text-muted);
        margin-bottom: 0.25rem;
    }

    .order-item .item-qty {
        font-size: 0.9rem;
        color: var(--color-primary);
        font-weight: 600;
    }

    .order-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        background: var(--color-surface-2);
        border-top: 1px solid var(--color-border);
    }

    .order-shipping {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .order-shipping .courier {
        background: var(--color-primary);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 600;
    }

    .order-shipping .resi {
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }

    .order-total {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .order-total span:first-child {
        color: var(--color-text-muted);
        font-size: 0.9rem;
    }

    .order-total .total {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--color-primary);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
    }

    .empty-state .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .empty-state h2 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--color-text);
    }

    .empty-state p {
        color: var(--color-text-muted);
        margin-bottom: 1.5rem;
    }

    @media (max-width: 600px) {
        .order-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .order-footer {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
    }
</style>

<div class="orders-page">
    <h1>Pesanan Saya</h1>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h2>Belum Ada Pesanan</h2>
            <p>Anda belum melakukan pemesanan apapun.</p>
            <a href="/browse.php" class="btn btn-primary">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-meta">
                            <span class="order-id">#<?= $order['order_id'] ?></span>
                            <span class="order-date"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <span class="order-status status-<?= $order['status'] ?>">
                            <?php
                            $statusLabels = [
                                'pending' => 'Menunggu Konfirmasi',
                                'packed' => 'Sedang Dikemas',
                                'shipped' => 'Dalam Pengiriman',
                                'completed' => 'Selesai'
                            ];
                            echo $statusLabels[$order['status']] ?? ucfirst($order['status']);
                            ?>
                        </span>
                    </div>

                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <a href="/detail.php?id=<?= $item['product_id'] ?>" class="order-item">
                                <img src="/assets/uploads/thumbnails/<?= $item['thumbnail'] ?>"
                                    alt="<?= sanitize($item['product_name']) ?>">
                                <div class="item-info">
                                    <h4><?= sanitize($item['product_name']) ?></h4>
                                    <span class="item-store"><?= sanitize($item['store_name']) ?></span>
                                    <span class="item-qty"><?= $item['quantity'] ?> x <?= formatRupiah($item['price']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <div class="order-shipping">
                            <span class="courier"><?= sanitize($order['courier']) ?></span>
                            <?php if ($order['tracking_number']): ?>
                                <span class="resi">Resi: <?= sanitize($order['tracking_number']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="order-total">
                            <span>Total:</span>
                            <span class="total"><?= formatRupiah($order['total_amount']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>