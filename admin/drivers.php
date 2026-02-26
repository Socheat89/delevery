<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$db = getDB();
$drivers = $db->query("SELECT id,name,email,phone,status,created_at FROM users WHERE role='driver' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drivers – DeliTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #22d3ee;
            --green: #10b981;
            --red: #ef4444;
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

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
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
                opacity: 1
            }

            50% {
                opacity: .4
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

        .search-bar {
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            padding: 8px 14px;
            font-size: .88rem;
            width: 220px;
        }

        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Modal */
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
            background: #16213e;
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
            <a href="drivers.php" class="nav-item active"><i class="bi bi-people"></i> Drivers</a>
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
            <a href="../api/logout.php" style="color:#ef4444;font-size:.82rem;text-decoration:none;"><i
                    class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </nav>

    <div class="main">
        <div class="topbar">
            <span class="topbar-title"><i class="bi bi-people" style="color:var(--primary)"></i> Driver
                Management</span>
            <div style="display:flex;gap:10px;">
                <input type="text" class="search-bar" id="searchInput" placeholder="🔍 Search drivers…"
                    oninput="filterTable()">
                <button class="btn-sm-action btn-track" onclick="openAddModal()">+ Add Driver</button>
            </div>
        </div>
        <div class="content">
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">All Drivers <span
                            style="color:var(--muted);font-size:.85rem;font-weight:400;">(
                            <?= count($drivers) ?>)
                        </span></span>
                </div>
                <table id="driversTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Driver</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drivers as $i => $d): ?>
                            <tr>
                                <td style="color:var(--muted)">
                                    <?= $i + 1 ?>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="user-avatar" style="width:32px;height:32px;font-size:.75rem;">
                                            <?= strtoupper(substr($d['name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($d['name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($d['email']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($d['phone'] ?? '—') ?>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <div class="dot <?= $d['status'] ?>"></div>
                                        <?= ucfirst($d['status']) ?>
                                    </div>
                                </td>
                                <td style="color:var(--muted)">
                                    <?= date('M d, Y', strtotime($d['created_at'])) ?>
                                </td>
                                <td style="display:flex;gap:6px;">
                                    <a href="map.php?driver_id=<?= $d['id'] ?>" class="btn-sm-action btn-track"><i
                                            class="bi bi-geo-alt"></i> Track</a>
                                    <button class="btn-sm-action btn-edit"
                                        onclick="editDriver(<?= $d['id'] ?>,'<?= addslashes($d['name']) ?>','<?= $d['email'] ?>','<?= $d['phone'] ?? '' ?>')"><i
                                            class="bi bi-pencil"></i></button>
                                    <button class="btn-sm-action btn-del" onclick="deleteDriver(<?= $d['id'] ?>)"><i
                                            class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="driverModal">
        <div class="modal-box">
            <h3 id="modalTitle">Add Driver</h3>
            <form id="driverForm">
                <input type="hidden" id="driverId" name="id">
                <label>Full Name</label><input type="text" id="driverName" name="name" required>
                <label>Email</label><input type="email" id="driverEmail" name="email" required>
                <label>Phone</label><input type="text" id="driverPhone" name="phone">
                <label>Password <span id="passHint" style="color:var(--muted);font-weight:400;">(leave blank to
                        keep)</span></label>
                <input type="password" id="driverPassword" name="password">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterTable() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('#driversTable tbody tr').forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Driver';
            document.getElementById('driverId').value = '';
            ['driverName', 'driverEmail', 'driverPhone', 'driverPassword'].forEach(id => document.getElementById(id).value = '');
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
        function closeModal() { document.getElementById('driverModal').classList.remove('show'); }
        document.getElementById('driverForm').addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('../api/save_driver.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json()).then(res => { if (res.success) { closeModal(); location.reload(); } else alert(res.message); })
                .catch(err => alert("Error: Could not connect to API. Please refresh the page."));
        });
        function deleteDriver(id) {
            if (!confirm('Delete this driver?')) return;
            fetch('../api/delete_driver.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'id=' + id })
                .then(r => r.json()).then(res => { if (res.success) location.reload(); else alert(res.message); })
                .catch(err => alert("Error deleting driver."));
        }
    </script>
</body>

</html>