<?php
// Mounted at /sermons/*
// Variables available: $uri, $method (from index.php)

$sub = substr($uri, strlen('/sermons'));
$db  = get_db();

// ── GET /sermons?limit=N ───────────────────────────────────────────────────
if ($method === 'GET' && ($sub === '' || $sub === '/')) {
    $limit = min((int)($_GET['limit'] ?? 9), 50);
    $stmt  = $db->prepare('SELECT * FROM sermons ORDER BY date DESC LIMIT ?');
    $stmt->execute([$limit]);
    send_ok($stmt->fetchAll());
}

// ── GET /sermons/all?page=1&per_page=20 ────────────────────────────────────
if ($method === 'GET' && $sub === '/all') {
    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = min((int)($_GET['per_page'] ?? 20), 100);
    $offset   = ($page - 1) * $per_page;

    $total = $db->query('SELECT COUNT(*) FROM sermons')->fetchColumn();
    $stmt  = $db->prepare('SELECT * FROM sermons ORDER BY date DESC LIMIT ? OFFSET ?');
    $stmt->execute([$per_page, $offset]);

    send_ok(['items' => $stmt->fetchAll(), 'total' => (int)$total, 'page' => $page, 'per_page' => $per_page]);
}

// ── GET /sermons/{id} ──────────────────────────────────────────────────────
if ($method === 'GET' && preg_match('#^/(\d+)$#', $sub, $m)) {
    $stmt = $db->prepare('SELECT * FROM sermons WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row  = $stmt->fetch();
    if (!$row) send_error(404, 'Sermon not found');
    send_ok($row);
}

// ── POST /sermons ──────────────────────────────────────────────────────────
if ($method === 'POST' && ($sub === '' || $sub === '/')) {
    require_admin();
    $b = json_body();
    $required = ['title', 'speaker', 'date', 'mp3_url', 'image_url'];
    foreach ($required as $f) {
        if (empty($b[$f])) send_error(400, "Field '$f' is required");
    }
    $db->prepare('INSERT INTO sermons (title, speaker, date, mp3_url, image_url) VALUES (?,?,?,?,?)')
       ->execute([$b['title'], $b['speaker'], $b['date'], $b['mp3_url'], $b['image_url']]);
    $id = (int)$db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM sermons WHERE id = ?');
    $stmt->execute([$id]);
    send_created($stmt->fetch());
}

// ── PUT /sermons/{id} ──────────────────────────────────────────────────────
if ($method === 'PUT' && preg_match('#^/(\d+)$#', $sub, $m)) {
    require_admin();
    $b = json_body();
    $db->prepare('UPDATE sermons SET title=?, speaker=?, date=?, mp3_url=?, image_url=? WHERE id=?')
       ->execute([$b['title'], $b['speaker'], $b['date'], $b['mp3_url'], $b['image_url'], $m[1]]);
    $stmt = $db->prepare('SELECT * FROM sermons WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();
    if (!$row) send_error(404, 'Sermon not found');
    send_ok($row);
}

// ── DELETE /sermons/{id} ───────────────────────────────────────────────────
if ($method === 'DELETE' && preg_match('#^/(\d+)$#', $sub, $m)) {
    require_admin();
    $db->prepare('DELETE FROM sermons WHERE id = ?')->execute([$m[1]]);
    send_ok(['ok' => true]);
}

send_error(404, 'Not found');
