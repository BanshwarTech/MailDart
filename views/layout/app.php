<?php
/**
 * Dashboard shell (was the main return of App.tsx): sidebar, top bar, page content, step navigation, footer, modals.
 * Variables from index.php: $page, $view, $title, $currentUser, $userRow, $campaign, $smtp, $stepDone, $activeNav, $pageSubtitle
 */
$pageScripts = $pageScripts ?? [];
$flashes = take_flashes();
$excelToast = $_SESSION['excel_toast'] ?? null;
$modal = (string) ($_GET['modal'] ?? '');

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
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%234a7fa7'/><stop offset='1' stop-color='%231a3d63'/></linearGradient></defs><rect width='32' height='32' rx='8' fill='url(%23g)'/><g transform='translate(4 4)'><path d='M21 3 9.6 21l-2.4-7.8L21 3Z' fill='white'/><path d='M21 3 7.2 13.2 2.8 11.4 21 3Z' fill='white' fill-opacity='.8'/></g></svg>">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="min-h-screen bg-page text-slate-900 font-sans selection:bg-[#34506D] selection:text-white">

  <?php /* Mobile drawer state (CSS only): the header menu button is a <label for="mobile-nav-toggle"> */ ?>
  <input type="checkbox" id="mobile-nav-toggle" class="peer sr-only" aria-hidden="true">

  <?php partial('sidebar', [
      'campaign' => $campaign,
      'smtp' => $smtp,
      'activeSection' => $page,
      'stepDone' => $stepDone,
      'counts' => ['recipients' => count($campaign['recipients']), 'logs' => count($campaign['logs'])],
      'isSending' => $campaign['status'] === 'running',
  ]); ?>

  <div class="lg:pl-64 min-h-screen flex flex-col min-w-0">

    <?php partial('header', [
        'campaign' => $campaign,
        'smtp' => $smtp,
        'title' => $activeNav['label'],
        'subtitle' => $pageSubtitle,
        'currentUser' => $currentUser,
    ]); ?>

    <main class="flex-1 min-w-0 w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 space-y-6">

      <!-- Mobile page title -->
      <div class="lg:hidden">
        <h1 class="text-lg font-semibold text-slate-900"><?= e($activeNav['label']) ?></h1>
        <p class="text-sm text-slate-500"><?= e($pageSubtitle) ?></p>
      </div>

      <?php partial('flash_messages', ['flashes' => $flashes]); ?>

      <?php if ($page === 'editor'): ?>
        <!-- Campaign details (step 3 only) -->
        <div class="relative z-10 bg-surface ring-1 ring-slate-200/80 rounded-2xl shadow-card">
          <div class="absolute inset-y-0 right-0 w-1/2 rounded-r-2xl bg-dots [mask-image:linear-gradient(to_left,black,transparent)] pointer-events-none"></div>
          <div class="relative p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
              <div class="w-12 h-12 shrink-0 flex items-center justify-center bg-gradient-to-br from-brand-500 to-accent-600 text-white rounded-xl shadow-button">
                <?= icon('Flame', 'w-5 h-5') ?>
              </div>
              <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Campaign details</p>
                <div class="flex flex-wrap items-center gap-2">
                  <form method="post" action="./" data-autosubmit>
                    <?= form_action('app.updateDetails') ?>
                    <input
                      type="text"
                      name="companyName"
                      value="<?= e($campaign['companyName']) ?>"
                      placeholder="Enter Company Name"
                      class="bg-transparent border border-transparent rounded-md hover:border-slate-200 hover:bg-slate-50 focus:bg-surface focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-lg md:text-xl font-semibold tracking-tight text-slate-900 placeholder:text-slate-400 focus:outline-none px-1.5 py-0.5 -ml-1.5 transition"
                    >
                  </form>
                  <?php partial('category_select', ['id' => 'campaign-festival', 'value' => $campaign['festival'], 'action' => 'app.updateDetails', 'field' => 'festival']); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($excelToast): ?>
        <!-- Excel Variables Auto-Generated Banner Notification -->
        <div class="p-4 bg-brand-50 border border-brand-200 rounded-xl shadow-sm flex items-center justify-between gap-4 animate-fade-in">
          <div class="flex items-start sm:items-center gap-3">
            <div class="p-2 rounded-lg bg-surface text-brand-700 border border-brand-200 shrink-0">
              <?= icon('Sparkles', 'w-5 h-5') ?>
            </div>
            <div class="space-y-1">
              <p class="text-xs sm:text-sm font-semibold text-brand-900 flex items-center gap-1.5">
                <span>⚡ <?= e($excelToast['message']) ?></span>
              </p>
              <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                <span class="text-[11px] text-brand-800/80">Tags ready to use:</span>
                <?php foreach ($excelToast['variables'] as $v): ?>
                  <code class="px-2 py-0.5 bg-surface border border-brand-200 text-brand-700 font-mono text-[11px] rounded"><?= e('{{' . $v . '}}') ?></code>
                <?php endforeach; ?>
                <span class="text-[11px] text-brand-700 ml-1">(you can use these in your email template right away)</span>
              </div>
            </div>
          </div>
          <?= post_button('app.dismissToast', [], icon('X', 'w-4 h-4'), 'p-1.5 text-brand-700 hover:text-brand-900 rounded-lg hover:bg-brand-100 transition shrink-0', ['title' => 'Dismiss', 'form_class' => 'shrink-0']) ?>
        </div>
      <?php endif; ?>

      <?= $pageHtml ?>

      <?php partial('step_nav', ['current' => $page, 'stepDone' => $stepDone]); ?>

    </main>

    <footer class="mt-auto border-t border-slate-200/80 py-5 text-xs text-slate-500">
      <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
        <span>© <?= date('Y') ?> MailDart Pro — All-Purpose Email Marketing &amp; 5-Minute Staggered Dispatcher</span>
        <div class="flex items-center gap-3 text-slate-400">
          <span>Anti-Spam Throttling</span>
          <span class="w-1 h-1 rounded-full bg-slate-300"></span>
          <span>Custom HTML Templates</span>
          <span class="w-1 h-1 rounded-full bg-slate-300"></span>
          <span>Excel Dynamic Variables</span>
        </div>
      </div>
    </footer>
  </div>

  <?php
  // Modals are opened with ?modal=… on any dashboard page; closing links back to the same page without it
  $closeUrl = url($page, array_diff_key($_GET, ['page' => 1, 'modal' => 1]));
  if ($modal === 'ai') {
      partial('ai_template_modal', ['campaign' => $campaign, 'closeUrl' => $closeUrl]);
  } elseif ($modal === 'test') {
      partial('test_email_modal', ['campaign' => $campaign, 'smtp' => $smtp, 'closeUrl' => $closeUrl]);
  }
  ?>

  <?php if ($campaign['status'] === 'running'): ?>
    <?php /* Keeps the staggered dispatch going on every dashboard page (see assets/js/app.js) */ ?>
    <div hidden data-dispatch-runner data-remaining="<?= (int) $campaign['remainingSeconds'] ?>"></div>
  <?php endif; ?>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (array_unique($pageScripts) as $script): ?>
<script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
