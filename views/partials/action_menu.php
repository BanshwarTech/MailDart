<?php
/**
 * "More actions" overflow menu (was ActionMenu.tsx).
 * Vars: $id (optional), $label = 'More actions',
 *       $items: list of [label, icon?, href | action (+ fields?), danger?, hidden?, separated?, confirm?]
 * Items with `href` render as links (e.g. downloads); items with `action` render as CSRF-protected POST buttons.
 */
$label = $label ?? 'More actions';
$visible = array_values(array_filter($items ?? [], fn ($i) => empty($i['hidden'])));
if (!$visible) {
    return;
}
?>
<details data-dropdown class="group relative"<?= attrs(['id' => $id ?? null]) ?>>
  <summary
    role="button"
    aria-haspopup="menu"
    aria-label="<?= e($label) ?>"
    title="<?= e($label) ?>"
    class="list-none [&::-webkit-details-marker]:hidden inline-flex items-center justify-center h-9 w-9 rounded-lg border shadow-xs transition bg-surface border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400 group-open:bg-slate-100 group-open:border-slate-400 group-open:text-slate-900"
  >
    <?= icon('MoreHorizontal', 'w-4 h-4') ?>
  </summary>

  <div role="menu" class="absolute right-0 top-full mt-1.5 z-50 w-56 bg-surface rounded-xl ring-1 ring-slate-200/80 shadow-popover p-1 animate-fade-in">
    <?php foreach ($visible as $item):
        $danger = !empty($item['danger']);
        $itemClass = 'w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm text-left transition ' . ($danger ? 'text-red-600 hover:bg-red-50' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900');
        $inner = (!empty($item['icon']) ? icon($item['icon'], 'w-4 h-4 ' . ($danger ? 'text-red-500' : 'text-slate-400')) : '') . e($item['label']);
    ?>
      <?php if (!empty($item['separated'])): ?>
        <div class="my-1 h-px bg-slate-100"></div>
      <?php endif; ?>
      <?php if (!empty($item['href'])): ?>
        <a role="menuitem" href="<?= e($item['href']) ?>" class="<?= $itemClass ?>"><?= $inner ?></a>
      <?php else: ?>
        <?= post_button($item['action'], $item['fields'] ?? [], $inner, $itemClass, ['role' => 'menuitem', 'confirm' => $item['confirm'] ?? null, 'form_class' => 'block']) ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</details>
