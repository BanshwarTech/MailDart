<?php
/** Flash messages queued by actions with flash($type, $message). Vars: $flashes */
$styles = [
    'success' => ['bg-brand-50 border-brand-200 text-brand-800', 'CheckCircle2', 'text-brand-600'],
    'error' => ['bg-red-50 border-red-200 text-red-800', 'AlertCircle', 'text-red-600'],
    'warning' => ['bg-amber-50 border-amber-200 text-amber-800', 'AlertTriangle', 'text-amber-600'],
    'info' => ['bg-sky-50 border-sky-200 text-sky-800', 'Info', 'text-sky-600'],
    'upgrade' => ['bg-amber-50 border-amber-200 text-amber-800', 'Lock', 'text-amber-600'],
];
foreach ($flashes ?? [] as $f):
    [$box, $ic, $icColor] = $styles[$f['type']] ?? $styles['info'];
?>
  <div class="p-3.5 border rounded-xl flex items-start gap-2.5 text-sm animate-fade-in <?= $box ?>" role="status" data-flash>
    <?= icon($ic, 'w-4 h-4 mt-0.5 shrink-0 ' . $icColor) ?>
    <span class="flex-1"><?= e($f['message']) ?></span>
    <?php if ($f['type'] === 'upgrade'): ?>
      <a href="<?= e(url('billing')) ?>" class="shrink-0 inline-flex items-center gap-1 h-7 px-2.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
        <?= icon('Award', 'w-3.5 h-3.5') ?> View plans
      </a>
    <?php endif; ?>
    <button type="button" class="p-0.5 opacity-70 hover:opacity-100 transition" data-dismiss aria-label="Dismiss"><?= icon('X', 'w-3.5 h-3.5') ?></button>
  </div>
<?php endforeach; ?>
