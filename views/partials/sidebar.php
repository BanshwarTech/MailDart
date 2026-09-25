<?php
/**
 * Dashboard sidebar navigation (was Sidebar.tsx).
 * Vars: $campaign, $smtp, $activeSection, $stepDone, $counts, $isSending
 *
 * The mobile drawer is CSS-only: views/layout/app.php renders
 * <input type="checkbox" id="mobile-nav-toggle" class="peer sr-only"> as the sibling right before this
 * partial's output, so every root element below can use peer-checked: to react to it.
 */
$navItems = nav_items();
$overviewItems = array_values(array_filter($navItems, fn ($n) => $n['id'] === 'overview'));
$stepItems = array_values(array_filter($navItems, fn ($n) => isset($n['step'])));
$helpItems = array_values(array_filter($navItems, fn ($n) => !isset($n['step']) && $n['id'] !== 'overview'));
$doneCount = count(array_filter($stepItems, fn ($n) => !empty($stepDone[$n['id']])));
$totalSteps = total_steps();

$total = count($campaign['recipients']);
$delivered = count(array_filter($campaign['recipients'], fn ($r) => $r['status'] === 'delivered'));
$progress = $total > 0 ? (int) round($delivered / $total * 100) : 0;
$smtpStatus = smtp_status($smtp);

$statusMeta = [
    'idle' => ['label' => 'Draft / Ready', 'dot' => 'bg-[#7A8CA6]'],
    'running' => ['label' => 'Sending', 'dot' => 'bg-amber-500 animate-pulse'],
    'paused' => ['label' => 'Paused', 'dot' => 'bg-amber-500'],
    'completed' => ['label' => 'Completed', 'dot' => 'bg-[#C4D2E1]'],
];
$status = $statusMeta[$campaign['status']] ?? $statusMeta['idle'];

/** One <li> nav row (was Sidebar.tsx renderItem). */
$renderItem = function (array $item) use ($activeSection, $stepDone, $counts, $isSending) {
    $active = $activeSection === $item['id'];
    $done = !empty($stepDone[$item['id']]);
    $hasCount = isset($counts[$item['id']]);
    ?>
    <li class="relative">
      <a
        href="<?= e(url($item['id'])) ?>"
        <?= $active ? 'aria-current="page"' : '' ?>
        class="group w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $active ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' ?>"
      >
        <?php if (isset($item['step'])): ?>
          <span class="relative z-10 w-5 h-5 shrink-0 rounded-full flex items-center justify-center text-[10px] font-bold <?= $done ? 'bg-brand-600 text-white' : ($active ? 'bg-surface ring-1 ring-inset ring-brand-400 text-brand-800' : 'bg-surface ring-1 ring-inset ring-slate-300 text-slate-500 group-hover:text-slate-700') ?>">
            <?= $done ? icon('Check', 'w-3 h-3', ['stroke-width' => 3]) : (int) $item['step'] ?>
          </span>
        <?php else: ?>
          <?= icon($item['icon'], 'w-[18px] h-[18px] shrink-0 ' . ($active ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-600')) ?>
        <?php endif; ?>
        <span class="flex-1 text-left"><?= e($item['label']) ?></span>
        <?php if ($item['id'] === 'dispatch' && $isSending): ?>
          <span class="relative flex w-2 h-2" title="Sending in progress">
            <span class="absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75 animate-ping"></span>
            <span class="relative inline-flex w-2 h-2 rounded-full bg-amber-500"></span>
          </span>
        <?php endif; ?>
        <?php if ($hasCount): ?>
          <span class="min-w-[22px] px-1.5 py-0.5 rounded-md text-[11px] font-semibold tabular-nums text-center <?= $active ? 'bg-surface text-brand-700 ring-1 ring-brand-100' : 'bg-slate-100 text-slate-500' ?>">
            <?= (int) $counts[$item['id']] ?>
          </span>
        <?php endif; ?>
      </a>
    </li>
    <?php
};

/** Sidebar body, rendered once for the desktop rail and once inside the mobile drawer (was Sidebar.tsx `content`). */
$renderContent = function () use ($overviewItems, $stepItems, $helpItems, $renderItem, $doneCount, $totalSteps, $campaign, $status, $delivered, $total, $progress, $smtpStatus) {
    ?>
    <div class="flex flex-col h-full">
      <!-- Brand -->
      <div class="h-16 px-5 flex items-center justify-between border-b border-slate-200/80">
        <a href="<?= e(url('home')) ?>" title="MailDart home" class="rounded-lg focus-visible:outline-offset-4">
          <?php partial('app_logo', ['size' => 'md', 'showTagline' => false]); ?>
        </a>
        <label for="mobile-nav-toggle" class="lg:hidden p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition cursor-pointer" aria-label="Close menu">
          <?= icon('X', 'w-5 h-5') ?>
        </label>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-6">
        <ul class="space-y-0.5"><?php foreach ($overviewItems as $item) {
            $renderItem($item);
        } ?></ul>

        <div>
          <div class="px-3 mb-2 flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Campaign steps</p>
            <span class="text-[11px] font-semibold text-slate-500 tabular-nums">
              <?= $doneCount ?>/<?= $totalSteps ?> done
            </span>
          </div>
          <ol class="relative space-y-0.5">
            <!-- Connector line between step numbers -->
            <span class="absolute left-[22px] top-5 bottom-5 w-px bg-slate-300/60" aria-hidden="true"></span>
            <?php foreach ($stepItems as $item) {
                $renderItem($item);
            } ?>
          </ol>
        </div>

        <div>
          <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Help</p>
          <ul class="space-y-0.5"><?php foreach ($helpItems as $item) {
              $renderItem($item);
          } ?></ul>
        </div>
      </nav>

      <!-- Campaign summary card -->
      <div class="p-3 border-t border-slate-200/80">
        <a
          href="<?= e(url('dispatch')) ?>"
          title="Open the Dispatcher"
          class="block w-full text-left rounded-xl bg-gradient-to-br from-[#1B3B5A] to-[#0A1B2E] ring-1 ring-inset ring-[#34506D]/70 hover:ring-[#557392] p-4 text-white shadow-card transition"
        >
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#A1B2C4]">Campaign</span>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#E1E8F0]">
              <span class="w-1.5 h-1.5 rounded-full <?= $status['dot'] ?>"></span>
              <?= e($status['label']) ?>
            </span>
          </div>
          <p class="mt-2 text-sm font-semibold text-[#F2F6F8] truncate"><?= e($campaign['companyName'] ?: 'Untitled campaign') ?></p>
          <div class="mt-3 flex items-baseline justify-between text-xs">
            <span class="text-[#A1B2C4]"><?= $delivered ?> / <?= $total ?> sent</span>
            <span class="font-semibold tabular-nums text-[#F2F6F8]"><?= $progress ?>%</span>
          </div>
          <div class="mt-1.5 h-1.5 rounded-full bg-white/10 overflow-hidden">
            <div class="h-full rounded-full bg-gradient-to-r from-[#557392] to-[#C4D2E1] transition-all duration-500" style="width: <?= $progress ?>%"></div>
          </div>
          <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between text-[11px]">
            <span class="text-[#A1B2C4]">Every <?= e((string) $campaign['intervalMinutes']) ?> min</span>
            <span class="<?= $smtpStatus === 'live' ? 'text-[#C4D2E1]' : 'text-amber-400' ?>">
              <?= e(['live' => '● Live SMTP', 'incomplete' => '● SMTP setup needed', 'sandbox' => '● Sandbox'][$smtpStatus]) ?>
            </span>
          </div>
        </a>
      </div>
    </div>
    <?php
};
?>
<!-- Desktop -->
<aside class="hidden lg:block fixed inset-y-0 left-0 z-30 w-64 bg-surface/80 backdrop-blur-xl border-r border-slate-200/80">
  <?php $renderContent(); ?>
</aside>

<!-- Mobile drawer (CSS-only via the #mobile-nav-toggle peer checkbox) -->
<div class="hidden peer-checked:block lg:hidden fixed inset-0 z-50">
  <label for="mobile-nav-toggle" class="absolute inset-0 bg-slate-950/40 backdrop-blur-[2px] animate-fade-in" aria-label="Close menu"></label>
  <aside class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-surface shadow-modal animate-slide-in">
    <?php $renderContent(); ?>
  </aside>
</div>
