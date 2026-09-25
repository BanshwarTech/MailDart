<?php
/**
 * HTML document shell used by every page.
 * Expects: $title (string), $view (views/pages/<view>.php). Other variables from index.php are visible to the view.
 * Pages can set $bodyClass before including, and push extra page scripts into $pageScripts (asset paths).
 */
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
<?php if (in_array($view, ['forgot', 'reset'], true)): ?>
  <meta name="robots" content="noindex, nofollow">
<?php else: ?>
  <link rel="canonical" href="<?= e(app_url() . ($view === 'landing' ? '' : $page)) ?>">
<?php endif; ?>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%234a7fa7'/><stop offset='1' stop-color='%231a3d63'/></linearGradient></defs><rect width='32' height='32' rx='8' fill='url(%23g)'/><g transform='translate(4 4)'><path d='M21 3 9.6 21l-2.4-7.8L21 3Z' fill='white'/><path d='M21 3 7.2 13.2 2.8 11.4 21 3Z' fill='white' fill-opacity='.8'/></g></svg>">
  <title><?= e($title ?? 'MailDart Pro') ?></title>
  <meta name="description" content="Design festive HTML email templates and dispatch automated staggered campaigns to multiple recipients at custom intervals (e.g. every 5 minutes) with real-time logs and SMTP integration.">
  <meta property="og:title" content="MailDart Pro - Staggered Email Campaign Dispatcher">
  <meta property="og:description" content="Design festive HTML email templates and dispatch automated staggered campaigns to multiple recipients at custom intervals (e.g. every 5 minutes) with real-time logs and SMTP integration.">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?= $pageHtml ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (array_unique($pageScripts) as $script): ?>
<script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
