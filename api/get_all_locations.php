<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();

// Fetch each driver's latest location + recent path (last 30 points)
$drivers = $db->query("SELECT id, name, status FROM users WHERE role='driver'")->fetchAll();

$result = [];
foreach ($drivers as $d) {
    // Latest location
    $latestStmt = $db->prepare("
        SELECT latitude, longitude, speed, created_at
        FROM locations
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $latestStmt->execute([$d['id']]);
    $latest = $latestStmt->fetch();

    // Last 30 points for path
    $pathStmt = $db->prepare("
        SELECT latitude AS lat, longitude AS lng
        FROM locations
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 30
    ");
    $pathStmt->execute([$d['id']]);
    $path = array_reverse($pathStmt->fetchAll());

    // Check if on active delivery
    $delStmt = $db->prepare("SELECT id FROM deliveries WHERE user_id=? AND status='active' LIMIT 1");
    $delStmt->execute([$d['id']]);
    $onDelivery = $delStmt->fetch() ? true : false;

    $result[] = [
        'id' => $d['id'],
        'name' => $d['name'],
        'status' => $d['status'],
        'on_delivery' => $onDelivery,
        'lat' => $latest ? $latest['latitude'] : null,
        'lng' => $latest ? $latest['longitude'] : null,
        'speed' => $latest ? $latest['speed'] : null,
        'last_seen' => $latest ? date('H:i:s', strtotime($latest['created_at'])) : 'Never',
        'path' => $path,
    ];
}

echo json_encode(['success' => true, 'drivers' => $result]);
