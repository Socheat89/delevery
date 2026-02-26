<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Admin only']);
    exit();
}

$db = getDB();
$id = (int) ($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$pass = $_POST['password'] ?? '';

if (!$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required']);
    exit();
}

// Email duplicate check
$check = $db->prepare("SELECT id FROM users WHERE email=? AND id != ?");
$check->execute([$email, $id]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already in use']);
    exit();
}

if ($id) {
    // Update
    if ($pass) {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET name=?,email=?,phone=?,password=? WHERE id=? AND role='driver'");
        $stmt->execute([$name, $email, $phone ?: null, $hash, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET name=?,email=?,phone=? WHERE id=? AND role='driver'");
        $stmt->execute([$name, $email, $phone ?: null, $id]);
    }
    echo json_encode(['success' => true, 'message' => 'Driver updated']);
} else {
    // Insert
    if (!$pass) {
        echo json_encode(['success' => false, 'message' => 'Password is required for new driver']);
        exit();
    }
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $username = strtolower(explode('@', $email)[0]); // e.g. john@example.com -> john

    // Check if username already exists just in case
    $ucheck = $db->prepare("SELECT id FROM users WHERE username=?");
    $ucheck->execute([$username]);
    if ($ucheck->fetch()) {
        $username .= uniqid(); // append random string if username exists
    }

    $stmt = $db->prepare("INSERT INTO users (username, name, email, password, phone, role) VALUES (?,?,?,?,?,'driver')");
    $stmt->execute([$username, $name, $email, $hash, $phone ?: null]);
    echo json_encode(['success' => true, 'message' => 'Driver created', 'id' => (int) $db->lastInsertId()]);
}
