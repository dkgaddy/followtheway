<?php
// POST /api/upload  — admin only
// Form fields: type (sermons|thoughts|events), file_type (image|audio), file (the upload)

require_admin();

$type      = $_POST['type']      ?? '';
$file_type = $_POST['file_type'] ?? '';

$allowed_types = ['sermons', 'thoughts', 'events'];
if (!in_array($type, $allowed_types)) {
    send_error(400, 'Invalid type. Must be: sermons, thoughts, or events');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['file']['error'] ?? -1;
    send_error(400, 'Upload error (code ' . $code . ')');
}

$file      = $_FILES['file'];
$tmp_path  = $file['tmp_name'];
$orig_name = $file['name'];
$size      = $file['size'];

// ── MIME type detection ────────────────────────────────────────────────────
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mime     = finfo_file($finfo, $tmp_path);
finfo_close($finfo);

$image_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$audio_types = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/wav'];

if ($file_type === 'image') {
    if (!in_array($mime, $image_types)) send_error(400, 'File must be a JPEG, PNG, WebP, or GIF image');
    if ($size > 10 * 1024 * 1024)      send_error(400, 'Image must be under 10 MB');
    $ext = match($mime) {
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
} elseif ($file_type === 'audio') {
    if (!in_array($mime, $audio_types)) send_error(400, 'File must be an MP3 or audio file');
    if ($size > 100 * 1024 * 1024)     send_error(400, 'Audio must be under 100 MB');
    $ext = 'mp3';
} else {
    send_error(400, 'file_type must be "image" or "audio"');
}

// ── Safe filename ──────────────────────────────────────────────────────────
$base     = pathinfo($orig_name, PATHINFO_FILENAME);
$base     = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);
$base     = substr($base, 0, 60);
$filename = uniqid() . '_' . $base . '.' . $ext;

// ── Move file ─────────────────────────────────────────────────────────────
$dest_dir = UPLOADS_PATH . '/' . $type;
if (!is_dir($dest_dir)) {
    mkdir($dest_dir, 0755, true);
}
$dest = $dest_dir . '/' . $filename;

if (!move_uploaded_file($tmp_path, $dest)) {
    send_error(500, 'Failed to save uploaded file');
}

$url = UPLOADS_URL . '/' . $type . '/' . $filename;
send_ok(['url' => $url, 'filename' => $filename]);
