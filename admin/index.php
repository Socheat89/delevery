<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$db = getDB();

// Stats
$totalDrivers = $db->query("SELECT COUNT(*) FROM users WHERE role='driver'")->fetchColumn();
$onlineDrivers = $db->query("SELECT COUNT(*) FROM users WHERE role='driver' AND status='online'")->fetchColumn();
$todayLocs = $db->query("SELECT COUNT(*) FROM locations WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$activeDeliveries = $db->query("SELECT COUNT(*) FROM deliveries WHERE status='active'")->fetchColumn();

// Recent drivers
$drivers = $db->query("
    SELECT u.id, u.name, u.email, u.status, u.phone,
    (SELECT id FROM deliveries WHERE user_id = u.id AND status = 'active' LIMIT 1) as active_delivery_id
    FROM users u 
    WHERE u.role='driver' 
    ORDER BY u.status DESC, u.name ASC
")->fetchAll();

// Active Deliveries List
$activeDeliveriesList = $db->query("
    SELECT d.id, u.name, d.started_at, u.id as driver_id
    FROM deliveries d
    JOIN users u ON d.user_id = u.id
    WHERE d.status='active'
    ORDER BY d.started_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – DeliTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-d: #4f46e5;
            --accent: #22d3ee;
            --green: #10b981;
            --yellow: #f59e0b;
            --red: #ef4444;
            --bg: #0f0f1a;
            --surface: #1a1a2e;
            --surface2: #16213e;
            --border: rgba(255, 255, 255, .07);
            --text: #e2e8f0;
            --muted: #64748b;
            --sidebar-w: 260px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
        }

        /* ── Sidebar ── */
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
            transition: transform .3s;
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

        .sidebar-brand p {
            font-size: .7rem;
            color: var(--muted);
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
            border-radius: 0;
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
            border-radius: 0 3px 3px 0;
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

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .user-name {
            font-size: .85rem;
            font-weight: 600;
            color: #fff;
        }

        .user-role {
            font-size: .72rem;
            color: var(--muted);
        }

        /* ── Main content ── */
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

        .topbar-time {
            font-size: .82rem;
            color: var(--muted);
        }

        .content {
            padding: 28px;
        }

        /* ── Stat cards ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: attr(data-icon);
            position: absolute;
            right: 14px;
            top: 14px;
            font-size: 2.2rem;
            opacity: .12;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }

        .stat-label {
            font-size: .8rem;
            color: var(--muted);
            margin-top: 6px;
            font-weight: 500;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            margin-top: 10px;
        }

        .stat-badge.green {
            background: rgba(16, 185, 129, .15);
            color: var(--green);
        }

        .stat-badge.blue {
            background: rgba(99, 102, 241, .15);
            color: var(--primary);
        }

        .stat-badge.cyan {
            background: rgba(34, 211, 238, .15);
            color: var(--accent);
        }

        .stat-badge.amber {
            background: rgba(245, 158, 11, .15);
            color: var(--yellow);
        }

        /* ── Map card ── */
        .map-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .map-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .map-title {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        #liveMap {
            height: 420px;
        }

        /* ── Driver table ── */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .section-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
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
            transition: background .15s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, .03);
        }

        td {
            padding: 13px 16px;
            font-size: .88rem;
            color: var(--text);
        }

        .status-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .dot.online {
            background: var(--green);
            box-shadow: 0 0 6px var(--green);
            animation: pulse 2s infinite;
        }

        .dot.offline {
            background: var(--muted);
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        .btn-sm-action {
            border: none;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-track {
            background: rgba(99, 102, 241, .15);
            color: var(--primary);
        }

        .btn-track:hover {
            background: var(--primary);
            color: #fff;
        }

        .btn-edit {
            background: rgba(34, 211, 238, .12);
            color: var(--accent);
        }

        .btn-edit:hover {
            background: var(--accent);
            color: #000;
        }

        .btn-del {
            background: rgba(239, 68, 68, .12);
            color: var(--red);
        }

        .btn-del:hover {
            background: var(--red);
            color: #fff;
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .stat-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Pulse live indicator */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, .1);
            color: var(--green);
            border: 1px solid rgba(16, 185, 129, .25);
            border-radius: 20px;
            padding: 4px 10px;
            font-size: .75rem;
            font-weight: 600;
        }

        .live-dot {
            width: 7px;
            height: 7px;
            background: var(--green);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        /* Modal overlay */
        #driverModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .65);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        #driverModal.show {
            display: flex;
        }

        .modal-box {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .6);
        }

        .modal-box h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
        }

        .modal-box label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
            margin-top: 12px;
        }

        .modal-box input,
        .modal-box select {
            width: 100%;
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 10px 12px;
            font-size: .9rem;
        }

        .modal-box input:focus,
        .modal-box select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: rgba(255, 255, 255, .06);
            border: 1px solid var(--border);
            color: #94a3b8;
            border-radius: 8px;
            padding: 8px 18px;
            cursor: pointer;
        }

        .btn-submit {
            background: var(--primary);
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="icon">🚚</div>
            <div>
                <h2>DeliTrack</h2>
                <p>Admin Control Panel</p>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-label">Overview</div>
            <a href="index.php" class="nav-item active" id="nav-dashboard">
                <i class="bi bi-grid"></i> Dashboard
            </a>
            <a href="map.php" class="nav-item" id="nav-map">
                <i class="bi bi-map"></i> Live Map
            </a>

            <div class="nav-label">Management</div>
            <a href="drivers.php" class="nav-item" id="nav-drivers">
                <i class="bi bi-people"></i> Drivers
            </a>
            <a href="history.php" class="nav-item" id="nav-history">
                <i class="bi bi-clock-history"></i> Route History
            </a>
            <a href="export.php" class="nav-item" id="nav-export">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
        <div class="sidebar-footer">
            <div class="user-badge">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                </div>
                <div>
                    <div class="user-name">
                        <?= htmlspecialchars($_SESSION['name']) ?>
                    </div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="../api/logout.php"
                style="display:block;margin-top:12px;color:var(--red);font-size:.82rem;text-decoration:none;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button onclick="document.getElementById('sidebar').classList.toggle('open')"
                    style="display:none;background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;"
                    id="menuBtn">
                    <i class="bi bi-list"></i>
                </button>
                <span class="topbar-title">Dashboard</span>
            </div>
            <div style="display:flex;align-items:center;gap:16px;">
                <div class="live-badge">
                    <div class="live-dot"></div> LIVE
                </div>
                <span class="topbar-time" id="clock"></span>
            </div>
        </div>

        <div class="content">

            <!-- Stat Cards -->
            <div class="stat-grid">
                <div class="stat-card" data-icon="🚗">
                    <div class="stat-value" id="statTotal">
                        <?= $totalDrivers ?>
                    </div>
                    <div class="stat-label">Total Drivers</div>
                    <div class="stat-badge blue"><i class="bi bi-person"></i> Registered</div>
                </div>
                <div class="stat-card" data-icon="📡">
                    <div class="stat-value" id="statOnline">
                        <?= $onlineDrivers ?>
                    </div>
                    <div class="stat-label">Online Now</div>
                    <div class="stat-badge green"><i class="bi bi-circle-fill"></i> Active</div>
                </div>
                <div class="stat-card" data-icon="📦">
                    <div class="stat-value" id="statDeliveries">
                        <?= $activeDeliveries ?>
                    </div>
                    <div class="stat-label">Active Deliveries</div>
                    <div class="stat-badge amber"><i class="bi bi-truck"></i> Running</div>
                </div>
                <div class="stat-card" data-icon="📍">
                    <div class="stat-value" id="statLocs">
                        <?= $todayLocs ?>
                    </div>
                    <div class="stat-label">Pings Today</div>
                    <div class="stat-badge cyan"><i class="bi bi-geo"></i> GPS Points</div>
                </div>
            </div>

            <!-- Live Map -->
            <div class="map-card">
                <div class="map-header">
                    <span class="map-title"><i class="bi bi-map" style="color:var(--primary)"></i> Live Driver
                        Map</span>
                    <span style="font-size:.8rem;color:var(--muted);">Auto-refresh every 5s</span>
                </div>
                <div id="liveMap"></div>
            </div>

            <!-- Active Deliveries List -->
            <div class="section-card mb-4">
                <div class="section-header">
                    <span class="section-title"><i class="bi bi-truck" style="color:var(--primary)"></i> Active Deliveries</span>
                    <span class="badge bg-primary"><?= count($activeDeliveriesList) ?> Running</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Started At</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeDeliveriesList)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No active deliveries at the moment.</td></tr>
                        <?php else: ?>
                                <?php foreach ($activeDeliveriesList as $del): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($del['name']) ?></strong></td>
                                            <td><?= date('H:i:s', strtotime($del['started_at'])) ?></td>
                                            <td>
                                                <span class="text-muted" style="font-size: .8rem;">
                                                    <?php
                                                    $diff = time() - strtotime($del['started_at']);
                                                    echo floor($diff / 3600) . "h " . floor(($diff % 3600) / 60) . "m";
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="map.php?driver_id=<?= $del['driver_id'] ?>" class="btn-sm-action btn-track">
                                                    <i class="bi bi-geo-alt"></i> View on Map
                                                </a>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Driver List -->
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title"><i class="bi bi-people" style="color:var(--accent)"></i> Driver
                        Overview</span>
                    <button class="btn-sm-action btn-track" onclick="openAddModal()">+ Add Driver</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="driversTable">
                        <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="user-avatar" style="width:32px;height:32px;font-size:.75rem;">
                                            <?= strtoupper(substr($driver['name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($driver['name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($driver['email']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($driver['phone'] ?? '—') ?>
                                </td>
                                <td>
                                    <div class="status-dot">
                                        <div class="dot <?= $driver['status'] ?>"></div>
                                        <?= ucfirst($driver['status']) ?>
                                        <?php if ($driver['active_delivery_id']): ?>
                                            <span class="badge bg-primary ms-2"
                                                style="font-size: .65rem; padding: 2px 6px;">DELIVERING</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button class="btn-sm-action btn-track" onclick="trackDriver(<?= $driver['id'] ?>)">
                                        <i class="bi bi-geo-alt"></i> Track
                                    </button>
                                    <button class="btn-sm-action btn-edit"
                                        onclick="editDriver(<?= $driver['id'] ?>, '<?= addslashes($driver['name']) ?>', '<?= $driver['email'] ?>', '<?= $driver['phone'] ?? '' ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn-sm-action btn-del" onclick="deleteDriver(<?= $driver['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Driver Modal -->
    <div id="driverModal">
        <div class="modal-box">
            <h3 id="modalTitle">Add Driver</h3>
            <form id="driverForm">
                <input type="hidden" id="driverId" name="id" value="">
                <label>Full Name</label>
                <input type="text" id="driverName" name="name" placeholder="John Doe" required>
                <label>Email</label>
                <input type="email" id="driverEmail" name="email" placeholder="john@example.com" required>
                <label>Phone</label>
                <input type="text" id="driverPhone" name="phone" placeholder="+1 234 567 890">
                <label>Password <span id="passHint" style="color:var(--muted);font-weight:400;">(leave blank to
                        keep)</span></label>
                <input type="password" id="driverPassword" name="password" placeholder="••••••••">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Driver</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Clock
        function updateClock() {
            document.getElementById('clock').textContent = new Date().toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Map
        const map = L.map('liveMap').setView([11.5564, 104.9282], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors', maxZoom: 19
        }).addTo(map);

        const driverMarkers = {};
        const driverPaths = {};

        function createIcon(name, online) {
            const color = online ? '#10b981' : '#64748b';
            const initial = name ? name[0].toUpperCase() : '?';
            return L.divIcon({
                className: '',
                html: `<div style="
            background:${color};
            width:36px;height:36px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-weight:700;font-size:14px;
            box-shadow:0 2px 10px rgba(0,0,0,.4);
            border:2px solid #fff;
        ">${initial}</div>`,
                iconSize: [36, 36], iconAnchor: [18, 18], popupAnchor: [0, -20]
            });
        }

        function fetchLocations() {
            fetch('../api/get_all_locations.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    data.drivers.forEach(d => {
                        if (!d.lat || !d.lng) return;
                        const latlng = [parseFloat(d.lat), parseFloat(d.lng)];
                        const icon = createIcon(d.name, d.status === 'online');

                        if (driverMarkers[d.id]) {
                            driverMarkers[d.id].setLatLng(latlng).setIcon(icon);
                        } else {
                            driverMarkers[d.id] = L.marker(latlng, { icon })
                                .addTo(map)
                                .bindPopup(`<b>${d.name}</b><br>Status: ${d.status}<br>Last seen: ${d.last_seen}`);
                        }
                        driverMarkers[d.id].setPopupContent(
                            `<b>${d.name}</b> ${d.on_delivery ? '<span style="background:var(--primary);color:#fff;padding:1px 4px;border-radius:3px;font-size:10px;margin-left:5px">DELIVERING</span>' : ''}<br>Status: ${d.status}<br>Speed: ${d.speed || '0'} km/h<br>${d.last_seen}`
                        );
                    });
                    // Update stats
                    fetch('../api/stats.php')
                        .then(r => r.json())
                        .then(s => {
                            document.getElementById('statOnline').textContent = s.online || 0;
                            document.getElementById('statDeliveries').textContent = s.active_deliveries || 0;
                            document.getElementById('statLocs').textContent = s.today_pings || 0;
                        });
                });
        }

        fetchLocations();
        setInterval(fetchLocations, 5000);

        function trackDriver(id) {
            window.location.href = 'map.php?driver_id=' + id;
        }

        // Modal
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Driver';
            document.getElementById('driverId').value = '';
            document.getElementById('driverName').value = '';
            document.getElementById('driverEmail').value = '';
            document.getElementById('driverPhone').value = '';
            document.getElementById('driverPassword').value = '';
            document.getElementById('passHint').style.display = 'none';
            document.getElementById('driverModal').classList.add('show');
        }
        function editDriver(id, name, email, phone) {
            document.getElementById('modalTitle').textContent = 'Edit Driver';
            document.getElementById('driverId').value = id;
            document.getElementById('driverName').value = name;
            document.getElementById('driverEmail').value = email;
            document.getElementById('driverPhone').value = phone;
            document.getElementById('driverPassword').value = '';
            document.getElementById('passHint').style.display = 'inline';
            document.getElementById('driverModal').classList.add('show');
        }
        function closeModal() {
            document.getElementById('driverModal').classList.remove('show');
        }
        document.getElementById('driverForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('../api/save_driver.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) { closeModal(); location.reload(); }
                    else alert(res.message || 'Error saving driver');
                });
        });
        function deleteDriver(id) {
            if (!confirm('Delete this driver? This is irreversible.')) return;
            fetch('../api/delete_driver.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            }).then(r => r.json()).then(res => {
                if (res.success) location.reload();
                else alert(res.message);
            });
        }

        // Responsive
        if (window.innerWidth < 768) {
            document.getElementById('menuBtn').style.display = 'block';
        }
    </script>
</body>

</html>