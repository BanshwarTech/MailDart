<?php
/**
 * "Sign in with Google" endpoint, served at the clean URL /google-auth.
 *   GET google-auth?next=<page>&from=login|register   -> redirect to Google's consent screen
 *   GET google-auth?code=…&state=…                     -> Google's callback: sign in, then go to the dashboard
 */

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

const GOOGLE_APP_PAGES = ['overview', 'settings', 'recipients', 'editor', 'dispatch', 'logs', 'guide'];

try {
    md_session_start();
    if (!google_enabled()) {
        flash('error', 'Google sign-in is not set up on this server yet. Ask your administrator to add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to the .env file.');
        redirect('login');
    }

    // Callback from Google
    if (isset($_GET['code']) || isset($_GET['error'])) {
        $from = ($_SESSION['google_oauth']['from'] ?? 'login') === 'register' ? 'register' : 'login';
        if (isset($_GET['error'])) {
            unset($_SESSION['google_oauth']);
            // access_denied = the visitor closed / cancelled the Google window: just go back quietly
            if ($_GET['error'] !== 'access_denied') {
                flash('error', 'Google sign-in failed. Please try again.');
            }
            redirect($from);
        }
        try {
            $result = google_complete((string) $_GET['code'], (string) ($_GET['state'] ?? ''));
        } catch (AuthFailure $e) {
            flash('error', $e->getMessage());
            redirect($from);
        }
        redirect(in_array($result['next'], GOOGLE_APP_PAGES, true) ? $result['next'] : 'overview');
    }

    // Start
    if (current_user_row()) {
        redirect('overview');
    }
    $next = (string) ($_GET['next'] ?? '');
    $from = (string) ($_GET['from'] ?? 'login');
    header('Location: ' . google_begin(in_array($next, GOOGLE_APP_PAGES, true) ? $next : '', true, $from), true, 302);
    exit;
} catch (PDOException $e) {
    error_log('MailDart DB error: ' . $e->getMessage());
    http_response_code(500);
    $dbError = $e->getMessage();
    require __DIR__ . '/views/layout/db_error.php';
}
