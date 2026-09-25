<?php
/**
 * Generic dropdown select (was SelectMenu.tsx). Must be placed INSIDE the caller's <form>.
 * Vars: $id, $name (form field), $value, $options (list of ['value','label','description'?,'badge'?]),
 *       $placeholder = 'Select…', $monoDescription = false, $className = '', $menuClassName = '',
 *       $ariaLabel = null, $mode = 'radio'|'submit' (default 'radio')
 *
 * mode 'submit': options are submit buttons (name=$name value=...), just like category_select.php.
 * mode 'radio': options are labels wrapping a real (visually hidden) radio input, so the value posts
 * correctly with the surrounding form even without JS. A small enhancement at the end of assets/js/app.js
 * updates the trigger text/badge and closes the menu when a radio changes.
 */
$placeholder = $placeholder ?? 'Select…';
$monoDescription = $monoDescription ?? false;
$className = $className ?? '';
$menuClassName = $menuClassName ?? '';
$ariaLabel = $ariaLabel ?? null;
$mode = $mode ?? 'radio';

$current = null;
foreach ($options as $o) {
    if ($o['value'] === $value) {
        $current = $o;
        break;
    }
}
?>
<details data-dropdown data-select-menu id="<?= e($id) ?>" class="group relative <?= e($className) ?>">
  <summary
    class="list-none [&::-webkit-details-marker]:hidden cursor-pointer w-full h-9 flex items-center gap-2 pl-3 pr-2.5 bg-surface rounded-lg text-left text-sm ring-1 ring-inset transition ring-slate-300 hover:ring-slate-400 group-open:ring-2 group-open:ring-brand-500 group-open:outline-4 group-open:outline-brand-500/10"<?= attrs(['aria-label' => $ariaLabel]) ?>
  >
    <span class="flex-1 min-w-0 truncate <?= $current ? 'text-slate-900 font-medium' : 'text-slate-400' ?>" data-trigger-label>
      <?= e($current ? $current['label'] : $placeholder) ?>
    </span>
    <span
      class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200"
      data-trigger-badge<?= attrs(['hidden' => !$current || empty($current['badge'])]) ?>
    >
      <?= e($current['badge'] ?? '') ?>
    </span>
    <?= icon('ChevronDown', 'w-4 h-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180') ?>
  </summary>

  <div
    role="<?= $mode === 'radio' ? 'radiogroup' : 'listbox' ?>"
    class="absolute left-0 right-0 top-full mt-1.5 z-50 min-w-[220px] max-h-72 overflow-y-auto bg-surface rounded-xl ring-1 ring-slate-200/80 shadow-popover p-1 animate-fade-in <?= e($menuClassName) ?>"
  >
    <?php if (!$options): ?>
      <p class="px-3 py-2.5 text-sm text-slate-500">No options available</p>
    <?php endif; ?>

    <?php foreach ($options as $opt): $selected = $opt['value'] === $value; ?>
      <?php if ($mode === 'submit'): ?>
        <button
          type="submit"
          name="<?= e($name) ?>"
          value="<?= e($opt['value']) ?>"
          role="option"
          aria-selected="<?= $selected ? 'true' : 'false' ?>"
          class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition text-left hover:bg-slate-100 focus:bg-slate-100 focus:outline-none"
        >
          <span class="flex-1 min-w-0">
            <span class="flex items-center gap-2 text-sm <?= $selected ? 'font-semibold text-brand-700' : 'font-medium text-slate-800' ?>">
              <span class="truncate"><?= e($opt['label']) ?></span>
              <?php if (!empty($opt['badge'])): ?>
                <span class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200"><?= e($opt['badge']) ?></span>
              <?php endif; ?>
            </span>
            <?php if (!empty($opt['description'])): ?>
              <span class="block truncate text-slate-500 mt-0.5 <?= $monoDescription ? 'font-mono text-[10.5px]' : 'text-[11px]' ?>"><?= e($opt['description']) ?></span>
            <?php endif; ?>
          </span>
          <?php if ($selected): ?><?= icon('Check', 'w-4 h-4 shrink-0 text-brand-700') ?><?php endif; ?>
        </button>
      <?php else: ?>
        <label data-option-label class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition hover:bg-slate-100">
          <input type="radio" name="<?= e($name) ?>" value="<?= e($opt['value']) ?>" class="sr-only peer"<?= checked($selected) ?>>
          <span class="flex-1 min-w-0">
            <span class="flex items-center gap-2 text-sm font-medium text-slate-800 peer-checked:font-semibold peer-checked:text-brand-700" data-option-text>
              <span class="truncate"><?= e($opt['label']) ?></span>
              <?php if (!empty($opt['badge'])): ?>
                <span data-option-badge class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200"><?= e($opt['badge']) ?></span>
              <?php endif; ?>
            </span>
            <?php if (!empty($opt['description'])): ?>
              <span class="block truncate text-slate-500 mt-0.5 <?= $monoDescription ? 'font-mono text-[10.5px]' : 'text-[11px]' ?>"><?= e($opt['description']) ?></span>
            <?php endif; ?>
          </span>
          <span class="hidden peer-checked:inline-flex shrink-0"><?= icon('Check', 'w-4 h-4 text-brand-700') ?></span>
        </label>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</details>
