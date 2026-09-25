<?php
/**
 * Google reCAPTCHA v3 (invisible, score based) for the login / sign-up / forgot-password forms.
 *
 * Enabled only when RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY are set in .env.
 * Browser side: assets/js/recaptcha.js asks Google for a token on submit and puts it in the
 * hidden "g-recaptcha-response" field. Server side: recaptcha_verify() checks it with Google.
 */

declare(strict_types=1);

function recaptcha_enabled(): bool
{
    return (bool) (env_secret('RECAPTCHA_SITE_KEY') && env_secret('RECAPTCHA_SECRET_KEY'));
}

function recaptcha_site_key(): string
{
    return (string) env_secret('RECAPTCHA_SITE_KEY');
}

/** Minimum score (0.0 = bot … 1.0 = human) to accept; Google recommends 0.5. */
function recaptcha_min_score(): float
{
    $v = (float) env('RECAPTCHA_MIN_SCORE', '0.5');
    return $v > 0 && $v <= 1 ? $v : 0.5;
}

/**
 * Attributes + hidden field for a protected form:
 *   <form ... <?= recaptcha_form_attrs('login') ?>> <?= recaptcha_field() ?>
 */
function recaptcha_form_attrs(string $action): string
{
    if (!recaptcha_enabled()) {
        return '';
    }
    return ' data-recaptcha-action="' . e($action) . '" data-recaptcha-site-key="' . e(recaptcha_site_key()) . '"';
}

function recaptcha_field(): string
{
    return recaptcha_enabled() ? '<input type="hidden" name="g-recaptcha-response" value="">' : '';
}

/** Scripts a page must load when it has a protected form (Google's api.js + our small helper). */
function recaptcha_scripts(): array
{
    if (!recaptcha_enabled()) {
        return [];
    }
    return ['https://www.google.com/recaptcha/api.js?render=' . rawurlencode(recaptcha_site_key())];
}

/** Small JSON POST helper for Google APIs (form-encoded body). Returns the decoded JSON or throws RuntimeException. */
function google_http_post(string $url, array $fields, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false) {
        throw new RuntimeException('Could not reach Google: ' . $err);
    }
    $data = json_decode((string) $body, true);
    if (!is_array($data)) {
        throw new RuntimeException("Unexpected response from Google (HTTP $status).");
    }
    return $data;
}

/**
 * Verify the token posted with a protected form. Throws AuthFailure (friendly message) when the check fails.
 * No-op when reCAPTCHA is not configured.
 */
function recaptcha_verify(string $expectedAction): void
{
    if (!recaptcha_enabled()) {
        return;
    }
    $token = (string) ($_POST['g-recaptcha-response'] ?? '');
    if ($token === '') {
        throw new AuthFailure('Security check did not load. Please refresh the page and try again.');
    }

    try {
        $res = google_http_post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env_secret('RECAPTCHA_SECRET_KEY'),
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (RuntimeException $e) {
        error_log('MailDart reCAPTCHA: ' . $e->getMessage());
        throw new AuthFailure('Security check could not be completed. Please check your connection and try again.');
    }

    $ok = !empty($res['success'])
        && ($res['action'] ?? '') === $expectedAction
        && (float) ($res['score'] ?? 0) >= recaptcha_min_score();

    if (!$ok) {
        error_log('MailDart reCAPTCHA rejected: ' . json_encode(array_intersect_key($res, array_flip(['success', 'score', 'action', 'hostname', 'error-codes']))));
        throw new AuthFailure("We couldn't verify that you're human. Please try again.");
    }
}
