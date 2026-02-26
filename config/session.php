<?php
if (session_status() === PHP_SESSION_NONE) {
    // Use a unique session name for the live environment
    session_name('DELITRACK_SESSION_LIVE');

    // Use default session handling which is most compatible with cPanel/Shared hosting
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
        // Detect if we are inside a subdirectory to redirect correctly
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
        header("Location: ../driver/index.php");
        exit();
    }
}

function requireDriver()
{
    requireLogin();
    if (!isDriver()) {
        header("Location: ../admin/index.php");
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