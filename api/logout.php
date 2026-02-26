<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    $db = getDB();
    // Set offline
    $db->prepare("UPDATE users SET status='offline' WHERE id=?")->execute([$_SESSION['user_id']]);
    // Stop active deliveries
    $db->prepare("UPDATE deliveries SET status='completed', ended_at=NOW() WHERE user_id=? AND status='active'")
        ->execute([$_SESSION['user_id']]);
}

session_destroy();
header('Location: ../index.php');
exit();
