<?php
if (session_status() === PHP_SESSION_NONE) {
    // ១. បង្កើត Folder សម្រាប់ទុក Session ខ្លួនឯង
    $sessionPath = __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    // ២. កំណត់ឈ្មោះ Session ឱ្យដាច់ដោយឡែក
    session_name('DELITRACK_SESSION');

    // ៣. កំណត់ឱ្យ Cookie ដើរតាម Subfolder របស់ Project (ជំនួសឱ្យ / ធំ)
    $projectPath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
    $projectPath = rtrim($projectPath, '/');
    if (empty($projectPath)) $projectPath = '/';
    
    ini_set('session.cookie_path', $projectPath);
    ini_set('session.gc_maxlifetime', 3600 * 24); // ១ ថ្ងៃ

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
        // បើកកូដនេះដើម្បីដឹងពីមូលហេតុបើនៅតែវិលជុំ (Debug)
        // die("Not Logged In. Session ID: " . session_id()); 

        $isSub = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/driver/') !== false);
        $path = $isSub ? '../index.php' : 'index.php';

        header("Location: $path");
        session_write_close();
        exit();
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../driver/index.php");
        session_write_close();
        exit();
    }
}

function requireDriver()
{
    requireLogin();
    if (!isDriver()) {
        header("Location: ../admin/index.php");
        session_write_close();
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