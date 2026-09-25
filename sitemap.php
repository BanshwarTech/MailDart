<?php
/**
 * XML sitemap, served at /sitemap.xml (see .htaccess).
 * Lists only public pages; dashboard pages need a login and are kept out of search engines.
 * The base URL comes from APP_URL in .env, or is auto-detected from the request.
 */

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$base = app_url();

// [path, view file (for <lastmod>), changefreq, priority]
$pages = [
    ['', 'views/pages/landing.php', 'weekly', '1.0'],
    ['register', 'views/pages/auth.php', 'monthly', '0.8'],
    ['login', 'views/pages/auth.php', 'monthly', '0.6'],
];

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // the sitemap itself should not show up in search results

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($pages as [$path, $file, $changefreq, $priority]) {
    $mtime = @filemtime(__DIR__ . '/' . $file) ?: time();
    echo "  <url>\n";
    echo '    <loc>', htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8'), "</loc>\n";
    echo '    <lastmod>', date('Y-m-d', $mtime), "</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
