<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStore();

$db = getDB();
$userId = getUserId();
$store = getStore($userId);
$isPremium = isPremium($store['storeid']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($orderId) {
        if ($action === 'confirm' && isset($_POST['tracking_number'])) {
            $trackingNumber = trim($_POST['tracking_number']);
            if (!empty($trackingNumber)) {
                $stmt = $db->prepare("UPDATE orders SET status = 'shipped', trackingnumber = ? WHERE orderid = ?");
                $stmt->execute([$trackingNumber, $orderId]);
                setFlash('success', 'Pesanan dikonfirmasi!');
            }
        } elseif ($action === 'process') {
            $stmt = $db->prepare("UPDATE orders SET status = 'packed' WHERE orderid = ?");
            $stmt->execute([$orderId]);
            setFlash('success', 'Pesanan sedang diproses!');
        }
    }
    header('Location: /seller/pesanan.php');
    exit;
}


$stmt = $db->prepare("
    SELECT DISTINCT o.*, c.fullname as buyername
    FROM orders o
    JOIN users c ON o.userid = c.userid
    JOIN orderdetails d ON o.orderid = d.orderid
    JOIN products p ON d.productid = p.productid
    WHERE p.storeid = ?
    ORDER BY o.createdat DESC
");
$stmt->execute([$store['storeid']]);
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $stmtItems = $db->prepare("
        SELECT d.*, p.productname, p.thumbnail
        FROM orderdetails d
        JOIN products p ON d.productid = p.productid
        WHERE d.orderid = ? AND p.storeid = ?
    ");
    $stmtItems->execute([$order['orderid'], $store['storeid']]);
    $order['items'] = $stmtItems->fetchAll();
    $order['storetotal'] = array_reduce($order['items'], fn($s, $i) => $s + ($i['price'] * $i['quantity']), 0);
}
unset($order);

$pageTitle = 'Pesanan Masuk';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Pesanan Masuk</h1>
    </div>

    <?php $flash = getFlash();
    if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h2>Belum Ada Pesanan</h2>
        </div>
    <?php else: ?>
        <div class="orders-list seller-orders">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <span class="order-id">#<?= $order['order_id'] ?></span>
                        <span class="order-status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                    <div class="order-buyer"><strong>Pembeli:</strong> <?= sanitize($order['buyer_name']) ?></div>
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <img src="/assets/uploads/thumbnails/<?= $item['thumbnail'] ?>" alt="">
                                <div class="item-info">
                                    <h4><?= sanitize($item['product_name']) ?></h4>
                                    <span><?= $item['quantity'] ?> x <?= formatRupiah($item['price']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-shipping">
                        <p><strong>Alamat:</strong> <?= sanitize(truncate($order['shipping_address'], 100)) ?></p>
                        <p><strong>Kurir:</strong> <?= $order['courier'] ?></p>
                        <?php if ($order['payment_proof']): ?>
                            <a href="/assets/uploads/bukti-bayar/<?= $order['payment_proof'] ?>" target="_blank">Lihat Bukti
                                Bayar</a>
                        <?php endif; ?>
                    </div>
                    <div class="order-footer">
                        <span class="total"><?= formatRupiah($order['store_total']) ?></span>
                        <?php if ($order['status'] === 'pending'): ?>
                            <form action="" method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="action" value="process">
                                <button type="submit" class="btn btn-primary">Proses</button>
                            </form>
                        <?php elseif ($order['status'] === 'packed'): ?>
                            <form action="" method="POST" class="resi-form">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="action" value="confirm">
                                <input type="text" name="tracking_number" placeholder="No. Resi" class="form-control" required>
                                <button type="submit" class="btn btn-primary">Kirim</button>
                            </form>
                        <?php elseif ($order['tracking_number']): ?>
                            <span>Resi: <?= $order['tracking_number'] ?></span>
                        <?php endif; ?>
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