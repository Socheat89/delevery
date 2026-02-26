<?php
if (session_status() === PHP_SESSION_NONE) {
    // ១. កំណត់ឈ្មោះ Session ឱ្យដាច់ដោយឡែក
    session_name('DELITRACK_SESSION');

    // ២. រកផ្លូវ URL របស់ Folder ដើម (Project Root) ឱ្យបានត្រឹមត្រូវ
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $parts = explode('/', trim($scriptDir, '/'));

    // ប្រសិនបើកូដស្ថិតនៅក្នុង admin/ ឬ driver/ ឬ api/ យើងត្រូវថយក្រោយ ១ ថ្នាក់
    $currentFolder = end($parts);
    if (in_array($currentFolder, ['admin', 'driver', 'api', 'config'])) {
        array_pop($parts);
    }

    $projectPath = '/' . implode('/', $parts);
    $projectPath = rtrim($projectPath, '/') . '/';
    if ($projectPath === '//')
        $projectPath = '/';

    // ៣. កំណត់ការកំណត់ Cookie ឱ្យមានសុវត្ថិភាព និងបត់បែន
    $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 3600 * 24,
        'path' => $projectPath,
        'secure' => $isSecure,
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