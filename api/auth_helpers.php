<?php
require_once __DIR__ . '/config.php';

// ── Base64url ──────────────────────────────────────────────────────────────

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

// ── JWT ───────────────────────────────────────────────────────────────────

function jwt_encode(array $payload): string {
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $body    = base64url_encode(json_encode($payload));
    $sig     = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) return null;
    $data = json_decode(base64url_decode($p), true);
    if (!$data || ($data['exp'] ?? 0) < time()) return null;
    return $data;
}

function issue_token(array $user): string {
    return jwt_encode([
        'sub'  => (int) $user['id'],
        'role' => $user['role'],
        'name' => $user['name'],
        'exp'  => time() + (7 * 24 * 3600),
    ]);
}

// ── Auth guards ───────────────────────────────────────────────────────────

function get_token_payload(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token  = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';
    return $token ? jwt_decode($token) : null;
}

function require_auth(): array {
    $payload = get_token_payload();
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    return $payload;
}

function require_admin(): array {
    $payload = get_token_payload();
    if (!$payload || $payload['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    return $payload;
}

// ── Helpers ───────────────────────────────────────────────────────────────

function json_body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function send(int $code, mixed $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function send_ok(mixed $data): void   { send(200, $data); }
function send_created(mixed $data): void { send(201, $data); }
function send_error(int $code, string $msg): void { send($code, ['error' => $msg]); }
