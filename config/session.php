<?php
if (session_status() === PHP_SESSION_NONE) {
    // ១. កំណត់ឈ្មោះ Session ឱ្យប្លែកគេបំផុត
    session_name('DELITRACK_SESSION_V3');

    // ២. ពិនិត្យរកមើល HTTPS ដើម្បីកំណត់សុវត្ថិភាព Cookie
    $isSecure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        $isSecure = true;
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        $isSecure = true;

    // ៣. កំណត់ឱ្យ Cookie អាចប្រើបានគ្រប់ទីកន្លែងក្នុង Domain (Path = /)
    // នេះជួយឱ្យ admin/ និង driver/ អាចឃើញ Session ដូចគ្នា
    session_set_cookie_params([
        'lifetime' => 86400, // ១ ថ្ងៃ
        'path' => '/',
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
        $currentPath = $_SERVER['PHP_SELF'];
        // ឆែកមើលថាតើយើងនៅក្នុង Subfolder ឬអត់
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
        // ប្រសិនបើមិនមែន Admin ទេ ឱ្យត្រឡប់ទៅ index.php ដើម្បីឱ្យវាឆែកបន្ត (ជៀសវាង loop រវាង subfolder)
        $path = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../index.php' : 'index.php';
        header("Location: $path");
        exit();
    }
}

function requireDriver()
{
    requireLogin();
    if (!isDriver()) {
        // ប្រសិនបើមិនមែន Driver ទេ ឱ្យត្រឡប់ទៅ index.php (ជៀសវាង loop រវាង subfolder)
        $path = (strpos($_SERVER['PHP_SELF'], '/driver/') !== false) ? '../index.php' : 'index.php';
        header("Location: $path");
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