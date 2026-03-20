<?php
// Mounted at /thoughts/*

$sub = substr($uri, strlen('/thoughts'));
$db  = get_db();

// ── GET /thoughts?limit=N ──────────────────────────────────────────────────
if ($method === 'GET' && ($sub === '' || $sub === '/')) {
    $limit = min((int)($_GET['limit'] ?? 3), 50);
    $stmt  = $db->prepare('SELECT * FROM thoughts ORDER BY publish_date DESC LIMIT ?');
    $stmt->execute([$limit]);
    send_ok($stmt->fetchAll());
}

// ── GET /thoughts/all ──────────────────────────────────────────────────────
if ($method === 'GET' && $sub === '/all') {
    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = min((int)($_GET['per_page'] ?? 12), 100);
    $offset   = ($page - 1) * $per_page;

    $total = $db->query('SELECT COUNT(*) FROM thoughts')->fetchColumn();
    $stmt  = $db->prepare('SELECT * FROM thoughts ORDER BY publish_date DESC LIMIT ? OFFSET ?');
    $stmt->execute([$per_page, $offset]);

    send_ok(['items' => $stmt->fetchAll(), 'total' => (int)$total, 'page' => $page, 'per_page' => $per_page]);
}

// ── GET /thoughts/{id} ────────────────────────────────────────────────────
if ($method === 'GET' && preg_match('#^/(\d+)$#', $sub, $m)) {
    $stmt = $db->prepare('SELECT * FROM thoughts WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row  = $stmt->fetch();
    if (!$row) send_error(404, 'Thought not found');
    send_ok($row);
}

// ── POST /thoughts ─────────────────────────────────────────────────────────
if ($method === 'POST' && ($sub === '' || $sub === '/')) {
    require_admin();
    $b = json_body();
    foreach (['title', 'author', 'publish_date', 'body'] as $f) {
        if (empty($b[$f])) send_error(400, "Field '$f' is required");
    }
    $db->prepare('INSERT INTO thoughts (title, body, author, image_url, publish_date) VALUES (?,?,?,?,?)')
       ->execute([$b['title'], $b['body'], $b['author'], $b['image_url'] ?? '', $b['publish_date']]);
    $id = (int)$db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM thoughts WHERE id = ?');
    $stmt->execute([$id]);
    send_created($stmt->fetch());
}

// ── PUT /thoughts/{id} ────────────────────────────────────────────────────
if ($method === 'PUT' && preg_match('#^/(\d+)$#', $sub, $m)) {
    require_admin();
    $b = json_body();
    $db->prepare('UPDATE thoughts SET title=?, body=?, author=?, image_url=?, publish_date=? WHERE id=?')
       ->execute([$b['title'], $b['body'], $b['author'], $b['image_url'] ?? '', $b['publish_date'], $m[1]]);
    $stmt = $db->prepare('SELECT * FROM thoughts WHERE id = ?');
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch();
    if (!$row) send_error(404, 'Thought not found');
    send_ok($row);
}

// ── DELETE /thoughts/{id} ─────────────────────────────────────────────────
if ($method === 'DELETE' && preg_match('#^/(\d+)$#', $sub, $m)) {
    require_admin();
    $db->prepare('DELETE FROM thoughts WHERE id = ?')->execute([$m[1]]);
    send_ok(['ok' => true]);
}

send_error(404, 'Not found');
