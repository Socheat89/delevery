<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$db = getDB();
$drivers = $db->query("SELECT id, name FROM users WHERE role='driver' ORDER BY name")->fetchAll();

$filterDriver = isset($_GET['driver_id']) ? (int) $_GET['driver_id'] : 0;
$filterDate = $_GET['date'] ?? date('Y-m-d');

// Build query
$where = ["DATE(l.created_at) = ?"];
$params = [$filterDate];
if ($filterDriver) {
    $where[] = "l.user_id = ?";
    $params[] = $filterDriver;
}
$whereStr = implode(' AND ', $where);

$history = $db->prepare("
    SELECT l.id, u.name, l.latitude, l.longitude, l.speed, l.created_at
    FROM locations l
    JOIN users u ON u.id = l.user_id
    WHERE $whereStr
    ORDER BY l.created_at DESC
    LIMIT 500
");
$history->execute($params);
$rows = $history->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route History – DeliTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #22d3ee;
            --green: #10b981;
            --bg: #0f0f1a;
            --surface: #1a1a2e;
            --border: rgba(255, 255, 255, .07);
            --text: #e2e8f0;
            --muted: #64748b;
            --sidebar-w: 260px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand .icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .sidebar-brand h2 {
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
        }

        .nav-section {
            padding: 8px 0;
            flex: 1;
            overflow-y: auto;
        }

        .nav-label {
            font-size: .65rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 20px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: all .2s;
            position: relative;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .04);
            color: #fff;
        }

        .nav-item.active {
            background: rgba(99, 102, 241, .15);
            color: var(--primary);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary);
        }

        .nav-item i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .85rem;
            color: #fff;
        }

        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
        }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        .content {
            padding: 28px;
        }

        .card-surface {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .filter-bar {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        select,
        input[type=date] {
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 8px 12px;
            font-size: .88rem;
        }

        select:focus,
        input[type=date]:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-filter {
            background: var(--primary);
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
        }

        #histMap {
            height: 360px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 12px 16px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            background: rgba(255, 255, 255, .02);
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, .03);
        }

        td {
            padding: 11px 16px;
            font-size: .85rem;
            color: var(--text);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }
    </style>
</head>

<body>
    <nav class="sidebar">
        <div class="sidebar-brand">
            <div class="icon">🚚</div>
            <div>
                <h2>DeliTrack</h2>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-label">Overview</div>
            <a href="index.php" class="nav-item"><i class="bi bi-grid"></i> Dashboard</a>
            <a href="map.php" class="nav-item"><i class="bi bi-map"></i> Live Map</a>
            <div class="nav-label">Management</div>
            <a href="drivers.php" class="nav-item"><i class="bi bi-people"></i> Drivers</a>
            <a href="history.php" class="nav-item active"><i class="bi bi-clock-history"></i> Route
                History</a>
            <a href="export.php" class="nav-item"><i class="bi bi-download"></i> Export CSV</a>
        </div>
        <div class="sidebar-footer">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size:.85rem;font-weight:600;color:#fff;">
                        <?= htmlspecialchars($_SESSION['name']) ?>
                    </div>
                    <div style="font-size:.72rem;color:var(--muted);">Administrator</div>
                </div>
            </div>
            <a href="../api/logout.php" style="color:#ef4444;font-size:.82rem;text-decoration:none;"><i
                    class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </nav>

    <div class="main">
        <div class="topbar">
            <span class="topbar-title"><i class="bi bi-clock-history" style="color:var(--primary)"></i> Route
                History</span>
            <a href="export.php?driver_id=<?= $filterDriver ?>&date=<?= $filterDate ?>"
                style="background:rgba(16,185,129,.15);color:#10b981;border:none;border-radius:8px;padding:7px 14px;font-size:.8rem;font-weight:600;text-decoration:none;">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
        <div class="content">

            <!-- Filter -->
            <div class="card-surface">
                <form class="filter-bar" method="GET">
                    <select name="driver_id">
                        <option value="">All Drivers</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $filterDriver == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel"></i> Filter</button>
                </form>
                <div id="histMap"></div>
            </div>

            <!-- Table -->
            <div class="card-surface">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Driver</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Speed</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="6" class="no-data">📭 No location data for the selected filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $r): ?>
                                <tr>
                                    <td style="color:var(--muted)">
                                        <?= $i + 1 ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['latitude']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['longitude']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['speed'] ?? '—') ?> km/h
                                    </td>
                                    <td>
                                        <?= date('H:i:s', strtotime($r['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const histMap = L.map('histMap').setView([11.5564, 104.9282], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(histMap);

        const histPoints = <?= json_encode(array_map(fn($r) => [
            'lat' => $r['latitude'],
            'lng' => $r['longitude'],
            'name' => $r['name'],
            'time' => date('H:i:s', strtotime($r['created_at'])),
            'speed' => $r['speed']
        ], $rows)) ?>;

        if (histPoints.length > 0) {
            const pts = histPoints.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
            L.polyline(pts, { color: '#6366f1', weight: 3, opacity: .8, dashArray: '8,4' }).addTo(histMap);

            // Start/End markers
            L.circleMarker(pts[pts.length - 1], { radius: 8, color: '#10b981', fillColor: '#10b981', fillOpacity: 1 })
                .addTo(histMap).bindPopup('🚦 Start: ' + histPoints[histPoints.length - 1].time);
            L.circleMarker(pts[0], { radius: 8, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 1 })
                .addTo(histMap).bindPopup('🏁 End: ' + histPoints[0].time);

            histMap.fitBounds(L.latLngBounds(pts).pad(.1));
        }
    </script>
</body>

</html>