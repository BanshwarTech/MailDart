<?php
/**
 * MailDart bootstrap: environment, configuration, database, sessions and JSON helpers.
 * Every entry point (index.php and api/*.php) starts with require __DIR__ . '/.../includes/bootstrap.php'.
 */

declare(strict_types=1);

define('MD_ROOT', dirname(__DIR__));
const MD_SCHEMA_VERSION = 11;

/** Writable storage folder (sessions, app key, schema markers); blocked from the web by storage/.htaccess. */
function md_storage_dir(): string
{
    $dir = MD_ROOT . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    return $dir;
}

/* ---------------------------------------------------------------------------
 * .env loader (KEY=value, optional quotes, # comments)
 * ------------------------------------------------------------------------- */
function md_load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

md_load_env(MD_ROOT . '/.env');

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

/** Placeholder values copied from .env.example must not count as configured keys. */
function env_secret(string $key): ?string
{
    $value = env($key);
    if ($value === null || preg_match('/^(MY_|YOUR_|nvapi-\.\.\.$)/i', $value)) {
        return null;
    }
    return trim($value);
}

/* ---------------------------------------------------------------------------
 * Errors: never leak PHP warnings into JSON responses
 * ------------------------------------------------------------------------- */
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/* ---------------------------------------------------------------------------
 * Database (MySQL via PDO). Database and tables are created automatically.
 * ------------------------------------------------------------------------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $name = env('DB_NAME', 'maildart');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');

    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('DB_NAME may only contain letters, numbers and underscores.');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, $options);
    } catch (PDOException $e) {
        // 1049 = unknown database: create it on first run
        if ((int) ($e->errorInfo[1] ?? 0) !== 1049) {
            throw $e;
        }
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");
    }

    // Create / upgrade tables once per schema version
    $marker = MD_ROOT . '/storage/schema-' . MD_SCHEMA_VERSION . '-' . $name . '.done';
    if (!is_file($marker)) {
        md_migrate($pdo);
        md_migrate_campaign($pdo);
        md_migrate_v2($pdo);
        md_migrate_v3($pdo);
        md_migrate_v4($pdo);
        md_migrate_v5($pdo);
        md_migrate_v6($pdo);
        md_migrate_v7($pdo);
        md_migrate_v8($pdo);
        md_migrate_v9($pdo);
        md_migrate_v10($pdo);
        md_migrate_v11($pdo);
        md_storage_dir();
        file_put_contents($marker, date('c'));
    }
    return $pdo;
}

/** v2: Google sign-in (users.google_id) for databases created by v1. */
function md_migrate_v2(PDO $pdo): void
{
    $has = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'")->fetch();
    if (!$has) {
        $pdo->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL UNIQUE AFTER photo_url');
    }
}

/**
 * v3: user roles (admin / user) and paid plans (plans, payments via Razorpay, users.plan_id / plan_expires_at).
 * The oldest account becomes the admin if there is none yet.
 */
function md_migrate_v3(PDO $pdo): void
{
    $columns = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(), 'Field');
    if (!in_array('role', $columns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user' AFTER google_id");
    }
    md_ensure_admin($pdo);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS plans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            description VARCHAR(500) NOT NULL DEFAULT '',
            price_paise INT UNSIGNED NOT NULL DEFAULT 0,
            currency CHAR(3) NOT NULL DEFAULT 'INR',
            billing_period ENUM('month','year','lifetime') NOT NULL DEFAULT 'month',
            features TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
    plans_seed_defaults($pdo);

    if (!in_array('plan_id', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN plan_id INT UNSIGNED NULL AFTER role,
                    ADD COLUMN plan_expires_at DATETIME NULL AFTER plan_id,
                    ADD CONSTRAINT fk_user_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL');
    }

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            plan_id INT UNSIGNED NULL,
            plan_name VARCHAR(120) NOT NULL DEFAULT '',
            amount_paise INT UNSIGNED NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT 'INR',
            razorpay_order_id VARCHAR(64) NOT NULL UNIQUE,
            razorpay_payment_id VARCHAR(64) NULL,
            status ENUM('created','paid','failed') NOT NULL DEFAULT 'created',
            created_at DATETIME NOT NULL,
            paid_at DATETIME NULL,
            INDEX idx_payments_user (user_id, created_at),
            CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_payment_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
}

/**
 * Guarantee at least one admin: if none exists, promote the oldest account (lowest id).
 * Called after every sign-up, so the very first user is always the admin. Idempotent and race-safe:
 * concurrent calls all pick the same lowest id.
 */
function md_ensure_admin(PDO $pdo): void
{
    $hasAdmin = $pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
    if (!$hasAdmin) {
        $pdo->exec("UPDATE users SET role = 'admin' ORDER BY id ASC LIMIT 1");
    }
}

/**
 * v4: plan limits (max recipients, Excel import, AI templates, CSV export). Existing plans named
 * Starter / Pro / Business (the v3 defaults) get matching limits; any other plan starts with Pro-like access.
 */
function md_migrate_v4(PDO $pdo): void
{
    $columns = array_column($pdo->query('SHOW COLUMNS FROM plans')->fetchAll(), 'Field');
    if (in_array('max_recipients', $columns, true)) {
        return;
    }
    $pdo->exec('ALTER TABLE plans
                ADD COLUMN max_recipients INT UNSIGNED NULL AFTER billing_period,
                ADD COLUMN allow_excel TINYINT(1) NOT NULL DEFAULT 1 AFTER max_recipients,
                ADD COLUMN allow_ai TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_excel,
                ADD COLUMN allow_export TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_ai');
    $pdo->exec("UPDATE plans SET max_recipients = 100, allow_excel = 0, allow_ai = 0, allow_export = 0 WHERE name = 'Starter' AND price_paise = 0");
    $pdo->exec("UPDATE plans SET max_recipients = 5000 WHERE name = 'Pro'");
    $pdo->exec("UPDATE plans SET max_recipients = NULL WHERE name = 'Business'");
    // Multiple campaigns per account is not a feature (one campaign per user), so drop that line from the default plan
    $pdo->exec("UPDATE plans SET features = REPLACE(features, 'Multiple campaigns for different brands\n', '') WHERE name = 'Business'");
}

/**
 * v5: two more plan limits (custom send gap, template test emails). Plan cards now list the limits
 * automatically, so the default plans' feature text keeps only the extras (support etc.) — rewritten
 * only when an admin has not edited it.
 */
function md_migrate_v5(PDO $pdo): void
{
    $columns = array_column($pdo->query('SHOW COLUMNS FROM plans')->fetchAll(), 'Field');
    if (in_array('allow_custom_interval', $columns, true)) {
        return;
    }
    $pdo->exec('ALTER TABLE plans
                ADD COLUMN allow_custom_interval TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_export,
                ADD COLUMN allow_test_email TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_custom_interval');
    $pdo->exec("UPDATE plans SET allow_custom_interval = 0, allow_test_email = 0 WHERE name = 'Starter' AND price_paise = 0");

    $rewrite = [
        'Starter' => ["Up to 100 recipients per campaign\nSandbox mode to practise safely\nReady-made festival & business templates\n1 SMTP account (Gmail or any SMTP)\nDelivery logs",
            "Sandbox mode to practise safely\nReady-made festival & business templates\n1 SMTP account (Gmail or any SMTP)\nDelivery logs & live countdown"],
        'Pro' => ["Up to 5,000 recipients per campaign\nExcel / CSV import with custom {{variables}}\nAI template generator\nCustom send gap & live countdown\nTest emails before sending\nExport delivery logs as CSV\nEmail support",
            "Everything in Starter\nEmail support"],
        'Business' => ["Unlimited recipients\nEverything in Pro\nPriority support\nHelp setting up SMTP & sender domains",
            "Everything in Pro\nPriority support\nHelp setting up SMTP & sender domains"],
    ];
    $stmt = $pdo->prepare('UPDATE plans SET features = ? WHERE name = ? AND features = ?');
    foreach ($rewrite as $name => [$old, $new]) {
        $stmt->execute([$new, $name, $old]);
    }
}

/** v6: monthly test-email quota per plan (Starter: 10 real test emails a month) + per-user usage counters. */
function md_migrate_v6(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS usage_counters (
            user_id INT UNSIGNED NOT NULL,
            metric VARCHAR(32) NOT NULL,
            period CHAR(7) NOT NULL,
            used INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (user_id, metric, period),
            CONSTRAINT fk_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $columns = array_column($pdo->query('SHOW COLUMNS FROM plans')->fetchAll(), 'Field');
    if (!in_array('test_email_limit', $columns, true)) {
        $pdo->exec('ALTER TABLE plans ADD COLUMN test_email_limit INT UNSIGNED NULL AFTER allow_test_email');
        $pdo->exec("UPDATE plans SET allow_test_email = 1, test_email_limit = 10 WHERE name = 'Starter' AND price_paise = 0");
    }
}

/** v7: Excel / CSV upload is part of the free plan too; only rows within the recipient limit are imported. */
function md_migrate_v7(PDO $pdo): void
{
    $pdo->exec("UPDATE plans SET allow_excel = 1 WHERE name = 'Starter' AND price_paise = 0");
}

/**
 * v8: fourth default plan "Basic" (₹199/month) between Starter and Pro; display order Starter, Basic, Pro, Business.
 * Skipped if a plan named Basic already exists. Pro's card text moves to "Everything in Basic" only if unedited.
 */
function md_migrate_v8(PDO $pdo): void
{
    $exists = $pdo->query("SELECT 1 FROM plans WHERE name = 'Basic'")->fetchColumn();
    if (!$exists && (int) $pdo->query('SELECT COUNT(*) FROM plans')->fetchColumn() > 0) {
        $pdo->prepare('INSERT INTO plans (name, description, price_paise, currency, billing_period, max_recipients, allow_excel, allow_ai,
                       allow_export, allow_custom_interval, allow_test_email, test_email_limit, features, is_active, is_featured, sort_order, created_at, updated_at)
                       VALUES (?, ?, 19900, \'INR\', \'month\', 1000, 1, 0, 1, 1, 1, 50, ?, 1, 0, 2, NOW(), NOW())')
            ->execute(['Basic', 'For small businesses growing their email list.', "Everything in Starter\nEmail support"]);
        $pdo->exec("UPDATE plans SET sort_order = 1 WHERE name = 'Starter'");
        $pdo->exec("UPDATE plans SET sort_order = 3 WHERE name = 'Pro'");
        $pdo->exec("UPDATE plans SET sort_order = 4 WHERE name = 'Business'");
        $pdo->exec("UPDATE plans SET features = 'Everything in Basic\nEmail support' WHERE name = 'Pro' AND features = 'Everything in Starter\nEmail support'");
    }
}

/** v9: every account without a plan is put on the default free plan (new accounts get it at sign-up). */
function md_migrate_v9(PDO $pdo): void
{
    $free = $pdo->query('SELECT id FROM plans WHERE is_active = 1 AND price_paise = 0 ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn();
    if ($free) {
        $pdo->prepare('UPDATE users SET plan_id = ?, plan_expires_at = NULL WHERE plan_id IS NULL')->execute([$free]);
    }
}

/** v10: app-wide settings (key/value), e.g. the admin's "Plans system" master switch (off by default). */
function md_migrate_v10(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(64) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
}

/** v11: public contact form submissions and their admin review state. */
function md_migrate_v11(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(191) NOT NULL,
            topic VARCHAR(40) NOT NULL,
            message TEXT NOT NULL,
            ip_hash CHAR(64) NOT NULL,
            mail_status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
            mail_error VARCHAR(500) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            INDEX idx_contact_created (created_at),
            INDEX idx_contact_ip (ip_hash, created_at),
            INDEX idx_contact_unread (read_at, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
}

function app_setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$key] ?? $default;
}

function app_setting_set(string $key, string $value): void
{
    db()->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)')
        ->execute([$key, $value, now_sql()]);
}

function md_migrate(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uid CHAR(32) NOT NULL UNIQUE,
            email VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(191) NOT NULL DEFAULT '',
            photo_url VARCHAR(500) NOT NULL DEFAULT '',
            google_id VARCHAR(64) NULL UNIQUE,
            role ENUM('admin','user') NOT NULL DEFAULT 'user',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS user_smtp_settings (
            user_id INT UNSIGNED PRIMARY KEY,
            config_json TEXT NOT NULL,
            password_enc TEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_smtp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL,
            INDEX idx_attempts (email, attempted_at),
            INDEX idx_attempts_ip (ip, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
}

function now_sql(): string
{
    return date('Y-m-d H:i:s');
}

/* ---------------------------------------------------------------------------
 * Encryption for stored SMTP passwords (AES-256-GCM, key from APP_KEY or storage/app.key)
 * ------------------------------------------------------------------------- */
function app_key(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }
    $fromEnv = env_secret('APP_KEY');
    if ($fromEnv) {
        return $key = hash('sha256', $fromEnv, true);
    }
    $file = md_storage_dir() . '/app.key';
    if (!is_file($file)) {
        file_put_contents($file, bin2hex(random_bytes(32)));
    }
    return $key = hash('sha256', trim((string) file_get_contents($file)), true);
}

function encrypt_secret(string $plain): string
{
    if ($plain === '') {
        return '';
    }
    $iv = random_bytes(12);
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function decrypt_secret(string $encoded): string
{
    if ($encoded === '') {
        return '';
    }
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 28) {
        return '';
    }
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $plain === false ? '' : $plain;
}

/* ---------------------------------------------------------------------------
 * Sessions
 * ------------------------------------------------------------------------- */
const MD_REMEMBER_SECONDS = 60 * 60 * 24 * 30; // "Keep me logged in" = 30 days

function md_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $savePath = md_storage_dir() . '/sessions';
    if (!is_dir($savePath)) {
        mkdir($savePath, 0770, true);
    }
    session_save_path($savePath);
    ini_set('session.gc_maxlifetime', (string) MD_REMEMBER_SECONDS);
    ini_set('session.use_strict_mode', '1');
    session_name('MAILDARTSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Re-issue the long-lived cookie on each visit so "keep me logged in" slides forward
    if (!empty($_SESSION['remember'])) {
        md_set_session_cookie(true);
    }
}

function md_set_session_cookie(bool $remember): void
{
    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
        'expires' => $remember ? time() + MD_REMEMBER_SECONDS : 0,
        'path' => $params['path'],
        'secure' => $params['secure'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Public user shape, same fields the React app read from Firebase's User object. */
function public_user(array $row): array
{
    return [
        'uid' => $row['uid'],
        'email' => $row['email'],
        'displayName' => $row['display_name'] !== '' ? $row['display_name'] : explode('@', $row['email'])[0],
        'photoURL' => $row['photo_url'] !== '' ? $row['photo_url'] : null,
        'role' => user_role($row),
    ];
}

function user_role(array $row): string
{
    return ($row['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
}

function is_admin(?array $row): bool
{
    return $row !== null && user_role($row) === 'admin';
}

function current_user_row(): ?array
{
    md_session_start();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* ---------------------------------------------------------------------------
 * JSON request / response helpers for api/*.php
 * ------------------------------------------------------------------------- */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function json_error(string $message, int $status = 400, array $extra = []): never
{
    json_response(['success' => false, 'error' => $message] + $extra, $status);
}

/** Parsed JSON body. POSTs must be JSON, which also blocks cross-site form posts (CSRF). */
function request_json(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return [];
    }
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($type, 'application/json') === false) {
        json_error('Requests must be sent as JSON.', 415);
    }
    $data = json_decode((string) file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        header('Allow: ' . $method);
        json_error('Method not allowed.', 405);
    }
}

function require_login(): array
{
    $user = current_user_row();
    if (!$user) {
        json_error('Please sign in to continue.', 401);
    }
    return $user;
}

/** Run an API handler, turning uncaught exceptions into a JSON 500. */
function api(callable $handler): void
{
    try {
        $handler();
    } catch (PDOException $e) {
        error_log('MailDart DB error: ' . $e->getMessage());
        json_error('Database error: could not reach MySQL. Make sure MySQL is running in XAMPP and the DB_* settings in .env are correct.', 500);
    } catch (Throwable $e) {
        error_log('MailDart error: ' . $e->getMessage());
        json_error($e->getMessage() ?: 'Unexpected server error.', 500);
    }
}

/** Absolute base URL of the app (for links in emails), e.g. http://localhost/core/MailDart/ */
function app_url(): string
{
    $configured = env_secret('APP_URL');
    if ($configured) {
        return rtrim($configured, '/') . '/';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $dir = preg_replace('#/api$#', '', $dir);
    return "$scheme://$host$dir/";
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/billing.php';
require_once __DIR__ . '/contact.php';
require_once __DIR__ . '/recaptcha.php';
require_once __DIR__ . '/google.php';
require_once __DIR__ . '/seo.php';
