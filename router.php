<?php
/**
 * Router for PHP's built-in dev server, which ignores .htaccess:
 *
 *   php -S localhost:8000 router.php
 *
 * Mirrors the clean-URL rules from .htaccess (Apache/XAMPP does not need this file):
 *   real static file            -> served as-is (dotfiles / config files are denied)
 *   /api/preview                -> api/preview.php
 *   /login, /settings, …        -> index.php?page=login
 *   anything else               -> index.php?page=404
 */

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$file = __DIR__ . $path;

// Never serve dotfiles (.env, .git) or config/docs, same as the <FilesMatch> blocks in .htaccess
if (preg_match('#(^|/)\.#', $path) || preg_match('#\.(md|json|lock|sql|log|example)$#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Trailing slash on a clean URL -> drop it (/recipients/ -> /recipients)
if ($path !== '/' && str_ends_with($path, '/') && !is_dir($file)) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . rtrim($path, '/') . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

// Existing non-PHP file (css, js, images, sitemap.xml, robots.txt, …): let the built-in server send it
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false;
}

$run = function (string $script, string $scriptName): void {
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $scriptName;
    $_SERVER['SCRIPT_FILENAME'] = $script;
    require $script;
};

// Direct .php requests (index.php, api/preview.php) and /api/preview -> api/preview.php
if (str_ends_with($path, '.php') && is_file($file)) {
    $run($file, $path);
    return;
}
if ($path !== '/' && is_file($file . '.php')) {
    $run($file . '.php', $path . '.php');
    return;
}

// /login, /overview, … -> index.php?page=…
if ($path !== '/') {
    $page = ltrim($path, '/');
    $_GET['page'] = preg_match('/^[a-z]+$/', $page) ? $page : '404';
}
$run(__DIR__ . '/index.php', '/index.php');
