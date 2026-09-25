<?php
/**
 * Account logic (replaces Firebase Auth): register, login (with brute-force throttle), logout, password reset.
 * Every function throws AuthFailure with a user-friendly message on failure.
 */

declare(strict_types=1);

class AuthFailure extends RuntimeException
{
}

const MD_MAX_LOGIN_ATTEMPTS = 8;      // per email or IP ...
const MD_LOGIN_WINDOW_MINUTES = 15;   // ... within this window
const MD_RESET_TOKEN_MINUTES = 60;

function auth_login_session(array $userRow, bool $remember): void
{
    md_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $userRow['id'];
    $_SESSION['remember'] = $remember;
    md_set_session_cookie($remember);
}

function auth_validate_email(string $email): string
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new AuthFailure('Please enter a valid email address.');
    }
    return $email;
}

/** Same rules the sign-up form shows (8+ chars, upper, lower, number, symbol). */
function auth_validate_password(string $password): void
{
    $ok = strlen($password) >= 8 && preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password) && preg_match('/[^A-Za-z0-9]/', $password);
    if (!$ok) {
        throw new AuthFailure('Please choose a stronger password.');
    }
}

function auth_register(string $email, string $password, string $name, bool $remember = true): array
{
    $email = auth_validate_email($email);
    auth_validate_password($password);

    $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        throw new AuthFailure('This email is already registered. Please sign in instead.');
    }

    $now = now_sql();
    $displayName = trim($name) !== '' ? mb_substr(trim($name), 0, 120) : explode('@', $email)[0];
    db()->prepare('INSERT INTO users (uid, email, password_hash, display_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([bin2hex(random_bytes(16)), $email, password_hash($password, PASSWORD_DEFAULT), $displayName, $now, $now]);

    $row = auth_find_by_email($email);
    auth_login_session($row, $remember);
    return $row;
}

function auth_find_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

function auth_client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function auth_login(string $email, string $password, bool $remember): array
{
    $email = auth_validate_email($email);
    $ip = auth_client_ip();
    $since = date('Y-m-d H:i:s', time() - MD_LOGIN_WINDOW_MINUTES * 60);

    $count = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip = ?) AND attempted_at > ?');
    $count->execute([$email, $ip, $since]);
    if ((int) $count->fetchColumn() >= MD_MAX_LOGIN_ATTEMPTS) {
        throw new AuthFailure('Too many attempts. Please wait a moment and try again.');
    }

    $row = auth_find_by_email($email);
    if (!$row || !password_verify($password, $row['password_hash'])) {
        db()->prepare('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)')->execute([$email, $ip, now_sql()]);
        throw new AuthFailure('Incorrect email or password. Please check and try again.');
    }

    db()->prepare('DELETE FROM login_attempts WHERE email = ? OR attempted_at < ?')->execute([$email, $since]);
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    db()->prepare('UPDATE users SET updated_at = ? WHERE id = ?')->execute([now_sql(), $row['id']]);
    auth_login_session($row, $remember);
    return $row;
}

function auth_logout(): void
{
    md_session_start();
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'httponly' => true, 'samesite' => 'Lax']);
    session_destroy();
}

/**
 * Email a password-reset link. Uses the server mail account from .env (MAIL_HOST, MAIL_PORT, MAIL_USERNAME,
 * MAIL_PASSWORD, MAIL_FROM_EMAIL, MAIL_FROM_NAME). Never reveals whether an email is registered.
 */
function auth_send_password_reset(string $email): void
{
    $email = auth_validate_email($email);
    $host = env_secret('MAIL_HOST');
    $user = env_secret('MAIL_USERNAME');
    $pass = env_secret('MAIL_PASSWORD');
    if (!$host || !$user || !$pass) {
        throw new AuthFailure('Password reset email is not set up on this server yet. Ask your administrator to add the MAIL_* settings in the .env file.');
    }

    $row = auth_find_by_email($email);
    if (!$row) {
        return; // same response as success
    }

    $token = bin2hex(random_bytes(32));
    db()->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < ?')->execute([$row['id'], now_sql()]);
    db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$row['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + MD_RESET_TOKEN_MINUTES * 60), now_sql()]);

    $link = app_url() . 'reset?token=' . $token;
    $name = e($row['display_name'] ?: 'there');
    $html = '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;color:#0a1b2e">'
        . '<h2 style="margin:0 0 12px">Reset your MailDart password</h2>'
        . "<p>Hi {$name},</p><p>We received a request to reset your password. This link is valid for " . MD_RESET_TOKEN_MINUTES . ' minutes.</p>'
        . '<p><a href="' . e($link) . '" style="display:inline-block;padding:10px 18px;background:#557392;color:#fff;border-radius:10px;text-decoration:none;font-weight:600">Choose a new password</a></p>'
        . '<p style="font-size:12px;color:#557392">If you did not request this, you can ignore this email.</p></div>';

    try {
        smtp_send(
            ['host' => $host, 'port' => (int) env('MAIL_PORT', '587'), 'secure' => (int) env('MAIL_PORT', '587') === 465, 'user' => $user, 'pass' => $pass],
            ['to' => $row['email'], 'subject' => 'Reset your MailDart password', 'html' => $html,
                'fromEmail' => env('MAIL_FROM_EMAIL', $user), 'fromName' => env('MAIL_FROM_NAME', 'MailDart')]
        );
    } catch (SmtpFailure $e) {
        throw new AuthFailure('Could not send the reset email: ' . $e->getMessage());
    }
}

/** Returns the user row for a valid, unused, unexpired reset token, else null. */
function auth_find_reset(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $stmt = db()->prepare('SELECT u.*, r.id AS reset_id FROM password_resets r JOIN users u ON u.id = r.user_id
                           WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?');
    $stmt->execute([hash('sha256', $token), now_sql()]);
    return $stmt->fetch() ?: null;
}

function auth_reset_password(string $token, string $password): array
{
    $row = auth_find_reset($token);
    if (!$row) {
        throw new AuthFailure('This reset link is invalid or has expired. Please request a new one.');
    }
    auth_validate_password($password);
    db()->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), now_sql(), $row['id']]);
    db()->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?')->execute([now_sql(), $row['reset_id']]);
    auth_login_session($row, false);
    return $row;
}
