<?php
/**
 * HTML document shell used by every page.
 * Expects: $title (string), $view (views/pages/<view>.php). Other variables from index.php are visible to the view.
 * Pages can set $bodyClass, push extra page scripts into $pageScripts (asset paths or https:// URLs) and add
 * JSON-LD nodes to $seoSchema. All SEO tags (title, description, canonical, Open Graph, structured data) come from
 * includes/seo.php.
 */
/** @var string $view  set by index.php before this layout is included */
$view = $view ?? 'landing';
$pageScripts = $pageScripts ?? [];
ob_start();
require MD_ROOT . '/views/pages/' . $view . '.php';
$pageHtml = (string) ob_get_clean();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="theme-color" content="#0a1b2e">
  <meta name="color-scheme" content="dark">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($view === '404'): ?>
  <base href="<?= e(app_url()) ?>"><?php /* keeps relative links working on nested 404 URLs like /foo/bar */ ?>
<?php endif; ?>
  <?= seo_head($view, $page ?? 'home', $seoSchema ?? []) . "\n" ?>
  <link rel="icon" href="<?= e(asset('img/favicon.ico')) ?>" sizes="32x32">
  <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
  <link rel="manifest" href="site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php /* Web fonts load without blocking the first paint (font-display: swap) */ ?>
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap"></noscript>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?= $pageHtml ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (array_unique($pageScripts) as $script): ?>
<script src="<?= e(str_starts_with($script, 'https://') ? $script : asset($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
