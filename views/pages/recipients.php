<?php
/**
 * Campaign Recipients page (was src/components/RecipientsManager.tsx). Var: $campaign.
 *
 * State -> mechanism mapping:
 *   showAddModal / showBulkModal / showExcelModal -> ?modal=add|bulk|excel
 *   editingRecipient                              -> ?edit=<recipient id> (row becomes an inline form, not a
 *                                                     centered modal - the lead's PHP_GUIDE asked for inline
 *                                                     edit specifically for this page; documented as a deviation)
 *   searchQuery / statusFilter                    -> ?q= / ?status=
 *   (new, not in the TSX) pagination              -> ?pg=  - the TSX rendered the whole filtered list inside a
 *                                                     scrolling <div>; a server-rendered table needs real paging
 *                                                     for large lists. Documented as an addition.
 *   (new, not in the TSX) bulk select + delete    -> checkboxes bound to a separate <form> via the HTML `form`
 *                                                     attribute (so they don't nest inside the per-row forms).
 *                                                     Documented as an addition.
 */
$pageScripts[] = 'js/recipients.js';

$modal = $modal ?? (string) ($_GET['modal'] ?? '');
$editId = (string) ($_GET['edit'] ?? '');
$searchQuery = (string) ($_GET['q'] ?? '');
$statusFilter = (string) ($_GET['status'] ?? 'all');
if (!in_array($statusFilter, ['all', 'pending', 'delivered', 'failed'], true)) {
    $statusFilter = 'all';
}
$pg = max(1, (int) ($_GET['pg'] ?? 1));
$perPage = 25;

// Params to reuse in links; excludes routing-only keys (page/modal/edit) so building a link from it is safe.
$baseParams = array_diff_key($_GET, ['page' => 1, 'modal' => 1, 'edit' => 1]);
$closeUrl = url('recipients', $baseParams); // closes any modal AND closes the inline edit row

$recipients = $campaign['recipients'];

/* ---------------------------------------------------------------------------
 * Small view helpers local to this page
 * ------------------------------------------------------------------------- */

function md_recipient_initials(string $name, string $email): string
{
    $src = trim($name !== '' ? $name : ($email !== '' ? $email : '?'));
    $parts = preg_split('/\s+/', $src) ?: [$src];
    $letters = array_slice(array_map(fn ($p) => mb_substr($p, 0, 1), $parts), 0, 2);
    return mb_strtoupper(implode('', $letters));
}

function md_recipient_status_badge(string $status): string
{
    return match ($status) {
        'delivered' => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-brand-50 text-brand-700 border border-brand-200">' . icon('CheckCircle2', 'w-3 h-3') . ' Delivered</span>',
        'sending' => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 animate-pulse">' . icon('RefreshCw', 'w-3 h-3 animate-spin') . ' Dispatching...</span>',
        'failed' => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">' . icon('AlertCircle', 'w-3 h-3') . ' Failed</span>',
        default => '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">' . icon('Clock', 'w-3 h-3 text-slate-400') . ' Pending (Queued)</span>',
    };
}

/** Custom-variable entries worth showing as chips (was the customEntries filter in the TSX row renderer). */
function md_custom_entries(?array $customData): array
{
    if (!$customData) {
        return [];
    }
    $out = [];
    foreach ($customData as $k => $v) {
        $lower = mb_strtolower($k);
        if ($k === $lower || empty($customData[$lower])) {
            $out[] = [$k, $v];
        }
    }
    return $out;
}

/** Deduped existing custom-variable pairs for the Edit form (was the `seen` Set in openEditModal). */
function md_recipient_edit_pairs(array $recipient): array
{
    $pairs = [];
    if (!empty($recipient['customData'])) {
        $seen = [];
        foreach ($recipient['customData'] as $k => $v) {
            $lower = mb_strtolower($k);
            if (!isset($seen[$lower])) {
                $seen[$lower] = true;
                $pairs[] = ['key' => $k, 'value' => $v];
            }
        }
    }
    return $pairs;
}

/** Builds the list of key/value pairs to render: re-fills from old() after a validation error, else $existingPairs, padded to $minRows blanks. */
function md_recipients_var_pairs(array $existingPairs, int $minRows): array
{
    $oldKeys = old('custom_key');
    $oldValues = old('custom_value');
    if (is_array($oldKeys) && $oldKeys !== []) {
        $pairs = [];
        foreach ($oldKeys as $i => $k) {
            $pairs[] = ['key' => (string) $k, 'value' => (string) ($oldValues[$i] ?? '')];
        }
    } else {
        $pairs = $existingPairs;
    }
    if (!$pairs) {
        $pairs[] = ['key' => '', 'value' => ''];
    }
    while (count($pairs) < $minRows) {
        $pairs[] = ['key' => '', 'value' => ''];
    }
    return $pairs;
}

/**
 * Renders the key/value input rows for a custom-variables editor. Rows beyond $visibleCount start hidden
 * (revealed one at a time by the "+ Add Variable" button in assets/js/recipients.js); a <noscript> style
 * next to the caller shows them all when JS is unavailable, so nothing is lost without JS.
 */
function md_render_var_rows(array $pairs, int $visibleCount, string $keyPlaceholder, string $valuePlaceholder, bool $hideFirstRemove): void
{
    foreach ($pairs as $i => $pair) {
        $extra = $i >= $visibleCount;
        $showRemove = !($hideFirstRemove && $i === 0);
        ?>
        <div class="flex items-center gap-2<?= $extra ? ' hidden' : '' ?>" data-var-row<?= $extra ? ' data-var-row-extra' : '' ?>>
          <input type="text" name="custom_key[]" placeholder="<?= e($keyPlaceholder) ?>" value="<?= e($pair['key']) ?>" class="w-1/2 px-2.5 py-1 text-xs bg-surface border border-slate-300 rounded text-amber-700 font-mono placeholder:text-slate-400 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
          <input type="text" name="custom_value[]" placeholder="<?= e($valuePlaceholder) ?>" value="<?= e($pair['value']) ?>" class="w-1/2 px-2.5 py-1 text-xs bg-surface border border-slate-300 rounded text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
          <?php if ($showRemove): ?>
          <button type="button" data-remove-var-row class="text-slate-400 hover:text-red-600 p-1">
            <?= icon('Trash2', 'w-3 h-3') ?>
          </button>
          <?php endif; ?>
        </div>
        <?php
    }
}

/* ---------------------------------------------------------------------------
 * Filtering + pagination (was the filteredRecipients computation in the TSX)
 * ------------------------------------------------------------------------- */

// PHP 8.4 has a native array_any(); this polyfill only kicks in on older PHP versions.
if (!function_exists('array_any')) {
    function array_any(array $arr, callable $fn): bool
    {
        foreach ($arr as $k => $v) {
            if ($fn($v, $k)) {
                return true;
            }
        }
        return false;
    }
}

$searchLower = mb_strtolower($searchQuery);
$filtered = array_values(array_filter($recipients, function ($r) use ($searchLower, $statusFilter) {
    $matchesSearch = $searchLower === ''
        || str_contains(mb_strtolower($r['name']), $searchLower)
        || str_contains(mb_strtolower($r['email']), $searchLower)
        || (!empty($r['customData']) && array_any($r['customData'] ?? [], fn ($val) => str_contains(mb_strtolower((string) $val), $searchLower)));
    $matchesStatus = $statusFilter === 'all' || $r['status'] === $statusFilter;
    return $matchesSearch && $matchesStatus;
}));

$totalFiltered = count($filtered);
$totalPages = max(1, (int) ceil($totalFiltered / $perPage));
$pg = min($pg, $totalPages);
$pageRecipients = array_slice($filtered, ($pg - 1) * $perPage, $perPage);
?>
<div class="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col">

  <!-- Top Bar -->
  <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
        <?= icon('Users', 'w-5 h-5') ?>
      </div>
      <div>
        <h2 class="text-base font-semibold tracking-tight text-slate-900">
          Campaign Recipients
        </h2>
        <p class="text-sm text-slate-500">
          <?= count($recipients) ?> recipients queued for staggered 5-minute dispatch
        </p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-2">
      <a href="<?= e(url('recipients', $baseParams + ['modal' => 'add'])) ?>" class="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 text-xs font-medium rounded-lg shadow-xs transition">
        <?= icon('UserPlus', 'w-3.5 h-3.5 text-slate-500') ?>
        <span>Add recipient</span>
      </a>

      <a href="<?= e(url('recipients', $baseParams + ['modal' => 'excel'])) ?>" class="inline-flex items-center gap-1.5 h-9 px-3.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition shadow-button" title="Upload Excel (.xlsx, .xls) or CSV — every column becomes a mail variable">
        <?= icon('FileSpreadsheet', 'w-4 h-4') ?>
        <span>Upload Excel</span>
      </a>

      <?php partial('action_menu', [
          'id' => 'recipients-actions-menu',
          'label' => 'More actions',
          'items' => [
              ['label' => 'Paste as text', 'icon' => 'ClipboardPaste', 'href' => url('recipients', $baseParams + ['modal' => 'bulk'])],
              ['label' => 'Download Excel format', 'icon' => 'Download', 'href' => './?action=recipients.downloadSample'],
              ['label' => 'Load sample data', 'icon' => 'Database', 'action' => 'recipients.loadSample', 'confirm' => count($recipients) > 0 ? 'Replace current recipients list with sample festival contacts?' : null],
              ['label' => 'Export as CSV', 'icon' => 'Download', 'href' => './?action=recipients.downloadCsv', 'hidden' => count($recipients) === 0, 'separated' => true],
              ['label' => 'Clear all recipients', 'icon' => 'Trash2', 'action' => 'recipients.clearAll', 'danger' => true, 'hidden' => count($recipients) === 0, 'separated' => true, 'confirm' => 'Are you sure you want to clear all recipients from this campaign?'],
          ],
      ]); ?>
    </div>
  </div>

  <!-- Filter and Search Bar -->
  <div class="px-5 sm:px-6 py-3 bg-surface border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
    <form method="get" action="./" class="relative flex-1 min-w-[200px] max-w-sm">
      <input type="hidden" name="page" value="recipients">
      <?php if ($statusFilter !== 'all'): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
      <?= icon('Search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
      <input
        type="text"
        name="q"
        value="<?= e($searchQuery) ?>"
        placeholder="Search by name, email, or variable..."
        class="w-full h-9 pl-9 pr-3 bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-sm"
      >
    </form>

    <div class="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
      <?php foreach (['all', 'pending', 'delivered', 'failed'] as $st): ?>
        <?php $count = $st === 'all' ? count($recipients) : count(array_filter($recipients, fn ($r) => $r['status'] === $st)); ?>
        <a
          href="<?= e(url('recipients', array_filter(['q' => $searchQuery !== '' ? $searchQuery : null, 'status' => $st !== 'all' ? $st : null]))) ?>"
          class="inline-flex items-center gap-1.5 h-7 px-3 rounded-md text-xs font-medium capitalize transition <?= $statusFilter === $st ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>"
        >
          <?= e($st) ?>
          <span class="min-w-[18px] px-1 rounded text-[10px] font-semibold tabular-nums <?= $statusFilter === $st ? 'bg-slate-100 text-slate-700' : 'bg-slate-200/70 text-slate-500' ?>">
            <?= $count ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($recipients): ?>
    <!-- Bulk select + delete (addition beyond the TSX; checkboxes below are bound to this form via the `form` attribute) -->
    <form id="recipients-bulk-form" method="post" action="./" data-confirm="Delete the selected recipients?"><?= form_action('recipients.bulkDelete') ?></form>
    <div class="px-5 sm:px-6 py-1.5 bg-surface border-b border-slate-200/80 flex items-center justify-end text-xs">
      <button type="submit" form="recipients-bulk-form" class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition font-medium">
        <?= icon('Trash2', 'w-3.5 h-3.5') ?>
        <span>Delete selected</span>
      </button>
    </div>
  <?php endif; ?>

  <!-- Table Container -->
  <div class="overflow-x-auto max-h-[480px]">
    <table class="w-full text-left border-collapse text-xs">
      <thead>
        <tr class="bg-slate-50 text-slate-500 sticky top-0 z-10 text-[11px] uppercase tracking-wider font-semibold border-b border-slate-200/80">
          <th class="py-3 pl-5 pr-2 whitespace-nowrap w-8"><input type="checkbox" data-select-all class="rounded accent-brand-600" title="Select all"></th>
          <th class="py-3 px-3 whitespace-nowrap">#</th>
          <th class="py-3 px-5 whitespace-nowrap">Name</th>
          <th class="py-3 px-5 whitespace-nowrap">Email</th>
          <th class="py-3 px-5 whitespace-nowrap">Custom Variables</th>
          <th class="py-3 px-5 whitespace-nowrap">Delivery Status</th>
          <th class="py-3 px-5 whitespace-nowrap">Dispatched Time</th>
          <th class="py-3 px-5 whitespace-nowrap text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 bg-surface">
        <?php if (!$pageRecipients): ?>
          <tr>
            <td colspan="8" class="py-14 text-center text-slate-500">
              <div class="flex flex-col items-center justify-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 ring-1 ring-slate-200 flex items-center justify-center"><?= icon('FileSpreadsheet', 'w-5 h-5 text-slate-400') ?></div>
                <div>
                  <p class="text-sm font-semibold text-slate-700">
                    <?= count($recipients) === 0 ? 'No recipients yet' : 'No recipients match this filter.' ?>
                  </p>
                  <p class="text-xs text-slate-500 mt-1">
                    Upload an Excel file and every column will become a variable instantly
                  </p>
                </div>
                <?php if (count($recipients) === 0): ?>
                  <div class="flex items-center gap-2 mt-1">
                    <a href="<?= e(url('recipients', $baseParams + ['modal' => 'excel'])) ?>" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition flex items-center gap-2 shadow-button">
                      <?= icon('FileSpreadsheet', 'w-4 h-4') ?>
                      <span>Upload Excel Spreadsheet</span>
                    </a>
                    <?= post_button('recipients.loadSample', [], 'Load Sample Data', 'px-3.5 py-2 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition') ?>
                  </div>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pageRecipients as $idx => $recipient): ?>
            <?php if ($editId !== '' && $recipient['id'] === $editId): ?>
              <tr>
                <td colspan="8" class="p-4 bg-slate-50/70">
                  <form method="post" action="./" class="space-y-3 max-w-xl">
                    <?= form_action('recipients.saveEdit') ?>
                    <input type="hidden" name="id" value="<?= e($recipient['id']) ?>">
                    <h3 class="text-sm font-semibold text-slate-900 mb-1 flex items-center gap-2">
                      <?= icon('Edit3', 'w-4 h-4 text-brand-700') ?>
                      Edit Recipient
                    </h3>
                    <div>
                      <label class="block text-xs text-slate-500 mb-1">Name</label>
                      <input type="text" name="name" value="<?= e(old('name', $recipient['name'])) ?>" class="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
                    </div>
                    <div>
                      <label class="block text-xs text-slate-500 mb-1">Email Address</label>
                      <input type="email" name="email" value="<?= e(old('email', $recipient['email'])) ?>" required class="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono">
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200" data-var-rows>
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                          <?= icon('Tag', 'w-3 h-3') ?>
                          Custom Variables for this Recipient
                        </span>
                        <button type="button" data-add-var-row class="text-[11px] text-brand-700 hover:text-brand-800 flex items-center gap-0.5">
                          <?= icon('Plus', 'w-3 h-3') ?> Add Variable
                        </button>
                      </div>
                      <noscript><style>[data-var-row-extra]{display:flex !important;}</style></noscript>
                      <div class="space-y-2 max-h-36 overflow-y-auto">
                        <?php
                        $existingPairs = md_recipient_edit_pairs($recipient);
                        $visible = max(1, count($existingPairs));
                        md_render_var_rows(md_recipients_var_pairs($existingPairs, $visible + 3), $visible, 'Tag (e.g. city, order_id)', 'Value (e.g. Mumbai)', false);
                        ?>
                      </div>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                      <a href="<?= e($closeUrl) ?>" class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900">Cancel</a>
                      <button type="submit" class="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-medium text-xs rounded-lg transition shadow-button">Save Changes</button>
                    </div>
                  </form>
                </td>
              </tr>
            <?php else: ?>
              <?php $customEntries = md_custom_entries($recipient['customData'] ?? null); ?>
              <tr class="hover:bg-slate-50 transition group">
                <td class="py-3 pl-5 pr-2">
                  <input type="checkbox" name="ids[]" value="<?= e($recipient['id']) ?>" form="recipients-bulk-form" class="rounded accent-brand-600">
                </td>
                <td class="py-3 px-3 text-slate-400 font-mono text-[11px] tabular-nums">
                  <?= ($pg - 1) * $perPage + $idx + 1 ?>
                </td>
                <td class="py-3 px-5">
                  <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 shrink-0 rounded-full bg-brand-50 ring-1 ring-inset ring-brand-100 text-brand-700 text-[11px] font-semibold flex items-center justify-center">
                      <?= e(md_recipient_initials($recipient['name'], $recipient['email'])) ?>
                    </span>
                    <span class="font-semibold text-slate-900 whitespace-nowrap"><?= e($recipient['name']) ?></span>
                  </div>
                </td>
                <td class="py-3 px-5 font-mono text-slate-600">
                  <?= e($recipient['email']) ?>
                </td>
                <td class="py-3 px-5">
                  <?php if ($customEntries): ?>
                    <div class="flex flex-wrap gap-1 max-w-xs">
                      <?php foreach (array_slice($customEntries, 0, 3) as [$k, $v]): ?>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-100 text-[10.5px] text-slate-600 font-mono" title="<?= e('{{' . $k . '}}: ' . $v) ?>">
                          <span class="text-slate-400"><?= e($k) ?>:</span>
                          <span class="text-slate-700 max-w-[80px] truncate"><?= e($v) ?></span>
                        </span>
                      <?php endforeach; ?>
                      <?php if (count($customEntries) > 3): ?>
                        <span class="text-[10px] text-slate-500 self-center">
                          +<?= count($customEntries) - 3 ?> more
                        </span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-slate-300">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-5">
                  <?= md_recipient_status_badge($recipient['status']) ?>
                </td>
                <td class="py-3 px-5 text-slate-500 text-[11px] font-mono tabular-nums">
                  <?= $recipient['sentAt'] ? e(format_time($recipient['sentAt'])) : '—' ?>
                </td>
                <td class="py-3 px-5 text-right">
                  <div class="flex items-center justify-end gap-0.5 opacity-60 group-hover:opacity-100 transition">
                    <a href="<?= e(url('recipients', $baseParams + ['edit' => $recipient['id']])) ?>" class="p-1.5 text-slate-500 hover:text-brand-800 hover:bg-brand-50 rounded-md transition" title="Edit recipient & custom variables">
                      <?= icon('Edit3', 'w-3.5 h-3.5') ?>
                    </a>
                    <?php if ($recipient['status'] === 'failed'): ?>
                      <?= post_button('recipients.retry', ['id' => $recipient['id']], icon('RotateCw', 'w-3.5 h-3.5'), 'p-1.5 text-amber-600 hover:bg-amber-50 rounded-md transition', ['title' => 'Retry sending']) ?>
                    <?php endif; ?>
                    <?= post_button('recipients.delete', ['id' => $recipient['id']], icon('Trash2', 'w-3.5 h-3.5'), 'p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-md transition', ['title' => 'Remove recipient']) ?>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <!-- Pagination (addition beyond the TSX, which rendered the whole filtered list in a scrolling container) -->
    <div class="px-5 sm:px-6 py-3 border-t border-slate-200/80 flex items-center justify-between gap-3 text-xs text-slate-500">
      <span>Page <?= $pg ?> of <?= $totalPages ?> (<?= $totalFiltered ?> recipients)</span>
      <div class="inline-flex items-center gap-1">
        <?php if ($pg > 1): ?>
          <a href="<?= e(url('recipients', $baseParams + ['pg' => $pg - 1])) ?>" class="h-7 px-3 inline-flex items-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition">Prev</a>
        <?php else: ?>
          <span class="h-7 px-3 inline-flex items-center rounded-md border border-slate-200 text-slate-300">Prev</span>
        <?php endif; ?>
        <?php if ($pg < $totalPages): ?>
          <a href="<?= e(url('recipients', $baseParams + ['pg' => $pg + 1])) ?>" class="h-7 px-3 inline-flex items-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition">Next</a>
        <?php else: ?>
          <span class="h-7 px-3 inline-flex items-center rounded-md border border-slate-200 text-slate-300">Next</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Single Add Modal (With Custom Variables Support) -->
  <?php if ($modal === 'add'): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in" data-modal-root>
      <div class="w-full max-w-md bg-surface border border-slate-200 rounded-2xl p-5 shadow-modal text-slate-700">
        <h3 class="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <?= icon('UserPlus', 'w-4 h-4 text-brand-700') ?>
          Add Recipient
        </h3>
        <form method="post" action="./" class="space-y-3">
          <?= form_action('recipients.addSingle') ?>
          <div>
            <label class="block text-xs text-slate-500 mb-1">
              Name <span class="text-[10px] text-slate-400">(tag: {{name}})</span>
            </label>
            <input type="text" name="name" value="<?= e(old('name')) ?>" placeholder="e.g. Aarav Sharma" class="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
          </div>

          <div>
            <label class="block text-xs text-slate-500 mb-1">
              Email Address <span class="text-red-600 font-bold">*</span>
            </label>
            <input type="email" name="email" value="<?= e(old('email')) ?>" placeholder="e.g. aarav@company.com" required class="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono">
          </div>

          <!-- Custom Variables Section -->
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200" data-var-rows>
            <div class="flex items-center justify-between mb-2">
              <span class="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                <?= icon('Tag', 'w-3 h-3') ?>
                Custom Variables
              </span>
              <button type="button" data-add-var-row class="text-[11px] text-brand-700 hover:text-brand-800 flex items-center gap-0.5">
                <?= icon('Plus', 'w-3 h-3') ?> Add Variable
              </button>
            </div>
            <p class="text-[10px] text-slate-500 mb-2">
              Example: Key <code>city</code>, Value <code>Mumbai</code> → use <code>{{city}}</code> in the email
            </p>
            <noscript><style>[data-var-row-extra]{display:flex !important;}</style></noscript>

            <div class="space-y-2 max-h-36 overflow-y-auto">
              <?php md_render_var_rows(md_recipients_var_pairs([], 6), 1, 'Tag (e.g. city, gift)', 'Value (e.g. Delhi)', true); ?>
            </div>
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <a href="<?= e($closeUrl) ?>" class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-medium text-xs rounded-lg transition shadow-button">Add Recipient</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- Bulk Paste Modal -->
  <?php if ($modal === 'bulk'): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in" data-modal-root>
      <div class="w-full max-w-lg bg-surface border border-slate-200 rounded-2xl p-5 shadow-modal text-slate-700">
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Bulk Import Recipients (Paste Text)</h3>
        <p class="text-xs text-slate-500 mb-3">
          Paste rows with &quot;Name, Email&quot; or list of emails (one per line):
        </p>
        <form method="post" action="./" class="space-y-3">
          <?= form_action('recipients.addBulk') ?>
          <textarea name="bulk_input" placeholder="Aarav Sharma, aarav@example.com&#10;Priya Patel, priya@acme.com&#10;rohit@company.org" rows="6" required class="w-full p-3 font-mono text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"><?= e(old('bulk_input')) ?></textarea>
          <div class="pt-2 flex justify-end gap-2">
            <a href="<?= e($closeUrl) ?>" class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-medium text-xs rounded-lg transition shadow-button">Import Contacts</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- Excel & CSV Upload Modal -->
  <?php if ($modal === 'excel'): ?>
    <?php partial('excel_upload_modal', ['campaign' => $campaign, 'closeUrl' => $closeUrl]); ?>
  <?php endif; ?>

</div>
