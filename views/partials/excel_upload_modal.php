<?php
/**
 * Excel & CSV Upload modal (was src/components/ExcelUploadModal.tsx), shown on ?page=recipients&modal=excel.
 * Vars: $campaign, $closeUrl (link back to the recipients page without ?modal=excel).
 *
 * Flow (was useState in the TSX, now server/session state):
 *   1. Dropzone form posts the file to recipients.uploadExcel (multipart). The handler parses it with
 *      includes/excel.php and stores the ParseExcelResult in flash_data('excel_preview', ...), then redirects
 *      back here.
 *   2. This partial reads it with take_flash_data() and immediately re-flashes it, so the preview step survives
 *      as long as the modal stays open across reloads (the confirm/reset actions consume it for good).
 *   3. Confirm posts recipients.confirmImport (import mode + auto-insert-block choice); Reset ("Change File")
 *      posts recipients.resetExcelPreview.
 */
$currentCount = count($campaign['recipients']);

$preview = take_flash_data('excel_preview');
if ($preview) {
    flash_data('excel_preview', $preview); // keep it alive for one more render (see docblock above)
}
$uploadError = take_flash_data('excel_upload_error');
$fileName = $preview['fileName'] ?? null;
?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in overflow-y-auto" data-modal-root>
  <div class="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto bg-surface border border-slate-200 rounded-2xl shadow-modal text-slate-700 p-6 md:p-8 my-4">

    <!-- Close Button -->
    <a href="<?= e($closeUrl) ?>" data-modal-close class="absolute top-5 right-5 text-slate-500 hover:text-slate-900 p-1.5 rounded-lg hover:bg-slate-100 transition">
      <?= icon('X', 'w-5 h-5') ?>
    </a>

    <!-- Modal Header -->
    <div class="flex items-center gap-3 mb-4">
      <div class="p-3 rounded-xl bg-brand-50 border border-brand-200 text-brand-700">
        <?= icon('FileSpreadsheet', 'w-6 h-6') ?>
      </div>
      <div>
        <h2 class="text-lg font-semibold text-slate-900">
          Import Recipients from Excel
        </h2>
        <p class="text-xs text-slate-500">
          Every column in your sheet automatically becomes a template variable
        </p>
      </div>
    </div>

    <!-- Requirements Banner -->
    <div class="mb-5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
      <div class="flex items-center justify-between">
        <span class="font-semibold text-slate-900 flex items-center gap-1.5">
          <?= icon('Info', 'w-4 h-4 text-brand-700') ?>
          Expected columns in your sheet:
        </span>
        <a href="./?action=recipients.downloadSample" class="text-[11px] text-brand-700 hover:text-brand-800 font-semibold flex items-center gap-1 underline underline-offset-2">
          <?= icon('Download', 'w-3 h-3') ?>
          Download Sample Sheet (.xlsx)
        </a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-slate-600 pt-1">
        <div class="p-2 rounded bg-surface border border-slate-200">
          <span class="font-bold text-brand-700 block">⭐️ Email (Required):</span>
          <span class="text-slate-500">Column: Email, Mail or Receiver</span>
        </div>
        <div class="p-2 rounded bg-surface border border-slate-200">
          <span class="font-bold text-brand-700 block">⭐️ Name:</span>
          <span class="text-slate-500">Column: Name or Full Name (tag: {{name}})</span>
        </div>
      </div>
      <p class="text-[11px] text-amber-800 pt-0.5">
        💡 <strong>Any extra column works:</strong> columns like <code>City</code>, <code>Gift Item</code>, <code>Order ID</code> or <code>Phone</code> automatically become <code>{{city}}</code>, <code>{{gift_item}}</code> and <code>{{order_id}}</code> as soon as you upload!
      </p>
    </div>

    <?php if (!$preview): ?>
      <!-- Upload Dropzone -->
      <form method="post" action="./" enctype="multipart/form-data" data-recipients-upload>
        <?= form_action('recipients.uploadExcel') ?>
        <label
          for="excel-file-input"
          data-dropzone
          class="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all flex flex-col items-center justify-center gap-3 border-slate-300 hover:border-brand-400 bg-slate-50 hover:bg-brand-50/40"
        >
          <input id="excel-file-input" type="file" name="file" accept=".xlsx, .xls, .csv" class="hidden">

          <div class="p-4 rounded-full bg-brand-50 border border-brand-200 text-brand-700">
            <?= icon('Upload', 'w-8 h-8') ?>
          </div>

          <div>
            <p class="text-sm font-semibold text-slate-900">
              Drag and drop your Excel file here, or click to browse
            </p>
            <p class="text-xs text-slate-500 mt-1">
              Supported formats: <strong>.xlsx, .xls, .csv</strong> (variables are created as soon as the file is uploaded)
            </p>
          </div>

          <noscript>
            <button type="submit" class="mt-2 px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition shadow-button">
              Upload File
            </button>
          </noscript>
        </label>
      </form>

      <!-- Error message -->
      <?php if ($uploadError): ?>
        <div class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
          <?= icon('AlertTriangle', 'w-4 h-4 shrink-0') ?>
          <span><?= e($uploadError) ?></span>
        </div>
      <?php endif; ?>

      <!-- Modal Footer -->
      <div class="flex items-center justify-between gap-3 pt-5 border-t border-slate-200 mt-5">
        <a href="<?= e($closeUrl) ?>" data-modal-close class="px-4 py-2 text-xs text-slate-500 hover:text-slate-900 transition">
          Cancel
        </a>
        <a href="./?action=recipients.downloadSample" class="px-4 py-2 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 text-xs font-semibold rounded-lg shadow-xs transition flex items-center gap-1.5">
          <?= icon('Download', 'w-3.5 h-3.5 text-brand-700') ?>
          <span>Download Blank Template</span>
        </a>
      </div>

    <?php else: ?>
      <!-- Parsed Result & Auto-Generated Variables Banner -->
      <form method="post" action="./">
        <?= form_action('recipients.confirmImport') ?>
        <div class="space-y-4">

          <!-- File info card -->
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <?= icon('FileCheck', 'w-5 h-5 text-brand-700') ?>
              <div>
                <p class="text-xs font-semibold text-slate-900 font-mono"><?= e($fileName) ?></p>
                <p class="text-[11px] text-slate-500">
                  Total rows: <?= (int) $preview['totalRows'] ?> | Valid emails: <strong class="text-brand-700"><?= (int) $preview['validCount'] ?></strong>
                  <?php if ($preview['duplicatesCount'] > 0): ?> | Duplicates: <?= (int) $preview['duplicatesCount'] ?><?php endif; ?>
                </p>
              </div>
            </div>

            <?= post_button('recipients.resetExcelPreview', [], 'Change File', 'text-xs text-slate-500 hover:text-slate-900 underline self-start sm:self-auto', ['form_class' => 'self-start sm:self-auto']) ?>
          </div>

          <!-- INSTANT AUTO-GENERATED VARIABLES BANNER -->
          <div class="p-3.5 rounded-xl bg-brand-50 border border-brand-200 space-y-2 shadow-sm">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <?= icon('Sparkles', 'w-4 h-4 text-brand-700') ?>
                <span class="text-xs font-semibold text-slate-900">
                  ⚡ <?= count($preview['discoveredVariables']) ?> variables created from your sheet:
                </span>
              </div>
              <span class="text-[10px] text-brand-700 font-mono bg-brand-100 px-2 py-0.5 rounded-full border border-brand-200">
                Ready to use instantly
              </span>
            </div>
            <p class="text-[11px] text-slate-600">
              No extra effort needed! These variables were created from the columns in your sheet. Wherever a tag appears in the template, each recipient's own data will be filled in:
            </p>

            <!-- Badges of all generated variables -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <?php foreach ($preview['discoveredVariables'] as $v): ?>
                <button
                  type="button"
                  data-copy="<?= e('{{' . $v . '}}') ?>"
                  data-copied-label="Copied!"
                  class="px-2.5 py-1 rounded-lg bg-surface hover:bg-brand-100 border border-brand-300 text-brand-700 font-mono text-xs font-semibold shadow-sm transition flex items-center gap-1 group"
                  title="Click to copy tag"
                >
                  <span data-copy-label><?= e('{{' . $v . '}}') ?></span>
                  <?= icon('Copy', 'w-3 h-3 text-slate-400 group-hover:text-brand-700') ?>
                </button>
              <?php endforeach; ?>
            </div>

            <!-- Auto-Insert into template checkbox -->
            <div class="pt-2 border-t border-brand-200 flex items-center justify-between text-xs">
              <label class="flex items-center gap-2 text-slate-700 cursor-pointer font-medium select-none">
                <input type="checkbox" name="autoInsertBlock" value="1" checked class="rounded focus:ring-0 accent-brand-600">
                <span>
                  Also add a block with these variables to the email template
                </span>
              </label>
            </div>
          </div>

          <!-- Preview Table -->
          <div class="border border-slate-200 rounded-xl overflow-hidden max-h-44 overflow-y-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead class="bg-slate-50 text-slate-500 sticky top-0 text-[10px] uppercase font-semibold">
                <tr>
                  <th class="py-2 px-3">#</th>
                  <th class="py-2 px-3">Name</th>
                  <th class="py-2 px-3">Email</th>
                  <th class="py-2 px-3">Auto-Generated Variables Data</th>
                  <th class="py-2 px-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-surface font-mono text-[11px]">
                <?php foreach ($preview['preview'] as $idx => $row): ?>
                  <tr class="<?= $row['isValid'] ? 'hover:bg-slate-50' : 'bg-red-50 text-red-700' ?>">
                    <td class="py-1.5 px-3 text-slate-500"><?= $idx + 1 ?></td>
                    <td class="py-1.5 px-3 font-sans text-slate-700"><?= e($row['name']) ?></td>
                    <td class="py-1.5 px-3 text-brand-700"><?= e($row['email']) ?></td>
                    <td class="py-1.5 px-3 text-slate-600">
                      <?php $fields = array_slice($row['customFields'], 0, 3, true); ?>
                      <?php if ($fields): ?>
                        <div class="flex flex-wrap gap-1">
                          <?php foreach ($fields as $k => $v): ?>
                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] text-slate-600">
                              <?= e($k) ?>: <strong class="text-amber-700"><?= e($v) ?></strong>
                            </span>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <span class="text-slate-400">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-1.5 px-3">
                      <?php if ($row['isValid']): ?>
                        <span class="text-[10px] text-brand-700 flex items-center gap-1">
                          <?= icon('Check', 'w-3 h-3') ?> Valid
                        </span>
                      <?php else: ?>
                        <span class="text-[10px] text-red-600 flex items-center gap-1">
                          <?= icon('AlertTriangle', 'w-3 h-3') ?> Invalid Email
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Import Mode Options (Append vs Replace) -->
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <span class="text-slate-700 font-medium">Import mode:</span>
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-1.5 cursor-pointer text-slate-700">
                <input type="radio" name="importMode" value="append" checked class="focus:ring-0 accent-brand-600">
                <span>Add to existing list (+<?= (int) $preview['validCount'] ?> to <?= (int) $currentCount ?>)</span>
              </label>
              <label class="flex items-center gap-1.5 cursor-pointer text-slate-700">
                <input type="radio" name="importMode" value="replace" class="focus:ring-0 accent-brand-600">
                <span>Replace entire list</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between gap-3 pt-5 border-t border-slate-200 mt-5">
          <a href="<?= e($closeUrl) ?>" data-modal-close class="px-4 py-2 text-xs text-slate-500 hover:text-slate-900 transition">
            Cancel
          </a>
          <button
            type="submit"
            <?= disabled($preview['validCount'] === 0) ?>
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs rounded-lg transition flex items-center gap-2 shadow-button"
          >
            <?= icon('CheckCircle2', 'w-4 h-4') ?>
            <span>
              Import <?= (int) $preview['validCount'] ?> Recipients
            </span>
          </button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>
