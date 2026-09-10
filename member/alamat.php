<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$db = getDB();
$userId = getUserId();

$errors = [];
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
        case 'edit':
            $id = (int)($_POST['address_id'] ?? 0);
            $label = trim($_POST['label'] ?? '');
            $recipientName = trim($_POST['recipient_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $fullAddress = trim($_POST['full_address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postalCode = trim($_POST['postal_code'] ?? '');
            $isDefault = isset($_POST['is_default']) ? 1 : 0;
            
            
            if (empty($label)) $errors[] = 'Label alamat wajib diisi';
            if (empty($recipientName)) $errors[] = 'Nama penerima wajib diisi';
            if (empty($phone)) $errors[] = 'Nomor HP wajib diisi';
            if (empty($fullAddress)) $errors[] = 'Alamat lengkap wajib diisi';
            if (empty($city)) $errors[] = 'Kota wajib diisi';
            
            if (empty($errors)) {
                
                if ($isDefault) {
                    $stmt = $db->prepare("UPDATE addresses SET isdefault = 0 WHERE userid = ?");
                    $stmt->execute([$userId]);
                }
                
                if ($action === 'add') {
                    $stmt = $db->prepare("
                        INSERT INTO addresses (userid, label, recipientname, phone, fulladdress, city, postalcode, isdefault)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $label, $recipientName, $phone, $fullAddress, $city, $postalCode ?: null, $isDefault]);
                    $success = 'Alamat berhasil ditambahkan';
                } else {
                    $stmt = $db->prepare("
                        UPDATE addresses SET label = ?, recipientname = ?, phone = ?, fulladdress = ?, city = ?, postalcode = ?, isdefault = ?
                        WHERE addressid = ? AND userid = ?
                    ");
                    $stmt->execute([$label, $recipientName, $phone, $fullAddress, $city, $postalCode ?: null, $isDefault, $id, $userId]);
                    $success = 'Alamat berhasil diperbarui';
                }
                
                header('Location: /member/alamat.php');
                exit;
            }
            break;
            
        case 'delete':
            $id = (int)($_POST['address_id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM addresses WHERE addressid = ? AND userid = ?");
            $stmt->execute([$id, $userId]);
            $success = 'Alamat berhasil dihapus';
            header('Location: /member/alamat.php');
            exit;
            
        case 'set_default':
            $id = (int)($_POST['address_id'] ?? 0);
            $stmt = $db->prepare("UPDATE addresses SET isdefault = 0 WHERE userid = ?");
            $stmt->execute([$userId]);
            $stmt = $db->prepare("UPDATE addresses SET isdefault = 1 WHERE addressid = ? AND userid = ?");
            $stmt->execute([$id, $userId]);
            header('Location: /member/alamat.php');
            exit;
    }
}


$stmt = $db->prepare("SELECT * FROM addresses WHERE userid = ? ORDER BY isdefault DESC, createdat DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();


$editAddress = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM addresses WHERE addressid = ? AND userid = ?");
    $stmt->execute([$editId, $userId]);
    $editAddress = $stmt->fetch();
}

$pageTitle = 'Alamat Pengiriman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="alamat-page">
    <div class="alamat-container">
        <div class="page-header">
            <h1>📍 Alamat Pengiriman</h1>
            <a href="/member/profile.php" class="btn btn-outline">← Kembali ke Profil</a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>
        
        <div class="alamat-content">
            
            <div class="section-card">
                <h2><?= $editAddress ? '✏️ Edit Alamat' : '➕ Tambah Alamat Baru' ?></h2>
                
                <form action="" method="POST" class="address-form">
                    <input type="hidden" name="action" value="<?= $editAddress ? 'edit' : 'add' ?>">
                    <?php if ($editAddress): ?>
                        <input type="hidden" name="address_id" value="<?= $editAddress['address_id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="label">Label Alamat</label>
                            <input type="text" id="label" name="label" class="form-control" 
                                   placeholder="Contoh: Rumah, Kantor, Kos" required
                                   value="<?= htmlspecialchars($editAddress['label'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="recipient_name">Nama Penerima</label>
                            <input type="text" id="recipient_name" name="recipient_name" class="form-control" required
                                   value="<?= htmlspecialchars($editAddress['recipient_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Nomor HP</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   placeholder="08xxxxxxxxxx" required
                                   value="<?= htmlspecialchars($editAddress['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="city">Kota</label>
                            <input type="text" id="city" name="city" class="form-control" required
                                   value="<?= htmlspecialchars($editAddress['city'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_address">Alamat Lengkap</label>
                        <textarea id="full_address" name="full_address" rows="3" class="form-control" 
                                  placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan..." required><?= htmlspecialchars($editAddress['full_address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">Kode Pos <span class="optional">(opsional)</span></label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" 
                                   placeholder="12345"
                                   value="<?= htmlspecialchars($editAddress['postal_code'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_default" value="1" 
                                       <?= ($editAddress['is_default'] ?? false) ? 'checked' : '' ?>>
                                <span>Jadikan alamat utama</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $editAddress ? 'Simpan Perubahan' : 'Tambah Alamat' ?>
                        </button>
                        <?php if ($editAddress): ?>
                            <a href="/member/alamat.php" class="btn btn-outline">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            
            <div class="section-card">
                <h2>📋 Daftar Alamat</h2>
                
                <?php if (empty($addresses)): ?>
                    <div class="empty-state small">
                        <p>Belum ada alamat tersimpan</p>
                    </div>
                <?php else: ?>
                    <div class="address-list">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="address-card <?= $addr['is_default'] ? 'is-default' : '' ?>">
                                <div class="address-header">
                                    <span class="address-label"><?= sanitize($addr['label']) ?></span>
                                    <?php if ($addr['is_default']): ?>
                                        <span class="badge badge-primary">Utama</span>
                                    <?php endif; ?>
                                </div>
                                <div class="address-body">
                                    <p class="address-name"><?= sanitize($addr['recipient_name']) ?></p>
                                    <p class="address-phone"><?= sanitize($addr['phone']) ?></p>
                                    <p class="address-detail">
                                        <?= sanitize($addr['full_address']) ?>, 
                                        <?= sanitize($addr['city']) ?>
                                        <?= $addr['postal_code'] ? ' ' . sanitize($addr['postal_code']) : '' ?>
                                    </p>
                                </div>
                                <div class="address-actions">
                                    <a href="?edit=<?= $addr['address_id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <?php if (!$addr['is_default']): ?>
                                        <form action="" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="set_default">
                                            <input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">Jadikan Utama</button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="address_id" value="<?= $addr['address_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Yakin hapus alamat ini?')">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
