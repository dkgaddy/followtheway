<?php
// Copy this file to config.php and fill in your values.
// config.php is gitignored — never commit it.

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Generate with: openssl rand -hex 32
define('JWT_SECRET', 'replace_with_a_long_random_string');

// Seed key — used once to create the first admin. Change after use.
define('SEED_KEY', 'replace_with_a_secret_seed_key');

// Absolute path to the uploads directory (no trailing slash)
// e.g. '/home/username/public_html/uploads'
define('UPLOADS_PATH', dirname(__DIR__) . '/uploads');
define('UPLOADS_URL',  '/uploads');
