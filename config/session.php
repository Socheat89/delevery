<?php
// config/session.php - Final Stability Fix
if (session_status() === PHP_SESSION_NONE) {
    // We use default PHP settings for maximum compatibility with all hosting providers
    session_start();
}

/**
 * Check if the user is authenticated
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the authenticated user is an admin
 */
function isAdmin()
{
    if (!isset($_SESSION['role']))
        return false;
    return strtolower(trim($_SESSION['role'])) === 'admin';
}

/**
 * Check if the authenticated user is a driver
 */
function isDriver()
{
    if (!isset($_SESSION['role']))
        return false;
    return strtolower(trim($_SESSION['role'])) === 'driver';
}

/**
 * Ensure the user is logged in
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        $current = $_SERVER['PHP_SELF'];
        $isSub = (strpos($current, '/admin/') !== false || strpos($current, '/driver/') !== false || strpos($current, '/api/') !== false);
        $target = $isSub ? '../index.php' : 'index.php';

        // Clear session to be sure
        session_unset();
        session_destroy();

        header("Location: $target");
        exit();
    }
}

/**
 * Ensure the user is an admin
 */
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        // If not admin, go back to root to be routed correctly
        $target = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../index.php' : 'index.php';
        session_write_close();
        header("Location: $target");
        exit();
    }
}

/**
 * Ensure the user is a driver
 */
function requireDriver()
{
    requireLogin();
    if (!isDriver()) {
        // If not driver, go back to root to be routed correctly
        $target = (strpos($_SERVER['PHP_SELF'], '/driver/') !== false) ? '../index.php' : 'index.php';
        session_write_close();
        header("Location: $target");
        exit();
    }
}

/**
 * Get current user data
 */
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