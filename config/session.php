<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Set flash message
 * @param string $type - success, error, warning, info
 * @param string $message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['userid']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getUserId()
{
    return $_SESSION['userid'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getUser()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Set user session after login
 * @param array $user
 */
function setUserSession($user)
{
    $_SESSION['userid'] = $user['userid'];
    $_SESSION['user'] = $user;
}

/**
 * Destroy user session (logout)
 */
function destroySession()
{
    session_unset();
    session_destroy();
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan login terlebih dahulu');
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin()
{
    requireLogin();
    if (getUser()['role'] !== 'admin') {
        setFlash('error', 'Akses ditolak');
        header('Location: /');
        exit;
    }
}

/**
 * Require user to have a store
 */
function requireStore()
{
    requireLogin();
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/../includes/functions.php';

    if (!hasStore(getUserId())) {
        setFlash('error', 'Anda belum memiliki toko');
        header('Location: /member/buka-toko.php');
        exit;
    }
}
