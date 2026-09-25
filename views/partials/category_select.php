<?php
/**
 * Campaign category / festival picker (was CategorySelect.tsx). Renders its own <form> and posts the
 * chosen value immediately (works fully without JS; app.js only adds outside-click / Escape closing).
 * Vars: $id, $value, $action (POST action name), $field (post field name)
 */
$groups = [
    [
        'title' => 'Festivals & greetings',
        'options' => [
            ['value' => 'diwali', 'emoji' => '🪔', 'label' => 'Diwali Festival', 'hint' => 'Festival of lights wishes & offers'],
            ['value' => 'eid', 'emoji' => '🌙', 'label' => 'Eid Mubarak', 'hint' => 'Warm Eid greetings'],
            ['value' => 'christmas', 'emoji' => '🎄', 'label' => 'Christmas & Holiday', 'hint' => 'Season’s greetings'],
            ['value' => 'newyear', 'emoji' => '🎉', 'label' => 'New Year Greeting', 'hint' => 'Welcome the new year'],
            ['value' => 'holi', 'emoji' => '🎨', 'label' => 'Holi Festival', 'hint' => 'Festival of colours'],
        ],
    ],
    [
        'title' => 'Business campaigns',
        'options' => [
            ['value' => 'newsletter', 'emoji' => '📰', 'label' => 'Company Newsletter', 'hint' => 'Monthly updates & digests'],
            ['value' => 'product_launch', 'emoji' => '🚀', 'label' => 'New Product Launch', 'hint' => 'Announce a feature or product'],
            ['value' => 'followup_reminder', 'emoji' => '⏰', 'label' => 'Follow-up & Reminder', 'hint' => 'Payments, renewals, nudges'],
            ['value' => 'welcome_onboarding', 'emoji' => '👋', 'label' => 'Welcome & Onboarding', 'hint' => 'Greet new customers'],
            ['value' => 'custom', 'emoji' => '🎯', 'label' => 'General / Custom Campaign', 'hint' => 'Anything else'],
        ],
    ],
];

$current = null;
foreach ($groups as $group) {
    foreach ($group['options'] as $opt) {
        if ($opt['value'] === $value) {
            $current = $opt;
            break 2;
        }
    }
}
if (!$current) {
    $current = ['value' => $value, 'emoji' => '🎯', 'label' => str_replace('_', ' ', $value), 'hint' => ''];
}
?>
<form method="post" action="./" class="relative">
  <?= form_action($action) ?>
  <details data-dropdown id="<?= e($id) ?>" class="group relative">
    <summary
      title="Change campaign category"
      class="list-none [&::-webkit-details-marker]:hidden cursor-pointer inline-flex items-center gap-1.5 h-8 pl-2.5 pr-2 rounded-lg text-xs font-semibold ring-1 ring-inset transition bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100 group-open:bg-brand-600 group-open:text-white group-open:ring-brand-600 group-open:shadow-button"
    >
      <span class="text-sm leading-none"><?= e($current['emoji']) ?></span>
      <span class="capitalize"><?= e($current['label']) ?></span>
      <?= icon('ChevronDown', 'w-3.5 h-3.5 transition-transform group-open:rotate-180') ?>
    </summary>

    <div
      role="listbox"
      class="absolute left-0 top-full mt-2 z-40 w-[560px] max-w-[calc(100vw-2rem)] bg-surface rounded-xl ring-1 ring-slate-200/80 shadow-popover p-1.5 max-h-[min(70vh,520px)] overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-x-1 animate-fade-in"
    >
      <?php foreach ($groups as $gi => $group): ?>
        <div class="<?= $gi > 0 ? 'max-sm:mt-1 max-sm:pt-1 max-sm:border-t sm:border-l border-slate-100 sm:pl-1' : '' ?>">
          <p class="px-2.5 pt-1.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400"><?= e($group['title']) ?></p>
          <?php foreach ($group['options'] as $opt): $selected = $opt['value'] === $value; ?>
            <button
              type="submit"
              name="<?= e($field) ?>"
              value="<?= e($opt['value']) ?>"
              role="option"
              aria-selected="<?= $selected ? 'true' : 'false' ?>"
              class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg cursor-pointer transition text-left hover:bg-slate-100 focus:bg-slate-100 focus:outline-none"
            >
              <span class="w-7 h-7 shrink-0 rounded-md flex items-center justify-center text-sm <?= $selected ? 'bg-brand-50 ring-1 ring-inset ring-brand-200' : 'bg-slate-50 ring-1 ring-inset ring-slate-200/80' ?>">
                <?= e($opt['emoji']) ?>
              </span>
              <span class="flex-1 min-w-0">
                <span class="block text-[13px] leading-tight truncate <?= $selected ? 'font-semibold text-brand-700' : 'font-medium text-slate-800' ?>">
                  <?= e($opt['label']) ?>
                </span>
                <span class="block text-[11px] leading-tight text-slate-500 truncate mt-0.5"><?= e($opt['hint']) ?></span>
              </span>
              <?php if ($selected): ?><?= icon('Check', 'w-4 h-4 shrink-0 text-brand-700') ?><?php endif; ?>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </details>
</form>
