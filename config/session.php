<?php
// config/session.php - Standardized Version for Hosting
if (session_status() === PHP_SESSION_NONE) {
    // We use a simple session name
    session_name('DRV_SESS_ID');

    // Default session start is often the most stable on shared hosting
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isDriver()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'driver';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        $currentPath = $_SERVER['PHP_SELF'];
        $isSub = (strpos($currentPath, '/admin/') !== false || strpos($currentPath, '/driver/') !== false || strpos($currentPath, '/api/') !== false);
        $path = $isSub ? '../index.php' : 'index.php';

        header("Cache-Control: no-cache, must-revalidate");
        header("Location: $path");
        exit();
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        // Redirect to a neutral zone if not admin, instead of looping
        session_write_close();
        header("Location: ../index.php?error=unauthorized_admin");
        exit();
    }
}

function requireDriver()
{
    requireLogin();
    if (!isDriver()) {
        // Redirect to a neutral zone if not driver, instead of looping
        session_write_close();
        header("Location: ../index.php?error=unauthorized_driver");
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