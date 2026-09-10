<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();


$stmt = $db->prepare("
    SELECT c.*, p.productname, p.price, p.stock, p.thumbnail, p.storeid,
           s.storename, s.city
    FROM carts c
    JOIN products p ON c.productid = p.productid
    JOIN stores s ON p.storeid = s.storeid
    WHERE c.userid = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    setFlash('error', 'Keranjang kosong');
    header('Location: /member/cart.php');
    exit;
}


$stmtAddr = $db->prepare("SELECT * FROM addresses WHERE userid = ? ORDER BY isdefault DESC, createdat DESC");
$stmtAddr->execute([$userId]);
$savedAddresses = $stmtAddr->fetchAll();


$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressId = (int)($_POST['address_id'] ?? 0);
    $courier = $_POST['courier'] ?? '';
    
    
    $shippingAddress = '';
    if ($addressId > 0) {
        $stmtGetAddr = $db->prepare("SELECT * FROM addresses WHERE addressid = ? AND userid = ?");
        $stmtGetAddr->execute([$addressId, $userId]);
        $selectedAddr = $stmtGetAddr->fetch();
        if ($selectedAddr) {
            $shippingAddress = $selectedAddr['recipientname'] . "\n" . 
                      $selectedAddr['phone'] . "\n" . 
                      $selectedAddr['fulladdress'] . ", " . 
                      $selectedAddr['city'] . 
                      ($selectedAddr['postalcode'] ? ' ' . $selectedAddr['postalcode'] : '');
        }
    }
    
    
    if (empty($shippingAddress)) {
        $errors['alamat'] = 'Pilih alamat pengiriman';
    }
    
    if (!in_array($courier, ['JNE', 'J&T', 'SiCepat'])) {
        $errors['courier'] = 'Pilih kurir pengiriman';
    }
    
    
    $paymentProof = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile(
            $_FILES['payment_proof'],
            __DIR__ . '/../assets/uploads/bukti-bayar',
            ['image/jpeg', 'image/png'],
            5 * 1024 * 1024
        );
        
        if ($result['success']) {
            $paymentProof = $result['filename'];
        } else {
            $errors['payment_proof'] = $result['error'];
        }
    } else {
        $errors['payment_proof'] = 'Upload bukti pembayaran';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            
            $stmt = $db->prepare("
                INSERT INTO orders (userid, totalamount, shippingaddress, courier, paymentproof, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $subtotal, $shippingAddress, $courier, $paymentProof]);
            $orderId = $db->lastInsertId();
            
            
            $stmtDetail = $db->prepare("
                INSERT INTO orderdetails (orderid, productid, price, quantity)
                VALUES (?, ?, ?, ?)
            ");
            
            
            $stmtStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE productid = ?");
            
            foreach ($cartItems as $item) {
                $stmtDetail->execute([
                    $orderId,
                    $item['product_id'],
                    $item['price'],
                    $item['quantity']
                ]);
                
                $stmtStock->execute([$item['quantity'], $item['product_id']]);
            }
            
            
            $stmt = $db->prepare("DELETE FROM carts WHERE userid = ?");
            $stmt->execute([$userId]);
            
            $db->commit();
            
            setFlash('success', 'Pesanan berhasil dibuat! Menunggu konfirmasi penjual.');
            header('Location: /member/orders.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors['general'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="checkout-page">
    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= $errors['general'] ?></div>
        <?php endif; ?>
        
        <form action="" method="POST" enctype="multipart/form-data" class="checkout-form">
            <div class="checkout-content">
                
                <div class="checkout-items">
                    <div class="section-card">
                        <h2>Ringkasan Pesanan</h2>
                        
                        <div class="items-list">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="checkout-item">
                                    <img src="/assets/uploads/thumbnails/<?= $item['thumbnail'] ?>" 
                                         alt="<?= sanitize($item['product_name']) ?>">
                                    <div class="item-info">
                                        <h4><?= sanitize($item['product_name']) ?></h4>
                                        <span class="item-store"><?= sanitize($item['store_name']) ?></span>
                                        <span class="item-qty"><?= $item['quantity'] ?>x <?= formatRupiah($item['price']) ?></span>
                                    </div>
                                    <span class="item-total"><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    
                    <div class="section-card">
                        <h2>📍 Alamat Pengiriman</h2>
                        
                        <?php if (empty($savedAddresses)): ?>
                            <div class="empty-state small">
                                <p>Belum ada alamat tersimpan</p>
                                <a href="/member/alamat.php" class="btn btn-primary">+ Tambah Alamat</a>
                            </div>
                        <?php else: ?>
                            <div class="address-selector">
                                <?php foreach ($savedAddresses as $addr): ?>
                                    <label class="address-option">
                                        <input type="radio" name="address_id" value="<?= $addr['address_id'] ?>" 
                                               <?= $addr['is_default'] ? 'checked' : '' ?> required>
                                        <div class="address-option-card">
                                            <div class="option-header">
                                                <span class="option-label"><?= sanitize($addr['label']) ?></span>
                                                <?php if ($addr['is_default']): ?>
                                                    <span class="badge badge-primary">Utama</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="option-name"><?= sanitize($addr['recipient_name']) ?> • <?= sanitize($addr['phone']) ?></p>
                                            <p class="option-detail">
                                                <?= sanitize($addr['full_address']) ?>, <?= sanitize($addr['city']) ?>
                                                <?= $addr['postal_code'] ? ' ' . sanitize($addr['postal_code']) : '' ?>
                                            </p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                                
                                <a href="/member/alamat.php" class="add-address-link">
                                    + Tambah Alamat Baru
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($errors['alamat'])): ?>
                            <span class="error-text"><?= $errors['alamat'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    
                    <div class="section-card">
                        <h2>Pilih Kurir</h2>
                        
                        <div class="courier-options">
                            <label class="courier-option">
                                <input type="radio" name="courier" value="JNE" 
                                       <?= ($_POST['courier'] ?? '') === 'JNE' ? 'checked' : '' ?> required>
                                <span class="courier-card">
                                    <span class="courier-name">JNE</span>
                                    <span class="courier-desc">Regular 2-3 hari</span>
                                </span>
                            </label>
                            <label class="courier-option">
                                <input type="radio" name="courier" value="J&T"
                                       <?= ($_POST['courier'] ?? '') === 'J&T' ? 'checked' : '' ?>>
                                <span class="courier-card">
                                    <span class="courier-name">J&T</span>
                                    <span class="courier-desc">Express 1-2 hari</span>
                                </span>
                            </label>
                            <label class="courier-option">
                                <input type="radio" name="courier" value="SiCepat"
                                       <?= ($_POST['courier'] ?? '') === 'SiCepat' ? 'checked' : '' ?>>
                                <span class="courier-card">
                                    <span class="courier-name">SiCepat</span>
                                    <span class="courier-desc">Regular 2-4 hari</span>
                                </span>
                            </label>
                        </div>
                        <?php if (isset($errors['courier'])): ?>
                            <span class="error-text"><?= $errors['courier'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                
                <div class="checkout-payment">
                    <div class="section-card sticky">
                        <h2>Pembayaran</h2>
                        
                        <div class="payment-info">
                            <div class="bank-info">
                                <h4>Transfer ke:</h4>
                                <p class="bank-name">Bank BCA</p>
                                <p class="bank-number">1234567890</p>
                                <p class="bank-holder">a.n ThriftVibe Market</p>
                            </div>
                            
                            <div class="payment-amount">
                                <span>Total Pembayaran:</span>
                                <span class="amount"><?= formatRupiah($subtotal) ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_proof">Upload Bukti Pembayaran</label>
                            <input type="file" id="payment_proof" name="payment_proof" 
                                   class="form-control <?= isset($errors['payment_proof']) ? 'error' : '' ?>"
                                   accept="image/jpeg,image/png" required>
                            <small class="form-hint">Max 5MB, JPG/PNG</small>
                            <?php if (isset($errors['payment_proof'])): ?>
                                <span class="error-text"><?= $errors['payment_proof'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            Bayar Sekarang
                        </button>
                        
                        <a href="/member/cart.php" class="btn btn-outline btn-block">
                            Kembali ke Keranjang
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
