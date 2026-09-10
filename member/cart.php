<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $productId = (int) ($_POST['product_id'] ?? 0);

            if ($productId) {

                $stmt = $db->prepare("SELECT stock FROM products WHERE productid = ? AND status = 'active'");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();

                if ($product && $product['stock'] > 0) {

                    $stmt = $db->prepare("SELECT cartid, quantity FROM carts WHERE userid = ? AND productid = ?");
                    $stmt->execute([$userId, $productId]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        if ($existing['quantity'] < $product['stock']) {
                            $stmt = $db->prepare("UPDATE carts SET quantity = quantity + 1 WHERE cartid = ?");
                            $stmt->execute([$existing['cartid']]);
                        }
                    } else {
                        $stmt = $db->prepare("INSERT INTO carts (userid, productid, quantity) VALUES (?, ?, 1)");
                        $stmt->execute([$userId, $productId]);
                    }

                    setFlash('success', 'Produk ditambahkan ke keranjang');
                } else {
                    setFlash('error', 'Produk tidak tersedia');
                }
            }
            break;

        case 'update':
            $cartId = (int) ($_POST['cart_id'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 1);

            if ($cartId && $quantity > 0) {
                $stmt = $db->prepare("UPDATE carts SET quantity = ? WHERE cartid = ? AND userid = ?");
                $stmt->execute([$quantity, $cartId, $userId]);
            }
            break;

        case 'remove':
            $cartId = (int) ($_POST['cart_id'] ?? 0);

            if ($cartId) {
                $stmt = $db->prepare("DELETE FROM carts WHERE cartid = ? AND userid = ?");
                $stmt->execute([$cartId, $userId]);
                setFlash('success', 'Produk dihapus dari keranjang');
            }
            break;

        case 'clear':
            $stmt = $db->prepare("DELETE FROM carts WHERE userid = ?");
            $stmt->execute([$userId]);
            setFlash('success', 'Keranjang dikosongkan');
            break;
    }

    header('Location: /member/cart.php');
    exit;
}


$stmt = $db->prepare("
    SELECT c.*, p.productname, p.price, p.stock, p.thumbnail, p.videofile,
           s.storename, s.city
    FROM carts c
    JOIN products p ON c.productid = p.productid
    JOIN stores s ON p.storeid = s.storeid
    WHERE c.userid = ?
    ORDER BY c.createdat DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();


$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$pageTitle = 'Keranjang';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="cart-page">
    <div class="cart-container">
        <h1>Keranjang Belanja</h1>

        <?php if (empty($cartItems)): ?>
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <h2>Keranjang Kosong</h2>
                <p>Belum ada produk di keranjang. Yuk mulai belanja!</p>
                <a href="/browse.php" class="btn btn-primary">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="item-media">
                                <a href="/detail.php?id=<?= $item['productid'] ?>">
                                    <img src="/assets/uploads/thumbnails/<?= $item['thumbnail'] ?>"
                                        alt="<?= sanitize($item['productname']) ?>">
                                </a>
                            </div>

                            <div class="item-details">
                                <a href="/detail.php?id=<?= $item['productid'] ?>" class="item-title">
                                    <?= sanitize($item['productname']) ?>
                                </a>
                                <div class="item-store">
                                    <span><?= sanitize($item['storename']) ?></span>
                                    <span class="separator">•</span>
                                    <span>📍 <?= sanitize($item['city']) ?></span>
                                </div>
                                <div class="item-price"><?= formatRupiah($item['price']) ?></div>
                            </div>

                            <div class="item-actions">
                                <form action="" method="POST" class="qty-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?= $item['cartid'] ?>">
                                    <div class="qty-control">
                                        <button type="button" class="qty-btn" onclick="updateQty(this, -1)">−</button>
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1"
                                            max="<?= $item['stock'] ?>" class="qty-input" onchange="this.form.submit()">
                                        <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                                    </div>
                                </form>

                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                    <button type="submit" class="btn-remove" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path
                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            <div class="item-subtotal">
                                <?= formatRupiah($item['price'] * $item['quantity']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Ringkasan Belanja</h3>

                        <div class="summary-row">
                            <span>Total (<?= count($cartItems) ?> produk)</span>
                            <span><?= formatRupiah($subtotal) ?></span>
                        </div>

                        <div class="summary-total">
                            <span>Total</span>
                            <span class="total-price"><?= formatRupiah($subtotal) ?></span>
                        </div>

                        <a href="/member/checkout.php" class="btn btn-primary btn-block btn-lg">
                            Checkout
                        </a>

                        <form action="" method="POST" class="clear-cart">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline btn-block"
                                onclick="return confirm('Yakin ingin mengosongkan keranjang?')">
                                Kosongkan Keranjang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateQty(btn, change) {
        const input = btn.parentElement.querySelector('.qty-input');
        let val = parseInt(input.value) + change;
        const min = parseInt(input.min);
        const max = parseInt(input.max);

        if (val >= min && val <= max) {
            input.value = val;
            btn.closest('form').submit();
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>