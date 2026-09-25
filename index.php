<?php
/**
 * MailDart front controller.
 *
 *   GET  index.php?page=<page>            render a page (views/pages/<page>.php)
 *   POST index.php  action=<ns>.<name>    run a handler from actions/<ns>.php, then redirect (Post/Redirect/Get)
 *   GET  index.php?action=<ns>.download…  read-only handlers whose name starts with "download" (CSV / Excel files)
 */

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

md_session_start();

const PUBLIC_PAGES = ['home', 'login', 'register', 'forgot', 'reset'];
const APP_PAGES = ['overview', 'settings', 'recipients', 'editor', 'dispatch', 'logs', 'guide'];
const ACTION_NAMESPACES = ['auth', 'app', 'smtp', 'recipients', 'editor', 'dispatch', 'logs'];
const PUBLIC_ACTION_NAMESPACES = ['auth'];

/** Load actions/<ns>.php in its own scope so its variables can never leak into (or clobber) the router's. */
function load_action_handlers(string $ns): array
{
    return require __DIR__ . "/actions/$ns.php";
}

try {
    /* ------------------------------------------------------------------ */
    /* Actions                                                             */
    /* ------------------------------------------------------------------ */
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $actionName = $method === 'POST' ? (string) ($_POST['action'] ?? '') : (string) ($_GET['action'] ?? '');

    if ($actionName !== '') {
        if (!preg_match('/^([a-z]+)\.([A-Za-z]+)$/', $actionName, $m) || !in_array($m[1], ACTION_NAMESPACES, true)) {
            http_response_code(404);
            exit('Unknown action.');
        }
        [, $ns, $name] = $m;

        if ($method === 'POST' && !csrf_valid($_POST['_csrf'] ?? null)) {
            flash('error', 'Your session expired. Please try again.');
            redirect_back('home');
        }
        if ($method === 'GET' && !str_starts_with($name, 'download')) {
            http_response_code(405);
            exit('This action requires POST.');
        }

        $user = current_user_row();
        if (!$user && !in_array($ns, PUBLIC_ACTION_NAMESPACES, true)) {
            flash('error', 'Please sign in to continue.');
            redirect('login');
        }

        $handlers = load_action_handlers($ns);
        if (!isset($handlers[$name])) {
            http_response_code(404);
            exit('Unknown action.');
        }
        $handlers[$name]($user);
        redirect_back('overview'); // handlers normally redirect themselves
    }

    /* ------------------------------------------------------------------ */
    /* Pages                                                               */
    /* ------------------------------------------------------------------ */
    // Old-style links (index.php, index.php?page=x&…, ./?page=x) -> 301 to the clean URL (x?…)
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    parse_str((string) parse_url($requestUri, PHP_URL_QUERY), $rawQuery);
    if (str_ends_with((string) parse_url($requestUri, PHP_URL_PATH), '/index.php') || isset($rawQuery['page'])) {
        $target = is_string($rawQuery['page'] ?? null) ? $rawQuery['page'] : 'home';
        $clean = url($target, $rawQuery);
        header('Location: ' . app_url() . (str_starts_with($clean, './') ? substr($clean, 2) : $clean), true, 301);
        exit;
    }

    $page = (string) ($_GET['page'] ?? 'home');
    // Unknown URL -> real 404 (not the home page with 200, which search engines treat as a "soft 404")
    if (!in_array($page, PUBLIC_PAGES, true) && !in_array($page, APP_PAGES, true)) {
        http_response_code(404);
        $currentUser = ($row = current_user_row()) ? public_user($row) : null;
        $page = '404';
        $view = '404';
        $title = 'Page not found · MailDart Pro';
        require __DIR__ . '/views/layout/base.php';
        exit;
    }

    $userRow = current_user_row();
    $currentUser = $userRow ? public_user($userRow) : null;

    // Logged-out visitors on a dashboard page go to Sign In (and come back after login)
    if (!$currentUser && in_array($page, APP_PAGES, true)) {
        redirect('login', ['next' => $page]);
    }
    // Logged-in users never stay on the Sign In / Sign Up pages
    if ($currentUser && in_array($page, ['login', 'register', 'forgot'], true)) {
        $next = (string) ($_GET['next'] ?? '');
        redirect(in_array($next, APP_PAGES, true) ? $next : 'overview');
    }

    if ($page === 'home') {
        $title = 'MailDart Pro - Staggered Email Campaign Dispatcher';
        $view = 'landing';
        require __DIR__ . '/views/layout/base.php';
        exit;
    }

    if (in_array($page, PUBLIC_PAGES, true)) {
        $title = match ($page) {
            'register' => 'Create account · MailDart Pro',
            'forgot' => 'Reset password · MailDart Pro',
            'reset' => 'Choose a new password · MailDart Pro',
            default => 'Sign in · MailDart Pro',
        };
        $view = in_array($page, ['login', 'register'], true) ? 'auth' : $page;
        require __DIR__ . '/views/layout/base.php';
        exit;
    }

    // Dashboard pages
    $campaign = campaign_load((int) $userRow['id']);
    $smtp = smtp_load((int) $userRow['id']);
    $stepDone = step_done($campaign, $smtp);
    $activeNav = find_nav_item($page);
    $pageSubtitle = isset($activeNav['step'])
        ? 'Step ' . $activeNav['step'] . ' of ' . total_steps() . ' · ' . $activeNav['description']
        : $activeNav['description'];
    $title = $activeNav['label'] . ' · MailDart Pro';
    $view = $page;
    require __DIR__ . '/views/layout/app.php';
} catch (PDOException $e) {
    error_log('MailDart DB error: ' . $e->getMessage());
    http_response_code(500);
    $dbError = $e->getMessage();
    require __DIR__ . '/views/layout/db_error.php';
}
