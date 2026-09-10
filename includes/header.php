<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

$user = getUser();
$cartCount = isLoggedIn() ? getCartCount(getUserId()) : 0;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ThriftVibe Market' ?> - Video-First Thrift Marketplace</title>
    <meta name="description"
        content="Marketplace pakaian bekas dengan video pendek. Lihat kondisi nyata barang sebelum beli.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="navbar-brand">
                <img src="/assets/img/logo.svg" alt="ThriftVibe" class="brand-logo">
                <span class="brand-text">ThriftVibe</span>
            </a>

            <form action="/browse.php" method="GET" class="search-form">
                <div class="search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" name="q" placeholder="Cari jaket, hoodie, kaos..." class="search-input"
                        value="<?= $_GET['q'] ?? '' ?>">
                </div>
            </form>

            <div class="navbar-actions">
                <a href="/browse.php" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Explore</span>
                </a>

                <?php if (isLoggedIn()): ?>
                    <a href="/member/cart.php" class="nav-link cart-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if (hasStore(getUserId())): ?>
                        <a href="/seller/dashboard.php" class="nav-link seller-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                            <span>Toko Saya</span>
                        </a>
                    <?php endif; ?>

                    <div class="user-dropdown">
                        <button class="user-avatar-btn" id="userDropdownBtn">
                            <img src="/assets/uploads/profile/<?= $user['profile_photo'] ?>" alt="Profile"
                                class="user-avatar">
                        </button>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <div class="dropdown-header">
                                <span class="dropdown-name"><?= sanitize($user['full_name']) ?></span>
                                <span class="dropdown-email"><?= sanitize($user['email']) ?></span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="/member/profile.php" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Profil Saya
                            </a>
                            <a href="/member/orders.php" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14,2 14,8 20,8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                </svg>
                                Pesanan Saya
                            </a>
                            <a href="/member/wishlist.php" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                                    </path>
                                </svg>
                                Wishlist
                            </a>
                            <?php if (!hasStore(getUserId())): ?>
                                <a href="/member/buka-toko.php" class="dropdown-item highlight">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                                    </svg>
                                    Buka Toko Gratis
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="/auth/logout.php" class="dropdown-item logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16,17 21,12 16,7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/auth/login.php" class="btn btn-outline">Login</a>
                    <a href="/auth/register.php" class="btn btn-primary">Daftar</a>
                <?php endif; ?>
            </div>


            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </nav>


    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
            <span><?= $flash['message'] ?></span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>

    <main class="main-content">
