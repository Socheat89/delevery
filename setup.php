<?php
/**
 * Setup Script - Run once to initialize the database
 * URL: http://localhost/location/setup.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'samann1_admin_panel');
define('DB_PASS', 'admin_panel@2025');
define('DB_NAME', 'samann1_admin_panel');

$errors = [];
$messages = [];

try {
    // Connect without selecting a database first
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = "✅ Database <strong>" . DB_NAME . "</strong> created / already exists.";

    // Select the database
    $pdo->exec("USE `" . DB_NAME . "`");

    // Helper function to add column if it doesn't exist
    function addColumnIfMissing($pdo, $table, $column, $definition)
    {
        $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
            return true;
        }
        return false;
    }

    // ── users table ──────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `username`   VARCHAR(100) NOT NULL DEFAULT '' UNIQUE,
        `name`       VARCHAR(100) NOT NULL,
        `email`      VARCHAR(150) NOT NULL UNIQUE,
        `password`   VARCHAR(255) NOT NULL,
        `role`       ENUM('admin','driver') NOT NULL DEFAULT 'driver',
        `status`     ENUM('online','offline') NOT NULL DEFAULT 'offline',
        `phone`      VARCHAR(20)  NULL,
        `avatar`     VARCHAR(255) NULL,
        `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_role`   (`role`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure all columns exist in users (migration for existing table)
    addColumnIfMissing($pdo, 'users', 'username', "VARCHAR(100) NOT NULL DEFAULT '' AFTER `id` ");
    try {
        $pdo->exec("ALTER TABLE `users` ADD UNIQUE (`username`)");
    } catch (Exception $e) {
        // Index already exists or other non-critical error
    }
    addColumnIfMissing($pdo, 'users', 'status', "ENUM('online','offline') NOT NULL DEFAULT 'offline' AFTER `role` ");
    addColumnIfMissing($pdo, 'users', 'phone', "VARCHAR(20) NULL AFTER `status` ");
    addColumnIfMissing($pdo, 'users', 'avatar', "VARCHAR(255) NULL AFTER `phone` ");
    addColumnIfMissing($pdo, 'users', 'created_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");

    $messages[] = "✅ Table <strong>users</strong> checked & updated.";

    // ── locations table ───────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `locations` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`    INT UNSIGNED NOT NULL,
        `latitude`   VARCHAR(30)  NOT NULL,
        `longitude`  VARCHAR(30)  NOT NULL,
        `speed`      VARCHAR(20)  NULL,
        `heading`    VARCHAR(20)  NULL,
        `accuracy`   VARCHAR(20)  NULL,
        `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_user_id`   (`user_id`),
        INDEX `idx_created_at`(`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure columns exist in locations (comprehensive migration)
    addColumnIfMissing($pdo, 'locations', 'user_id', "INT UNSIGNED NOT NULL AFTER `id` ");
    addColumnIfMissing($pdo, 'locations', 'latitude', "VARCHAR(30) NOT NULL AFTER `user_id` ");
    addColumnIfMissing($pdo, 'locations', 'longitude', "VARCHAR(30) NOT NULL AFTER `latitude` ");
    addColumnIfMissing($pdo, 'locations', 'speed', "VARCHAR(20) NULL AFTER `longitude` ");
    addColumnIfMissing($pdo, 'locations', 'heading', "VARCHAR(20) NULL AFTER `speed` ");
    addColumnIfMissing($pdo, 'locations', 'accuracy', "VARCHAR(20) NULL AFTER `heading` ");
    addColumnIfMissing($pdo, 'locations', 'created_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");

    $messages[] = "✅ Table <strong>locations</strong> checked & updated.";

    // ── deliveries table ──────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `deliveries` (
        `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`      INT UNSIGNED NOT NULL,
        `started_at`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ended_at`     DATETIME    NULL,
        `status`       ENUM('active','completed') NOT NULL DEFAULT 'active',
        `notes`        TEXT NULL,
        `created_at`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_status`  (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $messages[] = "✅ Table <strong>deliveries</strong> created / already exists.";

    // ── Seed default admin ────────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
    $stmt->execute(['admin@delivery.com']);
    if ((int) $stmt->fetchColumn() === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare("INSERT INTO `users` (username, name, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
        $ins->execute(['admin', 'Super Admin', 'admin@delivery.com', $hash]);
        $messages[] = "✅ Default admin created – <strong>admin@delivery.com</strong> / <strong>admin123</strong>";
    } else {
        $messages[] = "ℹ️ Admin account already exists.";
    }

    // ── Seed sample drivers ───────────────────────────────────────────────────
    $sampleDrivers = [
        ['John Doe', 'john@delivery.com', 'driver123'],
        ['Jane Smith', 'jane@delivery.com', 'driver123'],
        ['Bob Johnson', 'bob@delivery.com', 'driver123'],
    ];
    foreach ($sampleDrivers as [$name, $email, $pass]) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        if ((int) $stmt->fetchColumn() === 0) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $username = strtolower(explode('@', $email)[0]); // e.g. john@delivery.com → 'john'
            $ins = $pdo->prepare("INSERT INTO `users` (username, name, email, password, role) VALUES (?, ?, ?, ?, 'driver')");
            $ins->execute([$username, $name, $email, $hash]);
            $messages[] = "✅ Sample driver created – <strong>$email</strong> / <strong>$pass</strong>";
        }
    }

    $setupDone = true;

} catch (PDOException $e) {
    $errors[] = "❌ Error: " . htmlspecialchars($e->getMessage());
    $setupDone = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup – DeliTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }

        .setup-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            max-width: 640px;
            margin: 80px auto;
            padding: 40px;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #38bdf8;
        }

        .msg {
            background: rgba(56, 189, 248, .08);
            border-left: 3px solid #38bdf8;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: .9rem;
        }

        .err {
            background: rgba(239, 68, 68, .08);
            border-left: 3px solid #ef4444;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: .9rem;
            color: #fca5a5;
        }
    </style>
</head>

<body>
    <div class="setup-card">
        <h1 class="mb-1">🚚 DeliTrack Setup</h1>
        <p class="text-muted mb-4">Initializing database for the first time…</p>

        <?php foreach ($messages as $m): ?>
            <div class="msg">
                <?= $m ?>
            </div>
        <?php endforeach; ?>
        <?php foreach ($errors as $e): ?>
            <div class="err">
                <?= $e ?>
            </div>
        <?php endforeach; ?>

        <?php if ($setupDone): ?>
            <hr class="border-secondary mt-4">
            <h5 class="mt-3 text-success">Setup complete! 🎉</h5>
            <p>You can now log in with the credentials above.</p>
            <a href="index.php" class="btn btn-primary mt-2">Go to Login →</a>
        <?php else: ?>
            <hr class="border-secondary mt-4">
            <p class="text-danger">Setup encountered errors. Please fix them and try again.</p>
        <?php endif; ?>
    </div>
</body>

</html>