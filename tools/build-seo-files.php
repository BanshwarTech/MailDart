<?php
/**
 * Writes the static sitemap.xml and robots.txt in the project root.
 * Run it again whenever the site address (APP_URL) or the public pages change:
 *
 *   php tools/build-seo-files.php                          uses APP_URL from .env
 *   php tools/build-seo-files.php https://mail.example.com/   explicit site address
 *
 * Without APP_URL or an argument it assumes XAMPP: http://localhost/<path inside htdocs>/
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require __DIR__ . '/../includes/bootstrap.php';

$base = $argv[1] ?? env_secret('APP_URL');
if (!$base) {
    $root = str_replace('\\', '/', MD_ROOT);
    $pos = stripos($root, '/htdocs/');
    $base = 'http://localhost/' . ($pos !== false ? substr($root, $pos + 8) : '');
}
$base = rtrim($base, '/') . '/';
if (!filter_var($base, FILTER_VALIDATE_URL) || !preg_match('#^https?://#', $base)) {
    fwrite(STDERR, "Invalid site address: $base\n");
    exit(1);
}

file_put_contents(MD_ROOT . '/sitemap.xml', seo_sitemap_xml($base));
file_put_contents(MD_ROOT . '/robots.txt', seo_robots_txt($base));

echo "Site address: {$base}\n";
echo "Wrote sitemap.xml (" . count(seo_sitemap_pages()) . " URLs) and robots.txt\n";
