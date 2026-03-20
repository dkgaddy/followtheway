<?php
// Mounted at /users/*  — all endpoints require admin

$sub  = substr($uri, strlen('/users'));
$db   = get_db();
$self = require_admin();   // all user endpoints are admin-only

// ── GET /users ────────────────────────────────────────────────────────────
if ($method === 'GET' && ($sub === '' || $sub === '/')) {
    $rows = $db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    foreach ($rows as &$r) $r['id'] = (int)$r['id'];
    send_ok($rows);
}

// ── GET /users/{id} ───────────────────────────────────────────────────────
if ($method === 'GET' && preg_match('#^/(\d+)$#', $sub, $m)) {
    $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row  = $stmt->fetch();
    if (!$row) send_error(404, 'User not found');
    $row['id'] = (int)$row['id'];
    send_ok($row);
}

// ── POST /users ───────────────────────────────────────────────────────────
if ($method === 'POST' && ($sub === '' || $sub === '/')) {
    $b    = json_body();
    $name = trim($b['name']  ?? '');
    $email= trim($b['email'] ?? '');
    $pass = $b['password']   ?? '';
    $role = in_array($b['role'] ?? '', ['member','admin']) ? $b['role'] : 'member';

    if (!$name)  send_error(400, 'Name is required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) send_error(400, 'Valid email required');
    if (strlen($pass) < 8) send_error(400, 'Password must be at least 8 characters');

    $check = $db->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) send_error(409, 'Email already registered');

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)')
       ->execute([$name, $email, $hash, $role]);
    $id = (int)$db->lastInsertId();
    send_created(['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role]);
}

// ── PUT /users/{id} ───────────────────────────────────────────────────────
if ($method === 'PUT' && preg_match('#^/(\d+)$#', $sub, $m)) {
    $b    = json_body();
    $id   = (int)$m[1];
    $name = trim($b['name']  ?? '');
    $email= trim($b['email'] ?? '');
    $role = in_array($b['role'] ?? '', ['member','admin']) ? $b['role'] : 'member';

    if (!$name)  send_error(400, 'Name is required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) send_error(400, 'Valid email required');

    // Check email uniqueness excluding this user
    $check = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $check->execute([$email, $id]);
    if ($check->fetch()) send_error(409, 'Email already in use');

    $db->prepare('UPDATE users SET name=?, email=?, role=? WHERE id=?')
       ->execute([$name, $email, $role, $id]);

    // Optional password update
    $newPass = $b['new_password'] ?? '';
    if (strlen($newPass) >= 8) {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $id]);
    }

    $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) send_error(404, 'User not found');
    $row['id'] = (int)$row['id'];
    send_ok($row);
}

// ── PUT /users/{id}/password ──────────────────────────────────────────────
if ($method === 'PUT' && preg_match('#^/(\d+)/password$#', $sub, $m)) {
    $b    = json_body();
    $pass = $b['password'] ?? '';
    if (strlen($pass) < 8) send_error(400, 'Password must be at least 8 characters');
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $m[1]]);
    send_ok(['ok' => true]);
}

// ── DELETE /users/{id} ────────────────────────────────────────────────────
if ($method === 'DELETE' && preg_match('#^/(\d+)$#', $sub, $m)) {
    if ((int)$m[1] === (int)$self['sub']) {
        send_error(403, 'You cannot delete your own account');
    }
    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$m[1]]);
    send_ok(['ok' => true]);
}

send_error(404, 'Not found');
