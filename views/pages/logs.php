<?php
/**
 * Step 5: Delivery Logs (was src/components/DeliveryLogs.tsx).
 * Vars from the dashboard layout: $page, $campaign. Filter/search are plain GET query params so the
 * list, its URL and the browser back button all stay in sync without any JavaScript.
 */
$logs = $campaign['logs'];
$deliveredCount = count(array_filter($logs, fn ($l) => $l['status'] !== 'failed'));
$failedCount = count(array_filter($logs, fn ($l) => $l['status'] === 'failed'));
$successRate = count($logs) > 0 ? (int) round(($deliveredCount / count($logs)) * 100) : 0;

$filterParam = (string) ($_GET['filter'] ?? 'all');
$filter = in_array($filterParam, ['all', 'delivered', 'failed'], true) ? $filterParam : 'all';
$search = trim((string) ($_GET['q'] ?? ''));
$q = mb_strtolower($search);

$filteredLogs = array_values(array_filter($logs, function ($log) use ($filter, $q) {
    $matchesFilter = $filter === 'all' || ($filter === 'failed' ? $log['status'] === 'failed' : $log['status'] !== 'failed');
    if (!$matchesFilter) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    return str_contains(mb_strtolower($log['recipientEmail']), $q)
        || str_contains(mb_strtolower($log['recipientName']), $q)
        || str_contains(mb_strtolower($log['subject']), $q);
}));

$stats = [
    ['label' => 'Total dispatched', 'value' => count($logs), 'icon' => 'Send', 'tone' => 'text-slate-900'],
    ['label' => 'Delivered', 'value' => $deliveredCount, 'icon' => 'CheckCircle2', 'tone' => 'text-brand-700'],
    ['label' => 'Failed', 'value' => $failedCount, 'icon' => 'AlertCircle', 'tone' => $failedCount > 0 ? 'text-red-600' : 'text-slate-900'],
    ['label' => 'Success rate', 'value' => count($logs) ? $successRate . '%' : '—', 'icon' => 'Percent', 'tone' => 'text-slate-900'],
];
$filters = [
    ['id' => 'all', 'label' => 'All', 'count' => count($logs)],
    ['id' => 'delivered', 'label' => 'Delivered', 'count' => $deliveredCount],
    ['id' => 'failed', 'label' => 'Failed', 'count' => $failedCount],
];
?>
<div class="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col">

  <!-- Header -->
  <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
        <?= icon('Activity', 'w-5 h-5') ?>
      </div>
      <div>
        <h2 class="text-base font-semibold tracking-tight text-slate-900">Delivery Logs</h2>
        <p class="text-sm text-slate-500">Every dispatched email with its status, message ID and timestamp</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <?php if (count($logs) === 0): ?>
        <span class="inline-flex items-center gap-1.5 h-9 px-3 bg-surface text-slate-700 border border-slate-300 text-xs font-medium rounded-lg shadow-xs opacity-50 cursor-not-allowed" title="Download the logs as a CSV report">
          <?= icon('Download', 'w-3.5 h-3.5 text-slate-500') ?>
          <span>Export CSV</span>
        </span>
      <?php else: ?>
        <a href="./?action=logs.downloadCsv" class="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 text-xs font-medium rounded-lg shadow-xs transition" title="Download the logs as a CSV report">
          <?= icon('Download', 'w-3.5 h-3.5 text-slate-500') ?>
          <span>Export CSV</span>
        </a>
      <?php endif; ?>

      <?php partial('action_menu', [
          'label' => 'More actions',
          'items' => [
              ['label' => 'Export as CSV', 'icon' => 'Download', 'href' => './?action=logs.downloadCsv', 'hidden' => count($logs) === 0],
              ['label' => 'Clear all logs', 'icon' => 'Trash2', 'action' => 'logs.clear', 'danger' => true, 'hidden' => count($logs) === 0, 'separated' => true],
          ],
      ]); ?>
    </div>
  </div>

  <!-- Summary -->
  <div class="px-5 sm:px-6 py-4 border-b border-slate-200/80 grid grid-cols-2 lg:grid-cols-4 gap-3">
    <?php foreach ($stats as $s): ?>
      <div class="flex items-center gap-3 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 px-4 py-3">
        <div class="w-9 h-9 shrink-0 rounded-lg bg-surface ring-1 ring-slate-200 flex items-center justify-center">
          <?= icon($s['icon'], 'w-4 h-4 text-slate-400') ?>
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-medium text-slate-500 truncate"><?= e($s['label']) ?></p>
          <p class="text-lg font-bold tabular-nums leading-tight <?= $s['tone'] ?>"><?= e($s['value']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Toolbar -->
  <form method="get" action="./" class="px-5 sm:px-6 py-3 bg-surface border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs" data-autosubmit>
    <input type="hidden" name="page" value="logs">
    <input type="hidden" name="filter" value="<?= e($filter) ?>">

    <div class="relative flex-1 min-w-[200px] max-w-sm">
      <?= icon('Search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
      <input
        type="text"
        name="q"
        value="<?= e($search) ?>"
        placeholder="Search by email, name or subject..."
        class="w-full h-9 pl-9 pr-3 bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-sm"
      >
    </div>

    <div class="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
      <?php foreach ($filters as $f): ?>
        <a
          href="<?= e(url('logs', array_filter(['filter' => $f['id'], 'q' => $search]))) ?>"
          class="inline-flex items-center gap-1.5 h-7 px-3 rounded-md text-xs font-medium transition <?= $filter === $f['id'] ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>"
        >
          <?= e($f['label']) ?>
          <span class="min-w-[18px] px-1 rounded text-[10px] font-semibold tabular-nums <?= $filter === $f['id'] ? 'bg-slate-100 text-slate-700' : 'bg-slate-200/70 text-slate-500' ?>">
            <?= (int) $f['count'] ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </form>

  <!-- Table -->
  <div class="overflow-x-auto max-h-[480px]">
    <table class="w-full text-left border-collapse text-xs">
      <thead>
        <tr class="bg-slate-50 text-slate-500 sticky top-0 z-10 text-[11px] uppercase tracking-wider font-semibold border-b border-slate-200/80">
          <th class="py-3 px-5 whitespace-nowrap">Time</th>
          <th class="py-3 px-5 whitespace-nowrap">Recipient</th>
          <th class="py-3 px-5 whitespace-nowrap">Subject</th>
          <th class="py-3 px-5 whitespace-nowrap">Status</th>
          <th class="py-3 px-5 whitespace-nowrap">Sender (From)</th>
          <th class="py-3 px-5 whitespace-nowrap">Message ID</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 bg-surface">
        <?php if (count($filteredLogs) === 0): ?>
          <tr>
            <td colspan="6" class="py-14 text-center">
              <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-50 ring-1 ring-slate-200 flex items-center justify-center mb-3">
                <?= icon('Activity', 'w-5 h-5 text-slate-400') ?>
              </div>
              <p class="text-sm font-semibold text-slate-900">
                <?= count($logs) === 0 ? 'No delivery logs yet' : 'No logs match your search' ?>
              </p>
              <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                <?= count($logs) === 0
                    ? 'Start a campaign or send a test email, and every dispatch will appear here in real time.'
                    : 'Try a different search term or filter.' ?>
              </p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($filteredLogs as $log): ?>
            <?php $isFailed = $log['status'] === 'failed'; ?>
            <tr class="hover:bg-slate-50 transition">
              <td class="py-3 px-5 whitespace-nowrap">
                <div class="font-mono text-[11px] text-slate-700 tabular-nums"><?= e(format_time($log['timestamp'], 'h:i:s A')) ?></div>
                <div class="text-[11px] text-slate-400"><?= e(format_time($log['timestamp'], 'n/j/Y')) ?></div>
              </td>
              <td class="py-3 px-5">
                <div class="font-semibold text-slate-900 whitespace-nowrap"><?= e($log['recipientName']) ?></div>
                <div class="text-slate-500 font-mono text-[11px]"><?= e($log['recipientEmail']) ?></div>
              </td>
              <td class="py-3 px-5 text-slate-700 max-w-[260px] truncate" title="<?= e($log['subject']) ?>">
                <?= e($log['subject']) ?>
              </td>
              <td class="py-3 px-5 whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset <?= $isFailed ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-brand-50 text-brand-700 ring-brand-200' ?>"
                  title="<?= e($log['detail']) ?>"
                >
                  <?= icon($isFailed ? 'AlertCircle' : 'CheckCircle2', 'w-3 h-3') ?>
                  <?= $isFailed ? 'Failed' : 'Delivered' ?>
                </span>
                <div class="mt-1 text-[10.5px] text-slate-400"><?= $log['mode'] === 'smtp' ? 'via SMTP' : 'Sandbox (simulated)' ?></div>
              </td>
              <td class="py-3 px-5 font-mono text-[11px] text-slate-600 max-w-[180px] truncate" title="<?= e($log['senderEmail'] ?: 'My Account') ?>">
                <?= e($log['senderEmail'] ?: 'My Account') ?>
              </td>
              <td class="py-3 px-5 font-mono text-[10.5px] text-slate-400 max-w-[200px] truncate" title="<?= e($log['messageId']) ?>">
                <?= e($log['messageId'] ?: '—') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
