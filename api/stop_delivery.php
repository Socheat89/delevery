<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$db = getDB();

// Complete active delivery
$db->prepare("UPDATE deliveries SET status='completed', ended_at=NOW() WHERE user_id=? AND status='active'")
    ->execute([$userId]);

// Set driver offline
$db->prepare("UPDATE users SET status='offline' WHERE id=?")->execute([$userId]);

echo json_encode(['success' => true]);
