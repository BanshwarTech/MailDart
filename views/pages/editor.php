<?php
/**
 * Template Studio (was src/components/TemplateEditor.tsx). Vars from index.php: $campaign, $currentUser, $userRow, $smtp, ...
 * State that was React useState is now GET query params on this same page:
 *   view      editor | split | preview   (default split)
 *   device    desktop | mobile           (default desktop)
 *   recipient id of the recipient to preview as (default: first recipient)
 *   addvar    1 to show the "Add a custom variable" modal
 * Subject / discount code / HTML template are saved for real via the editor.save action (see actions/editor.php).
 */

$view = in_array($_GET['view'] ?? '', ['editor', 'split', 'preview'], true) ? $_GET['view'] : 'split';
$device = ($_GET['device'] ?? '') === 'mobile' ? 'mobile' : 'desktop';
$addVar = ($_GET['addvar'] ?? '') === '1';

/** Build a URL back to this page, keeping the current query params except the ones given in $overrides. */
$editorUrl = function (array $overrides) {
    return url('editor', array_merge(array_diff_key($_GET, ['page' => 1]), $overrides));
};

/* Distinct custom variables from Excel imports / recipient data (excludes name & email), insertion-ordered. */
$customVariableKeys = [];
$seenVarKeys = [];
foreach ($campaign['customVariables'] as $k) {
    $lower = strtolower(trim((string) $k));
    if ($lower !== '' && $lower !== 'name' && $lower !== 'email' && !isset($seenVarKeys[$lower])) {
        $seenVarKeys[$lower] = true;
        $customVariableKeys[] = trim((string) $k);
    }
}
foreach ($campaign['recipients'] as $r) {
    if (is_array($r['customData'] ?? null)) {
        foreach (array_keys($r['customData']) as $k) {
            $lower = strtolower(trim((string) $k));
            if ($lower !== '' && $lower !== 'name' && $lower !== 'email' && !isset($seenVarKeys[$lower])) {
                $seenVarKeys[$lower] = true;
                $customVariableKeys[] = trim((string) $k);
            }
        }
    }
}

/* Selected recipient for the live preview (same fallback sample recipient as the old TSX default). */
$defaultPreviewRecipient = [
    'id' => 'preview-default',
    'name' => 'Aarav Sharma',
    'email' => 'aarav.sharma@example.com',
    'customData' => ['city' => 'Mumbai', 'gift' => 'Royal Sweets Hamper'],
    'status' => 'pending',
];
$selectedRecipientId = (string) ($_GET['recipient'] ?? ($campaign['recipients'][0]['id'] ?? ''));
$currentRecipient = recipient_find($campaign, $selectedRecipientId) ?? ($campaign['recipients'][0] ?? $defaultPreviewRecipient);

$personalizedPreviewHtml = replace_placeholders($campaign['htmlTemplate'], $currentRecipient, $campaign);
$personalizedSubject = replace_placeholders($campaign['subject'], $currentRecipient, $campaign);

/* "Insert all" Excel-variables block (was handleInsertAllExcelVariables). */
$excelBlockRows = implode("\n", array_map(
    fn ($key) => '    <p style="margin: 4px 0; font-size: 13px; color: #cbd5e1;"><strong style="color: #ffffff; text-transform: capitalize;">' . str_replace('_', ' ', $key) . ':</strong> {{' . $key . '}}</p>',
    $customVariableKeys
));
$excelBlockHtml = "\n<!-- Auto-Generated Excel Variables Block -->\n<div style=\"margin: 20px 0; padding: 14px 18px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(26, 61, 99, 0.35); border-left: 4px solid #1a3d63; border-radius: 8px;\">\n  <p style=\"margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #1a3d63;\">✨ Your Personalised Details:</p>\n{$excelBlockRows}\n</div>\n";

$builtInTags = [
    ['tag' => '{{name}}', 'hint' => 'Recipient name'],
    ['tag' => '{{company}}', 'hint' => 'Your company name'],
    ['tag' => '{{discount}}', 'hint' => 'Discount code'],
    ['tag' => '{{festival}}', 'hint' => 'Occasion / category'],
    ['tag' => '{{email}}', 'hint' => 'Recipient email'],
];

$chip = 'inline-flex items-center h-7 px-1.5 rounded-md bg-surface ring-1 ring-inset ring-slate-200 text-slate-600 font-mono text-[11px] hover:bg-brand-50 hover:ring-brand-200 hover:text-brand-800 transition';

$viewTabs = [
    ['id' => 'editor', 'label' => 'Code', 'icon' => 'Code', 'className' => ''],
    ['id' => 'split', 'label' => 'Split', 'icon' => 'Split', 'className' => 'hidden lg:inline-flex'],
    ['id' => 'preview', 'label' => 'Preview', 'icon' => 'Eye', 'className' => ''],
];

$presetDisplayName = fn (array $preset) => explode(' (', $preset['name'])[0];

$pageScripts[] = 'js/editor.js';
?>
<div class="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col h-full">

  <?php /* Hidden form target for the Setup + Code Editor fields below (they use the form="" attribute so the
           Preview panel's own recipient-picker form, further down, never ends up nested inside this one). */ ?>
  <form id="editor-save-form" method="post" action="./" data-autosubmit>
    <?= form_action('editor.save', url('editor', array_diff_key($_GET, ['page' => 1]))) ?>
  </form>

  <!-- Header -->
  <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
    <div class="flex items-center gap-3 min-w-0">
      <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
        <?= icon('Code', 'w-5 h-5') ?>
      </div>
      <div class="min-w-0">
        <h2 class="text-base font-semibold tracking-tight text-slate-900">Email Template Studio</h2>
        <p class="text-sm text-slate-500">Choose a template, personalise it and preview it live</p>
      </div>
    </div>

    <div class="flex flex-wrap xl:flex-nowrap items-center gap-2 shrink-0">
      <!-- View switch -->
      <div class="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
        <?php foreach ($viewTabs as $t): ?>
          <?php
          $base = $t['className'] !== '' ? $t['className'] : 'inline-flex';
          $activeCls = $view === $t['id'] ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900';
          ?>
          <a href="<?= e($editorUrl(['view' => $t['id']])) ?>" class="<?= e($base . ' items-center gap-1.5 h-7 px-3 text-xs font-medium rounded-md transition ' . $activeCls) ?>">
            <?= icon($t['icon'], 'w-3.5 h-3.5') ?>
            <?= e($t['label']) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <button type="submit" form="editor-save-form"
              class="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
              title="Save subject, discount code and HTML template">
        <?= icon('Save', 'w-3.5 h-3.5') ?>
        <span>Save</span>
      </button>

      <button type="button" data-copy="<?= e($campaign['htmlTemplate']) ?>" data-copied-label="Copied!"
              class="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
              title="Copy HTML to clipboard">
        <?= icon('Copy', 'w-3.5 h-3.5') ?>
        <span data-copy-label>Copy Code</span>
      </button>

      <a href="<?= e(url('editor', ['modal' => 'test'])) ?>"
         class="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
         title="Send this email to yourself to check it in a real inbox">
        <?= icon('Send', 'w-3.5 h-3.5') ?>
        <span>Send test</span>
      </a>

      <a href="<?= e(url('editor', ['modal' => 'ai'])) ?>"
         class="inline-flex items-center gap-1.5 h-9 px-3.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition shadow-button"
         title="Generate festive HTML templates using NVIDIA NIM or Gemini AI">
        <?= icon('Sparkles', 'w-3.5 h-3.5') ?>
        <span>AI Studio</span>
      </a>
    </div>
  </div>

  <!-- Setup: template, subject & discount -->
  <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80 space-y-5">
    <div>
      <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-2">
        <?= icon('Layers', 'w-3.5 h-3.5 text-slate-400') ?>
        Start from a template
      </p>
      <div class="relative">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 pr-8 [scrollbar-width:thin]">
          <?php foreach (preset_templates() as $preset): ?>
            <?php $isCurrent = $campaign['festival'] === $preset['festival']; ?>
            <?= post_button(
                'editor.applyPreset',
                ['presetId' => $preset['id']],
                ($isCurrent ? icon('Check', 'w-3.5 h-3.5') : '') . e($presetDisplayName($preset)),
                'shrink-0 inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-medium whitespace-nowrap ring-1 ring-inset transition ' . ($isCurrent ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-surface text-slate-600 ring-slate-200 hover:ring-slate-300 hover:text-slate-900'),
                ['confirm' => 'Load "' . $preset['name'] . '"? This will replace the current HTML template with the festive design.', 'form_class' => 'shrink-0']
            ) ?>
          <?php endforeach; ?>
        </div>
        <div class="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-surface to-transparent"></div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_240px] gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Subject Line</label>
        <input
          type="text"
          id="template-subject"
          form="editor-save-form"
          name="subject"
          value="<?= e($campaign['subject']) ?>"
          placeholder="e.g. Happy Diwali from {{company}} to {{name}}! 🪔"
          class="w-full h-10 px-3 text-sm bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
        >
        <p class="mt-1.5 text-[11px] text-slate-400">Supports {{name}}, {{company}}, {{city}} and other tags.</p>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Default Discount Code</label>
        <input
          type="text"
          form="editor-save-form"
          name="discountCode"
          value="<?= e($campaign['discountCode']) ?>"
          placeholder="e.g. FESTIVE50"
          class="w-full h-10 px-3 text-sm bg-surface border border-slate-300 rounded-lg text-slate-900 font-mono font-semibold tracking-wide uppercase focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
        >
        <p class="mt-1.5 text-[11px] text-slate-400">Fallback for {{discount}}.</p>
      </div>
    </div>
  </div>

  <!-- Main Workspace: Code Editor & Live Preview -->
  <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 min-h-[560px] divide-y lg:divide-y-0 lg:divide-x divide-slate-200/80">

    <?php if ($view === 'editor' || $view === 'split'): ?>
      <!-- Code Editor Panel -->
      <div class="flex flex-col h-full min-w-0 bg-slate-50/60 <?= $view === 'editor' ? 'lg:col-span-2' : '' ?>">
        <!-- Editor toolbar: variables -->
        <div class="px-4 py-3 min-h-[61px] bg-surface border-b border-slate-200/80 flex items-center">
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mr-1" title="Click a tag to insert it at the cursor">
              <?= icon('Tag', 'w-3.5 h-3.5 text-slate-400') ?>
              Insert
            </span>
            <?php foreach ($builtInTags as $item): ?>
              <button type="button" class="<?= e($chip) ?>" data-insert-tag="<?= e($item['tag']) ?>" data-insert-target="template-html" data-copy="<?= e($item['tag']) ?>" title="<?= e($item['hint'] . ': insert ' . $item['tag']) ?>">
                <?= e($item['tag']) ?>
              </button>
            <?php endforeach; ?>

            <?php foreach ($customVariableKeys as $key): ?>
              <button type="button" class="<?= e($chip) ?> gap-1" data-insert-tag="<?= e('{{' . $key . '}}') ?>" data-insert-target="template-html" data-copy="<?= e('{{' . $key . '}}') ?>" title="<?= e('From your Excel sheet: insert {{' . $key . '}}') ?>">
                <?= icon('FileSpreadsheet', 'w-3 h-3 text-slate-400') ?>
                <?= e('{{' . $key . '}}') ?>
              </button>
            <?php endforeach; ?>

            <?php if ($customVariableKeys): ?>
              <button type="button" class="inline-flex items-center h-7 px-2.5 rounded-md text-[11px] font-semibold text-brand-700 hover:bg-brand-50 transition" data-insert-tag="<?= e($excelBlockHtml) ?>" data-insert-target="template-html" data-copy="<?= e($excelBlockHtml) ?>" title="Insert a block with all Excel variables">
                Insert all
              </button>
            <?php endif; ?>

            <a href="<?= e($editorUrl(['addvar' => 1])) ?>"
               class="inline-flex items-center justify-center h-7 w-7 rounded-md text-slate-500 border border-dashed border-slate-300 hover:text-brand-800 hover:border-brand-300 hover:bg-brand-50 transition"
               title="Add a custom tag (e.g. {{city}}, {{gift}}, {{order_id}})" aria-label="Add custom tag">
              <?= icon('Plus', 'w-3.5 h-3.5') ?>
            </a>
          </div>
        </div>

        <div class="px-4 py-2 border-b border-slate-200/80 flex items-center justify-between">
          <span class="font-mono text-[11px] font-medium text-slate-500">index.html <span class="font-sans text-slate-400">· auto-saves</span></span>
          <span class="text-[11px] text-slate-400 tabular-nums"><?= count(explode("\n", $campaign['htmlTemplate'])) ?> lines</span>
        </div>

        <textarea
          id="template-html"
          form="editor-save-form"
          name="htmlTemplate"
          placeholder="Paste your HTML email template here..."
          spellcheck="false"
          class="flex-1 w-full p-4 font-mono text-xs bg-transparent text-slate-800 resize-none focus:outline-none leading-relaxed selection:bg-brand-200 overflow-auto"
        ><?= e($campaign['htmlTemplate']) ?></textarea>
      </div>
    <?php endif; ?>

    <?php if ($view === 'preview' || $view === 'split'): ?>
      <!-- Live Preview Panel -->
      <div class="flex flex-col h-full min-w-0 bg-surface <?= $view === 'preview' ? 'lg:col-span-2' : '' ?>">

        <!-- Preview Controls Bar -->
        <div class="px-4 py-3 min-h-[61px] bg-surface border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
          <form method="get" action="./" class="flex items-center gap-2 min-w-0">
            <input type="hidden" name="page" value="editor">
            <input type="hidden" name="view" value="<?= e($view) ?>">
            <input type="hidden" name="device" value="<?= e($device) ?>">
            <span class="text-xs font-semibold text-slate-700 shrink-0">Preview as</span>
            <?php partial('select_menu', [
                'id' => 'preview-recipient',
                'name' => 'recipient',
                'value' => $selectedRecipientId,
                'options' => array_map(fn ($rec) => ['value' => $rec['id'], 'label' => $rec['name'] ?: $rec['email'], 'description' => $rec['email']], $campaign['recipients']),
                'placeholder' => 'No recipients yet',
                'monoDescription' => true,
                'className' => 'w-64 max-w-full',
                'ariaLabel' => 'Preview as recipient',
                'mode' => 'submit',
            ]); ?>
          </form>

          <!-- Device switch -->
          <div class="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
            <a href="<?= e($editorUrl(['device' => 'desktop'])) ?>"
               class="h-7 w-8 inline-flex items-center justify-center rounded-md transition <?= $device === 'desktop' ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>"
               title="Desktop View (600px)">
              <?= icon('Monitor', 'w-3.5 h-3.5') ?>
            </a>
            <a href="<?= e($editorUrl(['device' => 'mobile'])) ?>"
               class="h-7 w-8 inline-flex items-center justify-center rounded-md transition <?= $device === 'mobile' ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>"
               title="Mobile View (375px)">
              <?= icon('Smartphone', 'w-3.5 h-3.5') ?>
            </a>
          </div>
        </div>

        <!-- Email client style header -->
        <div class="px-4 py-3 border-b border-slate-200/80 text-xs space-y-1">
          <div class="flex items-baseline gap-2">
            <span class="text-slate-400 w-14 shrink-0">Subject</span>
            <span class="font-semibold text-slate-900 truncate" data-preview-subject><?= e($personalizedSubject ?: 'No Subject') ?></span>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-slate-400 w-14 shrink-0">To</span>
            <span class="text-slate-600 truncate"><?= e($currentRecipient['name']) ?> &lt;<?= e($currentRecipient['email']) ?>&gt;</span>
          </div>
          <?php if (!empty($currentRecipient['customData'])): ?>
            <div class="flex items-start gap-2 pt-1">
              <span class="text-slate-400 w-14 shrink-0 pt-0.5">Data</span>
              <div class="flex flex-wrap gap-1">
                <?php foreach (array_slice($currentRecipient['customData'], 0, 4, true) as $k => $v): ?>
                  <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10.5px]">
                    <span class="text-slate-400"><?= e($k) ?>:</span> <?= e($v) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Rendered HTML Iframe Container -->
        <div class="flex-1 p-5 overflow-auto flex items-start justify-center bg-slate-100/70">
          <div class="bg-surface rounded-2xl overflow-hidden shadow-card border border-slate-200/80 transition-all duration-300 <?= $device === 'desktop' ? 'w-full max-w-[620px] h-[550px]' : 'w-[375px] h-[580px] border-4 border-slate-300 rounded-3xl' ?>">
            <iframe
              id="template-preview-frame"
              title="Email Preview"
              srcdoc="<?= e($personalizedPreviewHtml) ?>"
              class="w-full h-full border-0"
              sandbox="allow-same-origin"
            ></iframe>
          </div>
        </div>

        <?php /* Read by assets/js/editor.js for the debounced live-preview POST to api/preview.php */ ?>
        <input type="hidden" id="preview-recipient-id" value="<?= e($selectedRecipientId) ?>">
      </div>
    <?php endif; ?>

  </div>

  <!-- Modal: Setup Custom Mail Variable -->
  <?php if ($addVar): ?>
    <?php $closeAddVarUrl = $editorUrl(['addvar' => null]); ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in" data-modal-root>
      <div class="w-full max-w-md bg-surface border border-slate-200 rounded-2xl p-6 shadow-modal text-slate-700">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
          <div class="flex items-center gap-2">
            <?= icon('Sliders', 'w-5 h-5 text-brand-700') ?>
            <h3 class="text-sm font-semibold text-slate-900">Add a Custom Variable</h3>
          </div>
          <a href="<?= e($closeAddVarUrl) ?>" data-modal-close class="text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg p-1">
            <?= icon('X', 'w-4 h-4') ?>
          </a>
        </div>

        <p class="text-xs text-slate-500 mb-4">
          Want to send personalised data to each recipient (like <code>city</code>, <code>gift</code>, <code>phone</code> or <code>order_id</code>)? Create a new variable here:
        </p>

        <form method="post" action="./" class="space-y-4">
          <?= form_action('editor.addVariable') ?>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Variable Name <span class="text-red-600">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-2 text-amber-600 font-mono text-xs">{{</span>
              <input
                type="text"
                id="var-name-input"
                name="varName"
                value="<?= e(old('varName')) ?>"
                placeholder="e.g. city, gift_hamper or order_id"
                required
                class="w-full pl-8 pr-8 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-amber-700 font-mono focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-semibold"
              >
              <span class="absolute right-3 top-2 text-amber-600 font-mono text-xs">}}</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">
              Wherever <code>{{<?= e(old('varName') ?: 'variable') ?>}}</code> appears in the email, it will be replaced with each recipient's value.
            </p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Default Value (for all recipients)
            </label>
            <input
              type="text"
              name="varDefaultVal"
              value="<?= e(old('varDefaultVal')) ?>"
              placeholder="e.g. Royal Sweets Box, Mumbai or Valued Customer"
              class="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            >
            <p class="text-[10px] text-slate-400 mt-1">
              Recipients who don't have this value will get the default value instead.
            </p>
          </div>

          <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-200">
            <a href="<?= e($closeAddVarUrl) ?>" class="px-3.5 py-1.5 text-xs text-slate-500 hover:text-slate-900">
              Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-lg transition flex items-center gap-1.5 shadow-button">
              <?= icon('CheckCircle2', 'w-4 h-4') ?>
              <span>Save Variable &amp; Insert in Template</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

</div>
