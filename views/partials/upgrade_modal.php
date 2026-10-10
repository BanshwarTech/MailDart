<?php
/**
 * Shown instead of a feature's modal when the user's plan does not include it (e.g. ?modal=ai on Starter).
 * Vars: $feature ('excel' | 'ai' | 'export'), $userRow, $closeUrl
 */
$limits = user_limits($userRow);
$label = PLAN_FEATURE_LABELS[$feature] ?? 'This feature';
?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in" data-modal-root>
  <div class="relative w-full max-w-md bg-surface border border-slate-200 rounded-2xl shadow-modal p-6 md:p-7 text-center">
    <a href="<?= e($closeUrl) ?>" data-modal-close class="absolute top-4 right-4 p-2 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition" aria-label="Close">
      <?= icon('X', 'w-5 h-5') ?>
    </a>
    <div class="mx-auto w-12 h-12 rounded-2xl bg-amber-50 ring-1 ring-amber-200 text-amber-600 flex items-center justify-center">
      <?= icon('Lock', 'w-5 h-5') ?>
    </div>
    <h2 class="mt-4 text-lg font-semibold text-slate-900"><?= e($label) ?> is a paid feature</h2>
    <p class="mt-2 text-sm text-slate-500">
      Your <strong class="text-slate-700"><?= e($limits['plan']) ?></strong> plan doesn’t include <?= e(mb_strtolower($label)) ?>.
      Upgrade to unlock it, along with higher recipient limits.
    </p>
    <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
      <a href="<?= e(url('billing')) ?>" class="inline-flex items-center justify-center gap-1.5 h-10 px-4 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
        <?= icon('Award', 'w-4 h-4') ?> View plans
      </a>
      <a href="<?= e($closeUrl) ?>" class="inline-flex items-center justify-center h-10 px-4 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 rounded-lg transition">
        Not now
      </a>
    </div>
  </div>
</div>
