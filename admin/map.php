<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$db = getDB();
$focusDriverId = isset($_GET['driver_id']) ? (int) $_GET['driver_id'] : 0;

// Drivers list for filter dropdown
$drivers = $db->query("SELECT id, name, status FROM users WHERE role='driver' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Map – DeliTrack</title>
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
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .topbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
        }

        /* Fullscreen map */
        #fullMap {
            flex: 1;
        }

        /* Floating control panel */
        .map-controls {
            position: absolute;
            top: 80px;
            right: 20px;
            z-index: 400;
            width: 280px;
        }

        .ctrl-card {
            background: rgba(15, 15, 26, .92);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            backdrop-filter: blur(16px);
            margin-bottom: 12px;
        }

        .ctrl-title {
            font-size: .8rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
        }

        .driver-filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background .15s;
            margin-bottom: 4px;
        }

        .driver-filter-item:hover {
            background: rgba(255, 255, 255, .05);
        }

        .driver-filter-item.selected {
            background: rgba(99, 102, 241, .12);
        }

        .driver-name-label {
            font-size: .85rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .dot.online {
            background: var(--green);
            box-shadow: 0 0 5px var(--green);
        }

        .dot.offline {
            background: var(--muted);
        }

        .select-all-btn {
            width: 100%;
            background: rgba(99, 102, 241, .15);
            border: none;
            color: var(--primary);
            border-radius: 8px;
            padding: 7px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 8px;
            transition: background .2s;
        }

        .select-all-btn:hover {
            background: var(--primary);
            color: #fff;
        }

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

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .4
            }
        }

        /* Route line legend */
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .legend-line {
            width: 24px;
            height: 3px;
            border-radius: 2px;
        }

        @media(max-width:768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main {
                margin-left: 0;
            }

            .map-controls {
                width: 200px;
                top: 70px;
                right: 10px;
            }
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
            <a href="map.php" class="nav-item active"><i class="bi bi-map"></i> Live Map</a>
            <div class="nav-label">Management</div>
            <a href="drivers.php" class="nav-item"><i class="bi bi-people"></i> Drivers</a>
            <a href="history.php" class="nav-item"><i class="bi bi-clock-history"></i> Route History</a>
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
            <a href="../api/logout.php" style="color:var(--red,#ef4444);font-size:.82rem;text-decoration:none;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    <div class="main" style="position:relative;">
        <div class="topbar">
            <span class="topbar-title"><i class="bi bi-map" style="color:var(--primary)"></i> Live Map Tracking</span>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="live-badge">
                    <div class="live-dot"></div> LIVE
                </div>
                <span style="font-size:.82rem;color:var(--muted);" id="clock"></span>
            </div>
        </div>

        <div id="fullMap"></div>

        <!-- Floating Controls -->
        <div class="map-controls">
            <div class="ctrl-card">
                <div class="ctrl-title">Driver Filter</div>
                <button class="select-all-btn" id="selectAllBtn">Select All</button>
                <div id="driverList">
                    <?php foreach ($drivers as $d): ?>
                        <div class="driver-filter-item <?= $focusDriverId == $d['id'] || !$focusDriverId ? 'selected' : '' ?>"
                            data-id="<?= $d['id'] ?>" onclick="toggleDriver(this)">
                            <div class="driver-name-label">
                                <div class="dot <?= $d['status'] ?>"></div>
                                <?= htmlspecialchars($d['name']) ?>
                            </div>
                            <i
                                class="bi bi-eye <?= $focusDriverId == $d['id'] || !$focusDriverId ? 'text-primary' : 'text-muted' ?>"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="ctrl-card">
                <div class="ctrl-title">Legend</div>
                <div class="legend-item">
                    <div class="legend-line" style="background:#10b981"></div> Online Driver
                </div>
                <div class="legend-item">
                    <div class="legend-line" style="background:#64748b"></div> Offline Driver
                </div>
                <div class="legend-item">
                    <div class="legend-line"
                        style="background:#6366f1;border-top:2px dashed #6366f1;height:0;margin:4px 0;"></div> Route
                    Path
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function updateClock() { document.getElementById('clock').textContent = new Date().toLocaleTimeString(); }
        setInterval(updateClock, 1000); updateClock();

        const map = L.map('fullMap').setView([11.5564, 104.9282], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);

        const markers = {}, paths = {}, selectedDrivers = new Set();
        const focusId = <?= $focusDriverId ?>;

        // Init selected set
        document.querySelectorAll('.driver-filter-item.selected').forEach(el => {
            selectedDrivers.add(parseInt(el.dataset.id));
        });
        if (focusId) { selectedDrivers.clear(); selectedDrivers.add(focusId); }
        if (selectedDrivers.size === 0) {
            document.querySelectorAll('.driver-filter-item').forEach(el => selectedDrivers.add(parseInt(el.dataset.id)));
        }

        function toggleDriver(el) {
            const id = parseInt(el.dataset.id);
            if (selectedDrivers.has(id)) {
                selectedDrivers.delete(id); el.classList.remove('selected');
            } else {
                selectedDrivers.add(id); el.classList.add('selected');
            }
            updateVisibility();
        }
        document.getElementById('selectAllBtn').onclick = function () {
            document.querySelectorAll('.driver-filter-item').forEach(el => {
                const id = parseInt(el.dataset.id);
                selectedDrivers.add(id); el.classList.add('selected');
            });
            updateVisibility();
        };
        function updateVisibility() {
            Object.keys(markers).forEach(id => {
                if (selectedDrivers.has(parseInt(id))) {
                    if (!map.hasLayer(markers[id])) markers[id].addTo(map);
                } else {
                    if (map.hasLayer(markers[id])) map.removeLayer(markers[id]);
                }
            });
        }

        function createIcon(name, online) {
            const color = online ? '#10b981' : '#64748b';
            const init = name ? name[0].toUpperCase() : '?';
            return L.divIcon({
                className: '',
                html: `<div style="background:${color};width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;box-shadow:0 2px 12px rgba(0,0,0,.5);border:2px solid rgba(255,255,255,.8);">${init}</div>`,
                iconSize: [38, 38], iconAnchor: [19, 19], popupAnchor: [0, -22]
            });
        }

        function fetchAndUpdate() {
            fetch('../api/get_all_locations.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    data.drivers.forEach(d => {
                        if (!d.lat || !d.lng) return;
                        const ll = [parseFloat(d.lat), parseFloat(d.lng)];
                        const icon = createIcon(d.name, d.status === 'online');
                        if (markers[d.id]) {
                            markers[d.id].setLatLng(ll).setIcon(icon);
                        } else {
                            markers[d.id] = L.marker(ll, { icon }).addTo(map);
                        }
                        markers[d.id].bindPopup(`<div style="font-family:Inter,sans-serif;min-width:160px">
                    <b style="font-size:1rem">${d.name}</b><br>
                    <span style="color:${d.status === 'online' ? '#10b981' : '#94a3b8'}">● ${d.status}</span><br>
                    Speed: ${d.speed || '—'} km/h<br>
                    Last ping: ${d.last_seen}
                </div>`);
                        // Draw path
                        if (d.path && d.path.length > 1) {
                            if (paths[d.id]) map.removeLayer(paths[d.id]);
                            const pts = d.path.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
                            paths[d.id] = L.polyline(pts, { color: '#6366f1', weight: 3, opacity: .7, dashArray: '6,4' }).addTo(map);
                        }
                    });
                    updateVisibility();
                    if (focusId && markers[focusId]) map.panTo(markers[focusId].getLatLng());
                });
        }
        fetchAndUpdate();
        setInterval(fetchAndUpdate, 5000);
    </script>
</body>

</html>