<?php
/**
 * Previous / next navigation shown at the bottom of every workflow step (was StepNav.tsx).
 * Vars: $current, $stepDone
 */
$steps = step_items();
$index = null;
foreach ($steps as $i => $s) {
    if ($s['id'] === $current) {
        $index = $i;
        break;
    }
}
if ($index === null) {
    return;
}
$prev = $steps[$index - 1] ?? null;
$next = $steps[$index + 1] ?? null;
$done = !empty($stepDone[$current]);
$total = total_steps();
?>
<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 rounded-2xl bg-surface border border-slate-200/80 shadow-card px-5 py-4">
  <div class="flex items-center gap-3">
    <span class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-sm font-semibold <?= $done ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-300' ?>">
      <?= $done ? icon('CheckCircle2', 'w-4 h-4') : (int) ($index + 1) ?>
    </span>
    <div>
      <p class="text-sm font-semibold text-slate-900">
        Step <?= (int) ($index + 1) ?> of <?= $total ?> · <?= e($steps[$index]['label']) ?>
      </p>
      <p class="text-xs text-slate-500"><?= $done ? 'Completed — you can move on.' : 'Complete this step, then continue.' ?></p>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <?php if ($prev): ?>
      <a
        href="<?= e(url($prev['id'])) ?>"
        class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-medium text-slate-700 bg-surface hover:bg-slate-100 border border-slate-300 rounded-lg transition"
      >
        <?= icon('ArrowLeft', 'w-3.5 h-3.5') ?>
        <?= e($prev['label']) ?>
      </a>
    <?php endif; ?>
    <?php if ($next): ?>
      <a
        href="<?= e(url($next['id'])) ?>"
        class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
      >
        Next: <?= e($next['label']) ?>
        <?= icon('ArrowRight', 'w-3.5 h-3.5') ?>
      </a>
    <?php else: ?>
      <a
        href="<?= e(url('overview')) ?>"
        class="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
      >
        Back to Overview
        <?= icon('ArrowRight', 'w-3.5 h-3.5') ?>
      </a>
    <?php endif; ?>
  </div>
</div>
