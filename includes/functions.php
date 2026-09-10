<?php


require_once __DIR__ . '/../config/database.php';

/**
 * Format number to Rupiah currency
 * @param int $number
 * @return string
 */
function formatRupiah($number)
{
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * Upload file with validation
 * @param array $file - $_FILES['field']
 * @param string $targetDir - Target directory
 * @param array $allowedTypes - Allowed MIME types
 * @param int $maxSize - Max file size in bytes
 * @return array - ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadFile($file, $targetDir, $allowedTypes, $maxSize)
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        $maxMB = $maxSize / 1024 / 1024;
        return ['success' => false, 'error' => "File terlalu besar. Maksimal {$maxMB}MB"];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipe file tidak diizinkan'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;

    // Create directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Gagal menyimpan file'];
}

/**
 * Check if user has a store
 * @param int $userId
 * @return bool
 */
function hasStore($userId)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT storeid FROM stores WHERE userid = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() !== false;
}

/**
 * Get user's store data
 * @param int $userId
 * @return array|null
 */
function getStore($userId)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM stores WHERE userid = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/**
 * Check if store is premium
 * @param int $storeId
 * @return bool
 */
function isPremium($storeId)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT membershiplevel, expireddate FROM stores WHERE storeid = ?");
    $stmt->execute([$storeId]);
    $store = $stmt->fetch();

    if (!$store)
        return false;

    // Check if premium and not expired
    if ($store['membershiplevel'] === 'premium') {
        if ($store['expireddate'] === null || $store['expireddate'] >= date('Y-m-d')) {
            return true;
        }
    }

    return false;
}

/**
 * Get cart count for user
 * @param int $userId
 * @return int
 */
function getCartCount($userId)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM carts WHERE userid = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Sanitize input
 * @param string $input
 * @return string
 */
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get cities list for dropdown
 * @return array
 */
function getCities()
{
    return [
        'Jakarta',
        'Bandung',
        'Surabaya',
        'Yogyakarta',
        'Semarang',
        'Malang',
        'Medan',
        'Makassar',
        'Bali',
        'Palembang'
    ];
}

/**
 * Get product categories
 * @return array
 */
function getCategories()
{
    return ['Hoodie', 'Jaket', 'Kaos', 'Celana'];
}

/**
 * Time ago format
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0)
        return $diff->y . ' tahun lalu';
    if ($diff->m > 0)
        return $diff->m . ' bulan lalu';
    if ($diff->d > 0)
        return $diff->d . ' hari lalu';
    if ($diff->h > 0)
        return $diff->h . ' jam lalu';
    if ($diff->i > 0)
        return $diff->i . ' menit lalu';
    return 'Baru saja';
}

/**
 * Truncate text
 * @param string $text
 * @param int $length
 * @return string
 */
function truncate($text, $length = 100)
{
    if (strlen($text) <= $length)
        return $text;
    return substr($text, 0, $length) . '...';
}
