<?php
// Mounted at /auth/*
// $uri and $method are set by index.php

$sub = substr($uri, strlen('/auth'));

// POST /auth/login
if ($method === 'POST' && ($sub === '/login' || $sub === '')) {
    $body  = json_body();
    $email = trim($body['email'] ?? '');
    $pass  = $body['password'] ?? '';

    if (!$email || !$pass) send_error(400, 'Email and password required');

    $db   = get_db();
    $stmt = $db->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        send_error(401, 'Invalid email or password');
    }

    send_ok([
        'token' => issue_token($user),
        'role'  => $user['role'],
        'name'  => $user['name'],
        'id'    => (int) $user['id'],
    ]);
}

// POST /auth/register
if ($method === 'POST' && $sub === '/register') {
    $body  = json_body();
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    $pass  = $body['password']   ?? '';

    if (!$name)                        send_error(400, 'Name is required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) send_error(400, 'Valid email required');
    if (strlen($pass) < 8)             send_error(400, 'Password must be at least 8 characters');

    $db   = get_db();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) send_error(409, 'Email already registered');

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
       ->execute([$name, $email, $hash, 'member']);

    send_created(['ok' => true, 'message' => 'Account created']);
}

// GET /auth/me
if ($method === 'GET' && $sub === '/me') {
    $payload = require_auth();
    $db   = get_db();
    $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    if (!$user) send_error(404, 'User not found');
    $user['id'] = (int) $user['id'];
    send_ok($user);
}

send_error(404, 'Not found');
