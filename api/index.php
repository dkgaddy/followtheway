<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/db.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = preg_replace('#^.*?/api#', '', $uri);   // strip everything up to and including /api
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// ── Health ─────────────────────────────────────────────────────────────────
if ($uri === '/health') {
    send_ok(['status' => 'ok']);
}

// ── Route dispatch ─────────────────────────────────────────────────────────
if (str_starts_with($uri, '/auth'))    { require __DIR__ . '/routes/auth.php';    exit; }
if (str_starts_with($uri, '/sermons')) { require __DIR__ . '/routes/sermons.php'; exit; }
if (str_starts_with($uri, '/thoughts')){ require __DIR__ . '/routes/thoughts.php';exit; }
if (str_starts_with($uri, '/events'))  { require __DIR__ . '/routes/events.php';  exit; }
if (str_starts_with($uri, '/users'))   { require __DIR__ . '/routes/users.php';   exit; }
if (str_starts_with($uri, '/upload'))  { require __DIR__ . '/upload.php';         exit; }

send_error(404, 'Not found');
