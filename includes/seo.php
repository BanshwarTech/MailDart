<?php
/**
 * SEO: per-page titles/descriptions, canonical URLs, Open Graph / Twitter cards, JSON-LD structured data,
 * robots directives and search-console verification tags. Rendered into <head> by views/layout/base.php.
 *
 * Optional .env settings:
 *   APP_NAME=MailDart Pro                 brand used in titles and structured data
 *   SEO_OG_IMAGE=https://…/share.png      custom share image (default: assets/img/og-image.png, 1200×630)
 *   SEO_TWITTER_HANDLE=@yourbrand         adds twitter:site
 *   SEO_ORG_SAME_AS=https://x.com/…,https://www.linkedin.com/company/…   social profiles (comma separated)
 *   GOOGLE_SITE_VERIFICATION=…  BING_SITE_VERIFICATION=…               Search Console / Bing Webmaster tags
 */

declare(strict_types=1);

function seo_brand(): string
{
    return env('APP_NAME', 'MailDart Pro');
}

/** Meta data for each public page. Dashboard pages are noindex and don't need entries. */
function seo_pages(): array
{
    $brand = seo_brand();
    return [
        'landing' => [
            'title' => "$brand — Staggered Email Campaigns That Land in the Inbox",
            'description' => 'Send festival greetings, newsletters and reminders one email at a time from your own Gmail or SMTP account. Personalised HTML templates, Excel import, live countdown and delivery logs.',
            'path' => '',
            'type' => 'website',
        ],
        'register' => [
            'title' => "Create your free account · $brand",
            'description' => "Sign up for $brand free: connect Gmail or any SMTP account, import recipients from Excel and send personalised, staggered email campaigns that avoid spam filters.",
            'path' => 'register',
            'type' => 'website',
        ],
        'login' => [
            'title' => "Sign in · $brand",
            'description' => "Sign in to $brand to manage your email campaigns, recipients, templates and delivery logs.",
            'path' => 'login',
            'type' => 'website',
        ],
        'forgot' => ['title' => "Reset your password · $brand", 'noindex' => true],
        'reset' => ['title' => "Choose a new password · $brand", 'noindex' => true],
        '404' => ['title' => "Page not found · $brand", 'noindex' => true],
    ];
}

function seo_og_image(): array
{
    $custom = env('SEO_OG_IMAGE');
    if ($custom) {
        return ['url' => $custom, 'width' => null, 'height' => null];
    }
    return ['url' => app_url() . 'assets/img/og-image.png', 'width' => 1200, 'height' => 630];
}

/**
 * All <head> SEO tags for a public page.
 * $view: landing | auth | forgot | reset | 404 ; $page: the routed page name ; $extraSchema: additional JSON-LD nodes
 */
function seo_head(string $view, string $page, array $extraSchema = []): string
{
    $key = $view === 'auth' ? $page : $view;
    $meta = seo_pages()[$key] ?? seo_pages()['landing'];
    $brand = seo_brand();
    $title = $meta['title'];
    $out = [];

    $out[] = '<title>' . e($title) . '</title>';

    if (!empty($meta['noindex'])) {
        $out[] = '<meta name="robots" content="noindex, nofollow">';
        return implode("\n  ", $out);
    }

    $url = app_url() . $meta['path'];
    $description = $meta['description'];
    $image = seo_og_image();

    $out[] = '<meta name="description" content="' . e($description) . '">';
    $out[] = '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">';
    $out[] = '<link rel="canonical" href="' . e($url) . '">';

    // Open Graph (Facebook, LinkedIn, WhatsApp, Slack…)
    $out[] = '<meta property="og:site_name" content="' . e($brand) . '">';
    $out[] = '<meta property="og:locale" content="en_US">';
    $out[] = '<meta property="og:type" content="' . e($meta['type']) . '">';
    $out[] = '<meta property="og:url" content="' . e($url) . '">';
    $out[] = '<meta property="og:title" content="' . e($title) . '">';
    $out[] = '<meta property="og:description" content="' . e($description) . '">';
    $out[] = '<meta property="og:image" content="' . e($image['url']) . '">';
    if ($image['width']) {
        $out[] = '<meta property="og:image:width" content="' . $image['width'] . '">';
        $out[] = '<meta property="og:image:height" content="' . $image['height'] . '">';
        $out[] = '<meta property="og:image:type" content="image/png">';
    }
    $out[] = '<meta property="og:image:alt" content="' . e("$brand — staggered email campaign dispatcher") . '">';

    // Twitter / X
    $out[] = '<meta name="twitter:card" content="summary_large_image">';
    $out[] = '<meta name="twitter:title" content="' . e($title) . '">';
    $out[] = '<meta name="twitter:description" content="' . e($description) . '">';
    $out[] = '<meta name="twitter:image" content="' . e($image['url']) . '">';
    if ($handle = env('SEO_TWITTER_HANDLE')) {
        $out[] = '<meta name="twitter:site" content="' . e($handle) . '">';
    }

    // Search engine ownership verification
    if ($g = env('GOOGLE_SITE_VERIFICATION')) {
        $out[] = '<meta name="google-site-verification" content="' . e($g) . '">';
    }
    if ($b = env('BING_SITE_VERIFICATION')) {
        $out[] = '<meta name="msvalidate.01" content="' . e($b) . '">';
    }

    // Structured data
    $schema = seo_schema($key, $url, $title, $description, $extraSchema);
    $out[] = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';

    return implode("\n  ", $out);
}

/** JSON-LD graph: Organization + WebSite + WebPage (+ SoftwareApplication and FAQPage on the landing page). */
function seo_schema(string $key, string $url, string $title, string $description, array $extra): array
{
    $base = app_url();
    $brand = seo_brand();
    $orgId = $base . '#organization';
    $siteId = $base . '#website';

    $org = [
        '@type' => 'Organization',
        '@id' => $orgId,
        'name' => $brand,
        'url' => $base,
        'logo' => ['@type' => 'ImageObject', 'url' => $base . 'assets/img/icon-512.png', 'width' => 512, 'height' => 512],
    ];
    $sameAs = array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_ORG_SAME_AS', '')))));
    if ($sameAs) {
        $org['sameAs'] = $sameAs;
    }

    $graph = [
        $org,
        ['@type' => 'WebSite', '@id' => $siteId, 'url' => $base, 'name' => $brand, 'publisher' => ['@id' => $orgId], 'inLanguage' => 'en'],
        [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => $siteId],
            'about' => ['@id' => $orgId],
            'inLanguage' => 'en',
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => seo_og_image()['url']],
        ],
    ];

    if ($key === 'landing') {
        $graph[] = [
            '@type' => 'SoftwareApplication',
            '@id' => $base . '#software',
            'name' => $brand,
            'url' => $base,
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'Email marketing',
            'operatingSystem' => 'Web browser',
            'description' => $description,
            'image' => seo_og_image()['url'],
            'publisher' => ['@id' => $orgId],
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
            'featureList' => [
                'Staggered sending: one email at a time on a timer',
                'Gmail, Elastic Email, Brevo, SendGrid, Amazon SES and any SMTP',
                'Personalised HTML email templates with {{tags}}',
                'Excel / CSV recipient import with custom variables',
                'Sandbox mode, live countdown and delivery logs with CSV export',
            ],
        ];
    }

    return ['@context' => 'https://schema.org', '@graph' => array_merge($graph, $extra)];
}

/** FAQPage JSON-LD node from [['q' => …, 'a' => …], …] (must match the FAQ text visible on the page). */
function seo_faq_schema(array $faqs): array
{
    return [
        '@type' => 'FAQPage',
        '@id' => app_url() . '#faq',
        'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ], $faqs),
    ];
}

/* ---------------------------------------------------------------------------
 * Static sitemap.xml + robots.txt (written by tools/build-seo-files.php)
 * ------------------------------------------------------------------------- */

/** Public pages listed in sitemap.xml: [path, view file (for <lastmod>), changefreq, priority] */
function seo_sitemap_pages(): array
{
    return [
        ['', 'views/pages/landing.php', 'weekly', '1.0'],
        ['register', 'views/pages/auth.php', 'monthly', '0.8'],
        ['login', 'views/pages/auth.php', 'monthly', '0.6'],
    ];
}

function seo_sitemap_xml(string $base): string
{
    $x = fn (string $v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
    foreach (seo_sitemap_pages() as [$path, $file, $changefreq, $priority]) {
        $mtime = @filemtime(MD_ROOT . '/' . $file) ?: time();
        $out .= "  <url>\n"
            . '    <loc>' . $x($base . $path) . "</loc>\n"
            . '    <lastmod>' . date('Y-m-d', $mtime) . "</lastmod>\n"
            . "    <changefreq>{$changefreq}</changefreq>\n"
            . "    <priority>{$priority}</priority>\n";
        if ($path === '') {
            $image = env('SEO_OG_IMAGE') ?: $base . 'assets/img/og-image.png';
            $out .= '    <image:image><image:loc>' . $x($image) . "</image:loc></image:image>\n";
        }
        $out .= "  </url>\n";
    }
    return $out . "</urlset>\n";
}

function seo_robots_txt(string $base): string
{
    $path = rtrim((string) parse_url($base, PHP_URL_PATH), '/'); // e.g. /core/MailDart, or '' at a domain root
    $lines = [
        '# robots.txt for ' . seo_brand() . ' — generated by tools/build-seo-files.php',
        'User-agent: *',
        "Allow: {$path}/\$",
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
        "Disallow: {$path}/google-auth",
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
        "Disallow: {$path}/tools/",
        "Disallow: {$path}/storage/",
        '',
        "Sitemap: {$base}sitemap.xml",
    ];
    return implode("\n", $lines) . "\n";
}
