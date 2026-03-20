<?php
// Mounted at /events/*

$sub = substr($uri, strlen('/events'));
$db  = get_db();

// ── GET /events/upcoming?limit=N ──────────────────────────────────────────
if ($method === 'GET' && $sub === '/upcoming') {
    $limit = min((int)($_GET['limit'] ?? 6), 50);
    $stmt  = $db->prepare('SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT ?');
    $stmt->execute([$limit]);
    send_ok($stmt->fetchAll());
}

// ── GET /events/all ───────────────────────────────────────────────────────
if ($method === 'GET' && $sub === '/all') {
    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = min((int)($_GET['per_page'] ?? 12), 100);
    $offset   = ($page - 1) * $per_page;

    $total = $db->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $stmt  = $db->prepare('SELECT * FROM events ORDER BY event_date DESC LIMIT ? OFFSET ?');
    $stmt->execute([$per_page, $offset]);

    send_ok(['items' => $stmt->fetchAll(), 'total' => (int)$total, 'page' => $page, 'per_page' => $per_page]);
}

// ── GET /events/{id} ──────────────────────────────────────────────────────
if ($method === 'GET' && preg_match('#^/(\d+)$#', $sub, $m)) {
    $stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row  = $stmt->fetch();
    if (!$row) send_error(404, 'Event not found');
    send_ok($row);
}

// ── POST /events ──────────────────────────────────────────────────────────
if ($method === 'POST' && ($sub === '' || $sub === '/')) {
    require_admin();
    $b = json_body();
    foreach (['title', 'event_date', 'image_url', 'description'] as $f) {
        if (empty($b[$f])) send_error(400, "Field '$f' is required");
    }
    $db->prepare('INSERT INTO events (title, image_url, event_date, description) VALUES (?,?,?,?)')
       ->execute([$b['title'], $b['image_url'], $b['event_date'], $b['description']]);
    $id = (int)$db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    send_created($stmt->fetch());
}

// ── PUT /events/{id} ──────────────────────────────────────────────────────
if ($method === 'PUT' && preg_match('#^/(\d+)$#', $sub, $m)) {
    require_admin();
    $b = json_body();
    $db->prepare('UPDATE events SET title=?, image_url=?, event_date=?, description=? WHERE id=?')
       ->execute([$b['title'], $b['image_url'], $b['event_date'], $b['description'], $m[1]]);
    $stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();
    if (!$row) send_error(404, 'Event not found');
    send_ok($row);
}

// ── DELETE /events/{id} ───────────────────────────────────────────────────
if ($method === 'DELETE' && preg_match('#^/(\d+)$#', $sub, $m)) {
    require_admin();
    $db->prepare('DELETE FROM events WHERE id = ?')->execute([$m[1]]);
    send_ok(['ok' => true]);
}

send_error(404, 'Not found');
