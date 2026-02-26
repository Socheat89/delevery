<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
requireDriver();

$db = getDB();
$userId = $_SESSION['user_id'];

// Check active delivery
$delivery = $db->prepare("SELECT * FROM deliveries WHERE user_id=? AND status='active' LIMIT 1");
$delivery->execute([$userId]);
$activeDelivery = $delivery->fetch();

// Today's distance (approximate using Haversine on PHP side would be complex, rough count)
$pings = $db->prepare("SELECT COUNT(*) FROM locations WHERE user_id=? AND DATE(created_at)=CURDATE()");
$pings->execute([$userId]);
$todayPings = $pings->fetchColumn();

// Last location
$lastLoc = $db->prepare("SELECT * FROM locations WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$lastLoc->execute([$userId]);
$lastLocation = $lastLoc->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Driver – DeliTrack</title>
    <meta name="theme-color" content="#0f0f1a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="../manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #22d3ee;
            --green: #10b981;
            --red: #ef4444;
            --yellow: #f59e0b;
            --bg: #0f0f1a;
            --surface: #1a1a2e;
            --surface2: #16213e;
            --border: rgba(255, 255, 255, .07);
            --text: #e2e8f0;
            --muted: #64748b;
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
            min-height: 100vh;
            padding-bottom: calc(env(safe-area-inset-bottom) + 80px);
        }

        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .header-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
        }

        .header-sub {
            font-size: .72rem;
            color: var(--muted);
        }

        .logout-btn {
            background: rgba(239, 68, 68, .12);
            color: var(--red);
            border: none;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .content {
            padding: 20px;
            max-width: 480px;
            margin: 0 auto;
        }

        /* Status Banner */
        .status-banner {
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all .4s ease;
        }

        .status-banner.offline {
            background: rgba(100, 116, 139, .1);
            border: 1px solid rgba(100, 116, 139, .2);
        }

        .status-banner.online {
            background: rgba(16, 185, 129, .08);
            border: 1px solid rgba(16, 185, 129, .25);
        }

        .status-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .status-banner.offline .status-icon {
            background: rgba(100, 116, 139, .15);
        }

        .status-banner.online .status-icon {
            background: rgba(16, 185, 129, .15);
        }

        .status-text h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        .status-text p {
            font-size: .82rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-left: auto;
        }

        .status-dot.online {
            background: var(--green);
            box-shadow: 0 0 10px var(--green);
            animation: pulse 1.8s infinite;
        }

        .status-dot.offline {
            background: var(--muted);
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1
            }

            50% {
                transform: scale(1.3);
                opacity: .6
            }
        }

        /* Big action buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }

        .btn-action {
            border: none;
            border-radius: 16px;
            padding: 22px 16px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity .2s;
            background: rgba(255, 255, 255, .1);
        }

        .btn-action:hover::before {
            opacity: 1;
        }

        .btn-action:active {
            transform: scale(.97);
        }

        .btn-action i {
            font-size: 2rem;
        }

        .btn-start {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            grid-column: 1 / -1;
            font-size: 1.2rem;
        }

        .btn-stop {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            grid-column: 1 / -1;
        }

        .btn-secondary {
            background: rgba(99, 102, 241, .12);
            color: var(--primary);
            border: 1px solid rgba(99, 102, 241, .25);
        }

        /* Map */
        .map-card {
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }

        .map-header {
            background: var(--surface);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .map-header-title {
            font-size: .9rem;
            font-weight: 700;
            color: #fff;
        }

        #driverMap {
            height: 280px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }

        .info-label {
            font-size: .7rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            margin-top: 4px;
        }

        .info-sub {
            font-size: .72rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* GPS accuracy bar */
        .accuracy-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 20px;
        }

        .acc-label {
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .acc-track {
            background: rgba(255, 255, 255, .05);
            border-radius: 100px;
            height: 6px;
            overflow: hidden;
        }

        .acc-fill {
            height: 100%;
            border-radius: 100px;
            transition: width .5s;
        }

        /* Timer */
        .timer-badge {
            background: rgba(99, 102, 241, .1);
            border: 1px solid rgba(99, 102, 241, .2);
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .timer-label {
            font-size: .8rem;
            color: var(--muted);
        }

        .timer-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            font-variant-numeric: tabular-nums;
        }

        /* Notification toast */
        .toast-notification {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 20px;
            font-size: .88rem;
            color: #fff;
            z-index: 999;
            display: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
            white-space: nowrap;
        }

        .toast-notification.show {
            display: block;
            animation: slideDown .3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px)
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0)
            }
        }
    </style>
</head>

<body>

    <div class="toast-notification" id="toast"></div>

    <!-- Header -->
    <div class="header">
        <div class="header-brand">
            <div class="header-icon">🚚</div>
            <div>
                <div class="header-title">DeliTrack</div>
                <div class="header-sub">Driver Panel –
                    <?= htmlspecialchars($_SESSION['name']) ?>
                </div>
            </div>
        </div>
        <a href="../api/logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i></a>
    </div>

    <div class="content">

        <!-- Status Banner -->
        <div class="status-banner <?= $activeDelivery ? 'online' : 'offline' ?>" id="statusBanner">
            <div class="status-icon">
                <?= $activeDelivery ? '📡' : '😴' ?>
            </div>
            <div class="status-text">
                <h3 id="statusTitle">
                    <?= $activeDelivery ? 'Delivery Active' : 'Offline' ?>
                </h3>
                <p id="statusDesc">
                    <?= $activeDelivery ? 'Transmitting GPS location…' : 'Press Start to begin tracking' ?>
                </p>
            </div>
            <div class="status-dot <?= $activeDelivery ? 'online' : 'offline' ?>" id="statusDot"></div>
        </div>

        <!-- Timer -->
        <div class="timer-badge" id="timerBadge" style="<?= !$activeDelivery ? 'display:none' : '' ?>">
            <span class="timer-label">⏱ Session Duration</span>
            <span class="timer-value" id="timerDisplay">00:00:00</span>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <?php if (!$activeDelivery): ?>
                <button class="btn-action btn-start" id="btnStart" onclick="startDelivery()">
                    <i class="bi bi-play-circle-fill"></i>
                    Start Delivery
                </button>
            <?php else: ?>
                <button class="btn-action btn-stop" id="btnStop" onclick="stopDelivery()">
                    <i class="bi bi-stop-circle-fill"></i>
                    Stop Delivery
                </button>
            <?php endif; ?>
            <button class="btn-action btn-secondary" onclick="forceLocationUpdate()">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Refresh Location</span>
            </button>
            <button class="btn-action btn-secondary" onclick="window.location.href='../admin/map.php'">
                <i class="bi bi-map-fill"></i>
                <span>View Map</span>
            </button>
        </div>

        <!-- GPS Accuracy -->
        <div class="accuracy-bar">
            <div class="acc-label">GPS Signal Accuracy: <span id="accText">Getting location…</span></div>
            <div class="acc-track">
                <div class="acc-fill" id="accFill" style="width:0%;background:#ef4444;"></div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">📍 Latitude</div>
                <div class="info-value" id="dispLat">—</div>
                <div class="info-sub">Current position</div>
            </div>
            <div class="info-card">
                <div class="info-label">📍 Longitude</div>
                <div class="info-value" id="dispLng">—</div>
                <div class="info-sub">Current position</div>
            </div>
            <div class="info-card">
                <div class="info-label">🚀 Speed</div>
                <div class="info-value" id="dispSpeed">—</div>
                <div class="info-sub">km/h</div>
            </div>
            <div class="info-card">
                <div class="info-label">📡 Pings Today</div>
                <div class="info-value" id="dispPings">
                    <?= $todayPings ?>
                </div>
                <div class="info-sub">Location sent</div>
            </div>
        </div>

        <!-- Map -->
        <div class="map-card">
            <div class="map-header">
                <span class="map-header-title"><i class="bi bi-map"></i> My Location</span>
                <span id="lastUpdateLabel" style="font-size:.75rem;color:var(--muted);">—</span>
            </div>
            <div id="driverMap"></div>
        </div>

    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // ── State ──────────────────────────────────────────────────────────────────
        let isTracking = <?= $activeDelivery ? 'true' : 'false' ?>;
        let deliveryId = <?= $activeDelivery ? $activeDelivery['id'] : 'null' ?>;
        let watchId = null;
        let sendInterval = null;
        let timerInterval = null;
        let pingCount = <?= (int) $todayPings ?>;
        let sessionStart = <?= $activeDelivery ? '"' . $activeDelivery['started_at'] . '"' : 'null' ?>;

        let currentLat = <?= $lastLocation ? $lastLocation['latitude'] : '11.5564' ?>;
        let currentLng = <?= $lastLocation ? $lastLocation['longitude'] : '104.9282' ?>;

        // ── Map ────────────────────────────────────────────────────────────────────
        const map = L.map('driverMap').setView([currentLat, currentLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);

        const myIcon = L.divIcon({
            className: '',
            html: `<div style="background:#6366f1;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;box-shadow:0 2px 14px rgba(99,102,241,.6);border:3px solid rgba(255,255,255,.8);">🚚</div>`,
            iconSize: [42, 42], iconAnchor: [21, 21]
        });
        let myMarker = L.marker([currentLat, currentLng], { icon: myIcon }).addTo(map);
        let pathLine = L.polyline([], { color: '#6366f1', weight: 4, opacity: .7 }).addTo(map);

        // ── Toast ──────────────────────────────────────────────────────────────────
        function showToast(msg, type = 'info') {
            const t = document.getElementById('toast');
            const colors = { info: '#6366f1', success: '#10b981', error: '#ef4444' };
            t.style.borderColor = colors[type] || colors.info;
            t.textContent = msg; t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3200);
        }

        // ── Timer ──────────────────────────────────────────────────────────────────
        function formatDuration(secs) {
            const h = Math.floor(secs / 3600), m = Math.floor((secs % 3600) / 60), s = secs % 60;
            return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
        }
        function startTimer() {
            const start = sessionStart ? new Date(sessionStart) : new Date();
            timerInterval = setInterval(() => {
                const diff = Math.floor((Date.now() - start.getTime()) / 1000);
                document.getElementById('timerDisplay').textContent = formatDuration(diff);
            }, 1000);
        }
        if (isTracking && sessionStart) startTimer();

        // ── GPS & Sending ─────────────────────────────────────────────────────────
        function updateAccuracyBar(acc) {
            const el = document.getElementById('accFill');
            const txt = document.getElementById('accText');
            if (acc === null) { el.style.width = '0%'; el.style.background = '#ef4444'; txt.textContent = 'No signal'; return; }
            const pct = Math.max(0, Math.min(100, 100 - Math.log10(acc + 1) * 40));
            el.style.width = pct + '%';
            if (pct > 70) { el.style.background = '#10b981'; txt.textContent = 'Excellent (±' + Math.round(acc) + 'm)'; }
            else if (pct > 40) { el.style.background = '#f59e0b'; txt.textContent = 'Good (±' + Math.round(acc) + 'm)'; }
            else { el.style.background = '#ef4444'; txt.textContent = 'Weak (±' + Math.round(acc) + 'm)'; }
        }

        function sendLocation(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const speed = pos.coords.speed ? (pos.coords.speed * 3.6).toFixed(1) : null;
            const acc = pos.coords.accuracy;

            // Update UI
            currentLat = lat; currentLng = lng;
            document.getElementById('dispLat').textContent = lat.toFixed(6);
            document.getElementById('dispLng').textContent = lng.toFixed(6);
            document.getElementById('dispSpeed').textContent = speed || '0';
            document.getElementById('lastUpdateLabel').textContent = 'Updated: ' + new Date().toLocaleTimeString();
            updateAccuracyBar(acc);

            // Move marker
            const ll = [lat, lng];
            myMarker.setLatLng(ll);
            map.panTo(ll);
            const pts = pathLine.getLatLngs();
            pts.push(L.latLng(lat, lng));
            pathLine.setLatLngs(pts);

            if (!isTracking) return;

            // Send to server
            fetch('../api/update_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ latitude: lat, longitude: lng, speed: speed || '', accuracy: acc })
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    pingCount++;
                    document.getElementById('dispPings').textContent = pingCount;
                }
            }).catch(() => { });
        }

        function startGPS() {
            if (!navigator.geolocation) { showToast('GPS not supported on this device', 'error'); return; }
            watchId = navigator.geolocation.watchPosition(
                sendLocation,
                err => { showToast('GPS error: ' + err.message, 'error'); },
                { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
            );
            sendInterval = setInterval(() => {
                navigator.geolocation.getCurrentPosition(sendLocation, () => { }, { enableHighAccuracy: true, timeout: 10000 });
            }, 12000);
        }

        function stopGPS() {
            if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
            if (sendInterval !== null) { clearInterval(sendInterval); sendInterval = null; }
        }

        // ── Delivery start/stop ───────────────────────────────────────────────────
        async function startDelivery() {
            const btn = document.getElementById('btnStart');
            btn.disabled = true; btn.textContent = 'Starting…';
            const res = await fetch('../api/start_delivery.php', { method: 'POST' }).then(r => r.json());
            if (res.success) {
                isTracking = true; deliveryId = res.delivery_id; sessionStart = new Date().toISOString();
                setOnlineUI();
                startGPS();
                startTimer();
                showToast('✅ Delivery started! Tracking active.', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                btn.disabled = false; btn.textContent = 'Start Delivery';
                showToast(res.message || 'Failed to start', 'error');
            }
        }

        async function stopDelivery() {
            if (!confirm('Stop delivery and go offline?')) return;
            const btn = document.getElementById('btnStop');
            btn.disabled = true; btn.textContent = 'Stopping…';
            await fetch('../api/stop_delivery.php', { method: 'POST' });
            isTracking = false;
            stopGPS();
            clearInterval(timerInterval);
            showToast('🛑 Delivery stopped.', 'info');
            setTimeout(() => location.reload(), 800);
        }

        function forceLocationUpdate() {
            if (!navigator.geolocation) { showToast('GPS unavailable', 'error'); return; }
            showToast('📡 Getting fresh location…', 'info');
            navigator.geolocation.getCurrentPosition(pos => {
                sendLocation(pos);
                showToast('📍 Location updated!', 'success');
            }, () => showToast('Could not get location', 'error'), { enableHighAccuracy: true, timeout: 12000 });
        }

        function setOnlineUI() {
            const b = document.getElementById('statusBanner');
            b.className = 'status-banner online';
            document.getElementById('statusTitle').textContent = 'Delivery Active';
            document.getElementById('statusDesc').textContent = 'Transmitting GPS location…';
            document.getElementById('statusDot').className = 'status-dot online';
            document.getElementById('timerBadge').style.display = 'flex';
        }

        // Auto-start GPS if delivery is active on page load
        if (isTracking) { startGPS(); }

        // Initial location fetch
        navigator.geolocation && navigator.geolocation.getCurrentPosition(pos => {
            document.getElementById('dispLat').textContent = pos.coords.latitude.toFixed(6);
            document.getElementById('dispLng').textContent = pos.coords.longitude.toFixed(6);
            const ll = [pos.coords.latitude, pos.coords.longitude];
            myMarker.setLatLng(ll);
            map.setView(ll, 15);
            updateAccuracyBar(pos.coords.accuracy);
        }, {}, { enableHighAccuracy: true, timeout: 8000 });
    </script>
</body>

</html>