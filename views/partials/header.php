<?php
/**
 * Dashboard top bar (was Header.tsx).
 * Vars: $campaign, $smtp, $title, $subtitle, $currentUser
 *
 * Note: Header.tsx defines a getStatusBadge() helper that is never called from its JSX return, so it renders
 * nothing in the compiled app; it is intentionally not reproduced here (see the conversion report).
 */
$smtpStatus = smtp_status($smtp);
$smtpLabel = $smtpStatus === 'live'
    ? (($smtp['fromEmail'] ?: $smtp['username']))
    : ($smtpStatus === 'incomplete' ? 'SMTP not configured' : 'Sandbox mode');

$iconButtonClass = 'items-center gap-1.5 h-9 px-3 text-xs font-medium text-slate-700 bg-surface hover:bg-slate-50 ring-1 ring-inset ring-slate-200 hover:ring-slate-300 rounded-lg shadow-xs transition';

$displayName = $currentUser ? (($currentUser['displayName'] ?: explode('@', $currentUser['email'])[0])) : '';
$initial = mb_strtoupper(mb_substr(($currentUser['displayName'] ?? '') ?: (($currentUser['email'] ?? '') ?: 'U'), 0, 1));
?>
<header class="sticky top-0 z-20 bg-surface/75 backdrop-blur-xl border-b border-slate-200/80">
  <div class="h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
    <!-- Left: mobile menu + page title -->
    <div class="flex items-center gap-3 min-w-0">
      <label for="mobile-nav-toggle" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition cursor-pointer" aria-label="Open menu">
        <?= icon('Menu', 'w-5 h-5') ?>
      </label>
      <a href="<?= e(url('home')) ?>" class="lg:hidden" title="MailDart home">
        <?php partial('app_logo', ['size' => 'sm', 'showTagline' => false]); ?>
      </a>
      <div class="hidden lg:block min-w-0">
        <h1 class="text-[15px] font-semibold text-slate-900 leading-tight truncate"><?= e($title) ?></h1>
        <?php if ($subtitle): ?>
          <p class="text-xs text-slate-500 truncate"><?= e($subtitle) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: status + actions -->
    <div class="flex items-center gap-2">
      <a
        href="<?= e(url('settings')) ?>"
        class="<?= $iconButtonClass ?> inline-flex max-w-[220px]"
        title="Configure User SMTP Delivery Account"
      >
        <span class="w-2 h-2 rounded-full shrink-0 <?= $smtpStatus === 'live' ? 'bg-brand-500' : 'bg-amber-500' ?>"></span>
        <?= icon('Settings', 'w-4 h-4 text-slate-400') ?>
        <span class="hidden sm:inline text-[11px] truncate <?= $smtpStatus === 'live' ? 'font-mono text-slate-900' : 'font-medium text-amber-700' ?>">
          <?= e($smtpLabel) ?>
        </span>
      </a>

      <!-- User Auth Section -->
      <?php if ($currentUser): ?>
        <details data-dropdown class="relative">
          <summary class="list-none [&::-webkit-details-marker]:hidden flex items-center gap-2 h-9 pl-1 pr-2 rounded-full hover:bg-slate-100 transition cursor-pointer">
            <?php if (!empty($currentUser['photoURL'])): ?>
              <img
                src="<?= e($currentUser['photoURL']) ?>"
                alt="<?= e($currentUser['displayName'] ?: 'User') ?>"
                class="w-7 h-7 rounded-full object-cover ring-2 ring-surface shadow-sm"
              >
            <?php else: ?>
              <div class="w-7 h-7 rounded-full bg-gradient-to-br from-brand-500 to-accent-600 text-white text-xs font-semibold flex items-center justify-center ring-2 ring-surface shadow-sm">
                <?= e($initial) ?>
              </div>
            <?php endif; ?>
            <span class="hidden md:block max-w-[110px] truncate text-sm font-medium text-slate-700">
              <?= e($displayName) ?>
            </span>
            <?= icon('ChevronDown', 'w-3.5 h-3.5 text-slate-400') ?>
          </summary>

          <div class="absolute right-0 mt-2 w-64 bg-surface ring-1 ring-slate-200/80 rounded-xl shadow-popover p-1.5 z-50 animate-fade-in">
            <div class="px-3 pt-2 pb-3 mb-1 border-b border-slate-100">
              <p class="text-sm font-semibold text-slate-900 truncate">
                <?= e($currentUser['displayName'] ?: 'User') ?>
              </p>
              <p class="text-xs text-slate-500 truncate">
                <?= e($currentUser['email']) ?>
              </p>
              <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-50 ring-1 ring-inset ring-brand-200 text-[11px] font-medium text-brand-700">
                <?= icon('ShieldCheck', 'w-3 h-3') ?>
                <span>Account Sync Active (MySQL)</span>
              </div>
            </div>

            <a
              href="<?= e(url('settings')) ?>"
              class="w-full text-left px-3 py-2 text-sm text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg flex items-center gap-2.5 transition"
            >
              <?= icon('Settings', 'w-4 h-4 text-slate-400') ?>
              <span>My SMTP &amp; API Settings</span>
            </a>

            <?= post_button(
                'auth.logout',
                [],
                icon('LogOut', 'w-4 h-4') . '<span>Log Out</span>',
                'w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg flex items-center gap-2.5 transition',
                ['form_class' => 'w-full']
            ) ?>
          </div>
        </details>
      <?php else: ?>
        <a
          href="<?= e(url('login')) ?>"
          class="inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
        >
          <?= icon('User', 'w-4 h-4') ?>
          <span class="hidden sm:inline">Log In / Sign Up</span>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
