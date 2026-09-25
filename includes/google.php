<?php
/**
 * "Sign in with Google" — OAuth 2.0 / OpenID Connect authorization-code flow in plain PHP (curl, no SDK).
 *
 * Enabled only when GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET are set in .env.
 * Authorized redirect URI to register in Google Cloud Console:  <APP_URL>google-auth
 * Flow: google-auth.php (start) -> Google consent -> google-auth.php?code=…&state=… (callback) -> signed in.
 */

declare(strict_types=1);

const GOOGLE_AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
const GOOGLE_USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';

function google_enabled(): bool
{
    return (bool) (env_secret('GOOGLE_CLIENT_ID') && env_secret('GOOGLE_CLIENT_SECRET'));
}

function google_redirect_uri(): string
{
    return app_url() . 'google-auth';
}

function base64url(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/** Build the Google consent URL and remember state + PKCE verifier in the session. */
function google_begin(string $next, bool $remember, string $from): string
{
    md_session_start();
    $state = bin2hex(random_bytes(24));
    $verifier = base64url(random_bytes(48));
    $_SESSION['google_oauth'] = [
        'state' => $state,
        'verifier' => $verifier,
        'next' => $next,
        'remember' => $remember,
        'from' => $from === 'register' ? 'register' : 'login',
        'created' => time(),
    ];

    return GOOGLE_AUTH_ENDPOINT . '?' . http_build_query([
        'client_id' => env_secret('GOOGLE_CLIENT_ID'),
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'code_challenge' => base64url(hash('sha256', $verifier, true)),
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
        'access_type' => 'online',
    ]);
}

/**
 * Handle Google's redirect back. Returns ['user' => row, 'next' => page, 'from' => login|register].
 * Throws AuthFailure with a friendly message on any problem.
 */
function google_complete(string $code, string $state): array
{
    md_session_start();
    $saved = $_SESSION['google_oauth'] ?? null;
    unset($_SESSION['google_oauth']);

    if (!$saved || !hash_equals($saved['state'], $state) || time() - (int) $saved['created'] > 600) {
        throw new AuthFailure('Google sign-in expired or was interrupted. Please try again.');
    }

    try {
        $token = google_http_post(GOOGLE_TOKEN_ENDPOINT, [
            'code' => $code,
            'client_id' => env_secret('GOOGLE_CLIENT_ID'),
            'client_secret' => env_secret('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => google_redirect_uri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $saved['verifier'],
        ]);
        if (empty($token['access_token'])) {
            error_log('MailDart Google token error: ' . json_encode($token));
            throw new AuthFailure('Google sign-in failed. Please try again.');
        }
        $profile = google_http_get(GOOGLE_USERINFO_ENDPOINT, $token['access_token']);
    } catch (RuntimeException $e) {
        error_log('MailDart Google: ' . $e->getMessage());
        throw new AuthFailure('Network error. Please check your internet connection.');
    }

    if (empty($profile['sub']) || empty($profile['email'])) {
        throw new AuthFailure('Google did not share your email address. Please try again.');
    }
    if (empty($profile['email_verified'])) {
        throw new AuthFailure('Your Google email address is not verified yet.');
    }

    $row = google_find_or_create_user($profile);
    auth_login_session($row, (bool) $saved['remember']);

    return ['user' => $row, 'next' => $saved['next'], 'from' => $saved['from']];
}

function google_http_get(string $url, string $accessToken): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $accessToken],
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false) {
        throw new RuntimeException('Could not reach Google: ' . $err);
    }
    $data = json_decode((string) $body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Unexpected userinfo response from Google.');
    }
    return $data;
}

/**
 * Match the Google account to a MailDart user:
 *   1. same Google account id           -> that user
 *   2. same (Google-verified) email     -> link Google to the existing account
 *   3. otherwise                        -> create a new account (random password; can be set later via "Forgot password")
 */
function google_find_or_create_user(array $profile): array
{
    $sub = (string) $profile['sub'];
    $email = strtolower(trim((string) $profile['email']));
    $name = trim((string) ($profile['name'] ?? '')) ?: explode('@', $email)[0];
    $photo = substr((string) ($profile['picture'] ?? ''), 0, 500);
    $now = now_sql();

    $stmt = db()->prepare('SELECT * FROM users WHERE google_id = ?');
    $stmt->execute([$sub]);
    $row = $stmt->fetch();

    if (!$row) {
        $row = auth_find_by_email($email);
        if ($row) {
            db()->prepare('UPDATE users SET google_id = ?, photo_url = IF(photo_url = \'\', ?, photo_url), updated_at = ? WHERE id = ?')
                ->execute([$sub, $photo, $now, $row['id']]);
        } else {
            db()->prepare('INSERT INTO users (uid, email, password_hash, display_name, photo_url, google_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([bin2hex(random_bytes(16)), $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), mb_substr($name, 0, 120), $photo, $sub, $now, $now]);
        }
        $stmt->execute([$sub]);
        $row = $stmt->fetch();
    } else {
        db()->prepare('UPDATE users SET photo_url = ?, updated_at = ? WHERE id = ?')->execute([$photo ?: $row['photo_url'], $now, $row['id']]);
    }

    return $row;
}
