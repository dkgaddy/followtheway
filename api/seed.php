<?php
/**
 * Seed the first admin user.
 * Usage (browser): /api/seed.php?key=YOUR_SEED_KEY&name=Your+Name&email=you@example.com&password=yourpassword
 * Usage (CLI):     php seed.php YOUR_SEED_KEY "Your Name" you@example.com yourpassword
 *
 * Remove or rename this file after use.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Accept args from CLI or query string
if (php_sapi_name() === 'cli') {
    [$_, $key, $name, $email, $password] = array_pad($argv, 5, '');
} else {
    $key      = $_GET['key']      ?? '';
    $name     = $_GET['name']     ?? '';
    $email    = $_GET['email']    ?? '';
    $password = $_GET['password'] ?? '';
}

if (!defined('SEED_KEY') || !hash_equals(SEED_KEY, $key)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid seed key']);
    exit;
}

if (!$name || !$email || !$password) {
    echo json_encode(['error' => 'name, email, and password are required']);
    exit;
}

$db   = get_db();
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'User already exists', 'email' => $email]);
    exit;
}

$db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
   ->execute([$name, $email, $hash, 'admin']);

echo json_encode(['ok' => true, 'message' => "Admin '$name' ($email) created."]);
