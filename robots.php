<?php
/**
 * robots.txt, served at /robots.txt (see .htaccess).
 * Allows the public landing / sign-up pages, keeps crawlers out of the dashboard, actions and internals,
 * and points to the sitemap using the site's real base URL.
 */

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$base = app_url();
$path = rtrim((string) parse_url($base, PHP_URL_PATH), '/'); // e.g. /core/MailDart

header('Content-Type: text/plain; charset=utf-8');

$lines = [
    'User-agent: *',
    "Allow: {$path}/$",
    "Allow: {$path}/login",
    "Allow: {$path}/register",
    "Allow: {$path}/assets/",
    '',
    '# Dashboard (login required), form actions and password-reset links',
    "Disallow: {$path}/overview",
    "Disallow: {$path}/settings",
    "Disallow: {$path}/recipients",
    "Disallow: {$path}/editor",
    "Disallow: {$path}/dispatch",
    "Disallow: {$path}/logs",
    "Disallow: {$path}/guide",
    "Disallow: {$path}/forgot",
    "Disallow: {$path}/reset",
    "Disallow: {$path}/*?action=",
    "Disallow: {$path}/*modal=",
    "Disallow: {$path}/index.php",
    '',
    '# Server internals',
    "Disallow: {$path}/api/",
    "Disallow: {$path}/actions/",
    "Disallow: {$path}/includes/",
    "Disallow: {$path}/views/",
    "Disallow: {$path}/lib/",
    "Disallow: {$path}/data/",
    "Disallow: {$path}/cron/",
    "Disallow: {$path}/storage/",
    '',
    "Sitemap: {$base}sitemap.xml",
];

echo implode("\n", $lines), "\n";
