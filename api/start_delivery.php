<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

if (!isLoggedIn() || !isDriver()) {
    echo json_encode(['success' => false, 'message' => 'Driver only']);
    exit();
}

$userId = $_SESSION['user_id'];
$db = getDB();

// Check no active delivery
$existing = $db->prepare("SELECT id FROM deliveries WHERE user_id=? AND status='active' LIMIT 1");
$existing->execute([$userId]);
if ($existing->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Delivery already active']);
    exit();
}

// Create delivery record
$stmt = $db->prepare("INSERT INTO deliveries (user_id, status) VALUES (?, 'active')");
$stmt->execute([$userId]);
$deliveryId = $db->lastInsertId();

// Set driver online
$db->prepare("UPDATE users SET status='online' WHERE id=?")->execute([$userId]);

echo json_encode(['success' => true, 'delivery_id' => (int) $deliveryId]);
