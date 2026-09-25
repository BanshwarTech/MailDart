<?php
/**
 * 404 "Page not found" page (sent with HTTP status 404 and noindex; see index.php and includes/seo.php).
 * Vars: $currentUser (null or user).
 */
?>
<main class="min-h-screen bg-page text-slate-900 flex flex-col">
  <header class="border-b border-slate-200/60">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center">
      <a href="<?= e(url('home')) ?>" aria-label="MailDart home"><?php partial('app_logo', ['size' => 'md', 'showTagline' => false]); ?></a>
    </div>
  </header>

  <div class="flex-1 flex items-center justify-center px-5 py-16">
    <div class="w-full max-w-lg text-center animate-fade-in">
      <p class="font-mono text-sm font-semibold tracking-[0.14em] text-slate-400">ERROR 404</p>
      <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-[-0.02em] text-slate-900">Page not found</h1>
      <p class="mt-4 text-slate-500">The page you are looking for doesn&#8217;t exist or has moved. Check the address, or head back to a page that works.</p>

      <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <?php if ($currentUser): ?>
          <a href="<?= e(url('overview')) ?>" class="inline-flex items-center justify-center gap-2 h-11 px-5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-xl shadow-button transition">
            <?= icon('LayoutDashboard', 'w-4 h-4') ?>Open Dashboard
          </a>
        <?php else: ?>
          <a href="<?= e(url('register')) ?>" class="inline-flex items-center justify-center gap-2 h-11 px-5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-xl shadow-button transition">
            Create free account<?= icon('ArrowRight', 'w-4 h-4') ?>
          </a>
        <?php endif; ?>
        <a href="<?= e(url('home')) ?>" class="inline-flex items-center justify-center gap-2 h-11 px-5 text-sm font-medium text-slate-700 bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl transition">
          <?= icon('ArrowLeft', 'w-4 h-4') ?>Back to home
        </a>
      </div>
    </div>
  </div>
</main>
