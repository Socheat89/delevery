<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$db = getDB();
$drivers = $db->query("SELECT id, name FROM users WHERE role='driver' ORDER BY name")->fetchAll();

$filterDriver = isset($_GET['driver_id']) ? (int) $_GET['driver_id'] : 0;
$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$where = ["DATE(l.created_at) BETWEEN ? AND ?"];
$params = [$dateFrom, $dateTo];
if ($filterDriver) {
    $where[] = "l.user_id = ?";
    $params[] = $filterDriver;
}

$stmt = $db->prepare("
    SELECT u.name, l.latitude, l.longitude, l.speed, l.created_at
    FROM locations l
    JOIN users u ON u.id = l.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY l.created_at ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="locations_' . date('Ymd_His') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['Driver Name', 'Latitude', 'Longitude', 'Speed (km/h)', 'Timestamp']);
foreach ($rows as $r) {
    fputcsv($out, [$r['name'], $r['latitude'], $r['longitude'], $r['speed'] ?? '', $r['created_at']]);
}
fclose($out);
exit();
