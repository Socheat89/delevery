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

$lat = trim($_POST['latitude'] ?? '');
$lng = trim($_POST['longitude'] ?? '');
$speed = trim($_POST['speed'] ?? '');
$accuracy = trim($_POST['accuracy'] ?? '');

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Missing lat/lng']);
    exit();
}

$userId = $_SESSION['user_id'];
$db = getDB();

// Insert location
$stmt = $db->prepare("INSERT INTO locations (user_id, latitude, longitude, speed, accuracy) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userId, $lat, $lng, $speed ?: null, $accuracy ?: null]);

// Update user status to online
$db->prepare("UPDATE users SET status='online' WHERE id=?")->execute([$userId]);

echo json_encode(['success' => true, 'message' => 'Location saved']);
