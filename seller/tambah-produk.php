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
    $productName = trim($_POST['nama_produk'] ?? '');
    $category = $_POST['kategori'] ?? '';
    $price = (int) ($_POST['harga'] ?? 0);
    $stock = (int) ($_POST['stok'] ?? 1);
    $description = trim($_POST['deskripsi'] ?? '');
    $size = $_POST['ukuran'] ?? '';
    $brand = trim($_POST['brand'] ?? '');


    if (empty($productName)) {
        $errors['nama_produk'] = 'Nama produk wajib diisi';
    } elseif (strlen($productName) > 100) {
        $errors['nama_produk'] = 'Nama produk maksimal 100 karakter';
    }


    if (!in_array($category, ['Hoodie', 'Jaket', 'Kaos', 'Celana'])) {
        $errors['kategori'] = 'Pilih kategori';
    }


    if ($price <= 0) {
        $errors['harga'] = 'Harga harus lebih dari 0';
    }


    if ($stock < 1) {
        $stock = 1;
    }


    $videoFilename = null;
    if (isset($_FILES['file_video']) && $_FILES['file_video']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile(
            $_FILES['file_video'],
            __DIR__ . '/../assets/uploads/videos',
            ['video/mp4'],
            20 * 1024 * 1024 // 20MB
        );

        if ($result['success']) {
            $videoFilename = $result['filename'];
        } else {
            $errors['file_video'] = $result['error'];
        }
    }


    $thumbnailFilename = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile(
            $_FILES['thumbnail'],
            __DIR__ . '/../assets/uploads/thumbnails',
            ['image/jpeg', 'image/png'],
            2 * 1024 * 1024 // 2MB
        );

        if ($result['success']) {
            $thumbnailFilename = $result['filename'];
        } else {
            $errors['thumbnail'] = $result['error'];
        }
    } else {
        $errors['thumbnail'] = 'Upload thumbnail (JPG/PNG, max 2MB)';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO products (storeid, productname, category, price, stock, description, size, brand, videofile, thumbnail)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $store['storeid'],
            $productName,
            $category,
            $price,
            $stock,
            $description ?: null,
            $size ?: null,
            $brand ?: null,
            $videoFilename,
            $thumbnailFilename
        ]);

        setFlash('success', 'Produk berhasil ditambahkan!');
        header('Location: /seller/produk.php');
        exit;
    }
}

$pageTitle = 'Tambah Produk';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - ThriftVibe Market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="dashboard-page">
    <div class="dashboard-layout">

        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="/" class="sidebar-logo">
                    <span class="brand-icon">🔥</span>
                    <span class="brand-text">ThriftVibe</span>
                </a>
            </div>

            <div class="store-info">
                <div class="store-avatar">
                    <span><?= strtoupper(substr($store['store_name'], 0, 1)) ?></span>
                </div>
                <div class="store-details">
                    <h3><?= sanitize($store['store_name']) ?></h3>
                    <span class="store-badge <?= $isPremium ? 'premium' : 'regular' ?>">
                        <?= $isPremium ? '⭐ Star Seller' : 'Regular' ?>
                    </span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="/seller/dashboard.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Dashboard
                </a>
                <a href="/seller/produk.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                    </svg>
                    Produk Saya
                </a>
                <a href="/seller/tambah-produk.php" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah Produk
                </a>
                <a href="/seller/pesanan.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                    </svg>
                    Pesanan Masuk
                </a>
                <a href="/seller/langganan.php" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                        </polygon>
                    </svg>
                    Star Seller
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="/" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    </svg>
                    Kembali ke Store
                </a>
            </div>
        </aside>


        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1>Tambah Produk Baru</h1>
            </div>

            <div class="section-card">
                <form action="" method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama_produk">Nama Produk</label>
                            <input type="text" id="nama_produk" name="nama_produk"
                                value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>"
                                class="form-control <?= isset($errors['nama_produk']) ? 'error' : '' ?>"
                                placeholder="Contoh: Crewneck Nike Vintage Size L" maxlength="100" required>
                            <small class="form-hint">Maksimal 100 karakter</small>
                            <?php if (isset($errors['nama_produk'])): ?>
                                <span class="error-text"><?= $errors['nama_produk'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="kategori"
                                class="form-control <?= isset($errors['kategori']) ? 'error' : '' ?>" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach (getCategories() as $cat): ?>
                                    <option value="<?= $cat ?>" <?= ($_POST['kategori'] ?? '') === $cat ? 'selected' : '' ?>>
                                        <?= $cat ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['kategori'])): ?>
                                <span class="error-text"><?= $errors['kategori'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="harga">Harga (Rp)</label>
                            <input type="number" id="harga" name="harga" value="<?= $_POST['harga'] ?? '' ?>"
                                class="form-control <?= isset($errors['harga']) ? 'error' : '' ?>" placeholder="150000"
                                min="1" required>
                            <?php if (isset($errors['harga'])): ?>
                                <span class="error-text"><?= $errors['harga'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="stok">Stok</label>
                            <input type="number" id="stok" name="stok" value="<?= $_POST['stok'] ?? 1 ?>"
                                class="form-control" min="1" value="1">
                            <small class="form-hint">Default 1 untuk barang thrift</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ukuran">Ukuran</label>
                            <select id="ukuran" name="ukuran" class="form-control">
                                <option value="">Pilih Ukuran</option>
                                <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                    <option value="<?= $size ?>" <?= ($_POST['ukuran'] ?? '') === $size ? 'selected' : '' ?>>
                                        <?= $size ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="brand">Brand <span class="optional">(opsional)</span></label>
                            <input type="text" id="brand" name="brand" class="form-control"
                                placeholder="Nike, Adidas, dll" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" class="form-control"
                            placeholder="Jelaskan minus barang, ukuran detail (P x L)..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                        <small class="form-hint">Jelaskan minus dan detail lainnya</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="file_video">Video Produk <span class="optional">(opsional)</span></label>
                            <input type="file" id="file_video" name="file_video"
                                class="form-control <?= isset($errors['file_video']) ? 'error' : '' ?>"
                                accept="video/mp4">
                            <small class="form-hint">MP4, maksimal 20MB</small>
                            <video id="videoPreview" class="upload-preview hidden" controls></video>
                            <?php if (isset($errors['file_video'])): ?>
                                <span class="error-text"><?= $errors['file_video'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="thumbnail">Foto Produk <span class="required">*</span></label>
                            <input type="file" id="thumbnail" name="thumbnail"
                                class="form-control <?= isset($errors['thumbnail']) ? 'error' : '' ?>"
                                accept="image/jpeg,image/png" required>
                            <small class="form-hint">JPG/PNG, maksimal 2MB</small>
                            <img id="thumbPreview" class="upload-preview hidden">
                            <?php if (isset($errors['thumbnail'])): ?>
                                <span class="error-text"><?= $errors['thumbnail'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="/seller/produk.php" class="btn btn-outline">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>

        document.getElementById('file_video').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const preview = document.getElementById('videoPreview');
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
            }
        });

        document.getElementById('thumbnail').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const preview = document.getElementById('thumbPreview');
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
            }
        });
    </script>

    <script src="/assets/js/main.js"></script>
</body>

</html>