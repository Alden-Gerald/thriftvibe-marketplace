<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || getUser()['role'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$productId) {
    header('Location: /admin/konten.php');
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, s.storename, s.city, s.membershiplevel, s.description as store_description,
           u.fullname as owner_name, u.email as owner_email, u.phone as owner_phone
    FROM products p
    JOIN stores s ON p.storeid = s.storeid
    JOIN users u ON s.userid = u.userid
    WHERE p.productid = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /admin/konten.php');
    exit;
}

$pageTitle = 'Detail Produk';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $pageTitle ?> - Admin ThriftVibe
    </title>
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
                <a href="/admin/konten.php" class="nav-item active">Konten</a>
                <a href="/admin/laporan.php" class="nav-item">Laporan</a>
            </nav>
            <div class="sidebar-footer">
                <a href="/auth/logout.php" class="nav-item logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <div class="page-header">
                <a href="/admin/konten.php" class="btn btn-outline">&larr; Kembali</a>
                <h1>Detail Produk</h1>
            </div>

            <div class="admin-product-detail">
                <div class="detail-grid">
                    <!-- Product Info -->
                    <div class="detail-card">
                        <h2>Informasi Produk</h2>
                        <div class="detail-thumbnail">
                            <img src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>"
                                alt="<?= sanitize($product['productname']) ?>">
                        </div>
                        <table class="detail-table">
                            <tr>
                                <th>ID Produk</th>
                                <td>#
                                    <?= $product['productid'] ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Nama Produk</th>
                                <td>
                                    <?= sanitize($product['productname']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Kategori</th>
                                <td>
                                    <?= sanitize($product['category']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Harga</th>
                                <td><strong>
                                        <?= formatRupiah($product['price']) ?>
                                    </strong></td>
                            </tr>
                            <tr>
                                <th>Stok</th>
                                <td>
                                    <?= $product['stock'] ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Ukuran</th>
                                <td>
                                    <?= sanitize($product['size']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Brand</th>
                                <td>
                                    <?= sanitize($product['brand']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="status-badge status-<?= $product['status'] ?>">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Tanggal Upload</th>
                                <td>
                                    <?= date('d M Y, H:i', strtotime($product['uploadedat'])) ?>
                                </td>
                            </tr>
                        </table>

                        <h3>Deskripsi Produk</h3>
                        <p class="description-text">
                            <?= nl2br(sanitize($product['description'])) ?>
                        </p>

                        <?php if ($product['videofile']): ?>
                            <h3>Video Produk</h3>
                            <video controls class="product-video">
                                <source src="/assets/uploads/videos/<?= $product['videofile'] ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </div>

                    <!-- Store & Seller Info -->
                    <div class="detail-card">
                        <h2>Informasi Toko & Penjual</h2>
                        <table class="detail-table">
                            <tr>
                                <th>Nama Toko</th>
                                <td>
                                    <?= sanitize($product['storename']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Kota</th>
                                <td>
                                    <?= sanitize($product['city']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Membership</th>
                                <td>
                                    <?php if ($product['membershiplevel'] === 'premium'): ?>
                                        <span class="badge badge-premium">⭐ Star Seller</span>
                                    <?php else: ?>
                                        <span class="badge badge-regular">Regular</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Deskripsi Toko</th>
                                <td>
                                    <?= sanitize($product['store_description']) ?>
                                </td>
                            </tr>
                        </table>

                        <h3 style="margin-top: 2rem;">Data Pemilik Toko</h3>
                        <table class="detail-table">
                            <tr>
                                <th>Nama Pemilik</th>
                                <td>
                                    <?= sanitize($product['owner_name']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>
                                    <?= sanitize($product['owner_email']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Telepon</th>
                                <td>
                                    <?= sanitize($product['owner_phone']) ?>
                                </td>
                            </tr>
                        </table>

                        <div class="action-buttons" style="margin-top: 2rem;">
                            <form action="/admin/konten.php" method="POST"
                                onsubmit="return confirm('Hapus produk ini?')">
                                <input type="hidden" name="product_id" value="<?= $product['productid'] ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-block">
                                    Hapus Produk Ini
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .page-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            margin: 0;
        }

        .admin-product-detail {
            max-width: 1200px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
        }

        .detail-card h2 {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--color-border);
        }

        .detail-card h3 {
            margin: 1.5rem 0 1rem;
            font-size: 1rem;
        }

        .detail-thumbnail {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .detail-thumbnail img {
            max-width: 300px;
            border-radius: var(--radius-md);
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th,
        .detail-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--color-border);
        }

        .detail-table th {
            width: 40%;
            color: var(--color-text-muted);
            font-weight: 500;
        }

        .description-text {
            background: var(--color-surface-2);
            padding: 1rem;
            border-radius: var(--radius-md);
            line-height: 1.6;
        }

        .product-video {
            width: 100%;
            max-height: 400px;
            border-radius: var(--radius-md);
            background: #000;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-premium {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .badge-regular {
            background: var(--color-surface-2);
            color: var(--color-text-muted);
        }
    </style>
</body>

</html>