<?php
// config/session.php - Robust Version
if (session_status() === PHP_SESSION_NONE) {
    session_name('DELITRACK_SESSION_V4');

    // Force cookie to be available everywhere in the domain
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin()
{
    // ឆែកមើល Role ឱ្យហ្មត់ចត់ (ដក space និងប្តូរជាអក្សរតូច)
    return isset($_SESSION['role']) && trim(strtolower($_SESSION['role'])) === 'admin';
}

function isDriver()
{
    // ឆែកមើល Role ឱ្យហ្មត់ចត់ (ដក space និងប្តូរជាអក្សរតូច)
    return isset($_SESSION['role']) && trim(strtolower($_SESSION['role'])) === 'driver';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        $currentPath = $_SERVER['PHP_SELF'];
        $isSub = (strpos($currentPath, '/admin/') !== false || strpos($currentPath, '/driver/') !== false || strpos($currentPath, '/api/') !== false);
        $path = $isSub ? '../index.php' : 'index.php';

        header("Location: $path");
        exit();
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        // បើមិនមែន admin ទេ ឱ្យទៅទំព័រ login ដើម្បីឆែក role ឡើងវិញ ជៀសវាងវិលជុំ
        $path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../index.php' : 'index.php';
        header("Location: $path?error=access_denied_admin");
        exit();
    }
}

function requireDriver()
{
    requireLogin();
    if (!isDriver()) {
        // បើមិនមែន driver ទេ ឱ្យទៅទំព័រ login ដើម្បីឆែក role ឡើងវិញ ជៀសវាងវិលជុំ
        $path = (strpos($_SERVER['PHP_SELF'], '/driver/') !== false) ? '../index.php' : 'index.php';
        header("Location: $path?error=access_denied_driver");
        exit();
    }
}

function getCurrentUser()
{
    if (!isLoggedIn())
        return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role'],
    ];
}