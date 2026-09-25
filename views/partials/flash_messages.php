<?php
/** Flash messages queued by actions with flash($type, $message). Vars: $flashes */
$styles = [
    'success' => ['bg-brand-50 border-brand-200 text-brand-800', 'CheckCircle2', 'text-brand-600'],
    'error' => ['bg-red-50 border-red-200 text-red-800', 'AlertCircle', 'text-red-600'],
    'warning' => ['bg-amber-50 border-amber-200 text-amber-800', 'AlertTriangle', 'text-amber-600'],
    'info' => ['bg-sky-50 border-sky-200 text-sky-800', 'Info', 'text-sky-600'],
];
foreach ($flashes ?? [] as $f):
    [$box, $ic, $icColor] = $styles[$f['type']] ?? $styles['info'];
?>
  <div class="p-3.5 border rounded-xl flex items-start gap-2.5 text-sm animate-fade-in <?= $box ?>" role="status" data-flash>
    <?= icon($ic, 'w-4 h-4 mt-0.5 shrink-0 ' . $icColor) ?>
    <span class="flex-1"><?= e($f['message']) ?></span>
    <button type="button" class="p-0.5 opacity-70 hover:opacity-100 transition" data-dismiss aria-label="Dismiss"><?= icon('X', 'w-3.5 h-3.5') ?></button>
  </div>
<?php endforeach; ?>
