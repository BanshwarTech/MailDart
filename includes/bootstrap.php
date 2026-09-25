<?php
/**
 * MailDart bootstrap: environment, configuration, database, sessions and JSON helpers.
 * Every entry point (index.php and api/*.php) starts with require __DIR__ . '/.../includes/bootstrap.php'.
 */

declare(strict_types=1);

define('MD_ROOT', dirname(__DIR__));
const MD_SCHEMA_VERSION = 2;

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
    ];
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
require_once __DIR__ . '/recaptcha.php';
require_once __DIR__ . '/google.php';
require_once __DIR__ . '/seo.php';
