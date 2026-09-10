<?php


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $productId = (int) $_POST['product_id'];
    $stmt = $db->prepare("DELETE FROM products WHERE productid = ?");
    $stmt->execute([$productId]);
    setFlash('success', 'Konten berhasil dihapus');
    header('Location: /admin/konten.php');
    exit;
}


$search = trim($_GET['q'] ?? '');
$where = "";
$params = [];
if ($search) {
    $where = "WHERE p.productname LIKE ? OR s.storename LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql = "SELECT p.*, s.storename FROM products p JOIN stores s ON p.storeid = s.storeid $where ORDER BY p.uploadedat DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Moderasi Konten';
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
                <a href="/admin/langganan.php" class="nav-item">Langganan</a>
                <a href="/admin/konten.php" class="nav-item active">Konten</a>
                <a href="/admin/laporan.php" class="nav-item">Laporan</a>
            </nav>
            <div class="sidebar-footer">
                <a href="/auth/logout.php" class="nav-item logout">Logout</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1>Moderasi Konten</h1>
                <form action="" method="GET" class="search-form-inline">
                    <input type="text" name="q" placeholder="Cari produk atau toko..."
                        value="<?= htmlspecialchars($search) ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
            </div>

            <?php $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <div class="section-card">
                <p>Ditemukan <?= count($products) ?> produk</p>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thumbnail</th>
                            <th>Nama Produk</th>
                            <th>Toko</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="/assets/uploads/thumbnails/<?= $product['thumbnail'] ?>" alt=""
                                        style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
                                </td>
                                <td>
                                    <a href="/admin/product-detail.php?id=<?= $product['productid'] ?>">
                                        <?= sanitize(truncate($product['productname'], 40)) ?>
                                    </a>
                                </td>
                                <td><?= sanitize($product['storename']) ?></td>
                                <td><?= formatRupiah($product['price']) ?></td>
                                <td><span
                                        class="status-badge status-<?= $product['status'] ?>"><?= ucfirst($product['status']) ?></span>
                                </td>
                                <td>
                                    <a href="/admin/product-detail.php?id=<?= $product['productid'] ?>"
                                        class="btn btn-outline btn-sm">Lihat</a>
                                    <form action="" method="POST" style="display:inline;"
                                        onsubmit="return confirm('Hapus konten ini?')">
                                        <input type="hidden" name="product_id" value="<?= $product['productid'] ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>