<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();

$online = $db->query("SELECT COUNT(*) FROM users WHERE role='driver' AND status='online'")->fetchColumn();
$activeDeliveries = $db->query("SELECT COUNT(*) FROM deliveries WHERE status='active'")->fetchColumn();
$todayPings = $db->query("SELECT COUNT(*) FROM locations WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalDrivers = $db->query("SELECT COUNT(*) FROM users WHERE role='driver'")->fetchColumn();

echo json_encode([
    'success' => true,
    'online' => (int) $online,
    'active_deliveries' => (int) $activeDeliveries,
    'today_pings' => (int) $todayPings,
    'total_drivers' => (int) $totalDrivers,
]);
