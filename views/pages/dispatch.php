<?php
/**
 * Step 4: Dispatcher (was src/components/DispatchController.tsx).
 * Vars from the dashboard layout: $page, $campaign, $smtp.
 * The live countdown text is painted by assets/js/app.js from data-countdown/data-seconds; the actual
 * staggered sends happen via the data-dispatch-runner hook the layout adds while status === 'running'
 * (POSTing api/dispatch-tick.php, which calls dispatch_tick() in includes/campaign.php).
 */
$recipients = $campaign['recipients'];
$total = count($recipients);
$delivered = count(array_filter($recipients, fn ($r) => $r['status'] === 'delivered'));
$failed = count(array_filter($recipients, fn ($r) => $r['status'] === 'failed'));
$pending = count(array_filter($recipients, fn ($r) => $r['status'] === 'pending'));
$progressPercent = $total > 0 ? (int) round(($delivered / $total) * 100) : 0;
$nextRecipient = first_pending($campaign);
$estMinutesRemaining = max(0, $pending * $campaign['intervalMinutes']);
$smtpStatusValue = smtp_status($smtp);
$isRunning = $campaign['status'] === 'running';

$intervalPresets = [
    ['label' => '5 Min (Recommended)', 'value' => 5, 'highlight' => true],
    ['label' => '2 Min', 'value' => 2, 'highlight' => false],
    ['label' => '1 Min', 'value' => 1, 'highlight' => false],
    ['label' => '30 Sec (Test)', 'value' => 0.5, 'highlight' => false],
];
?>
<div class="bg-surface border border-slate-200/80 rounded-2xl p-5 md:p-6 shadow-card">

  <!-- Header & Core Status -->
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-5 mb-5 border-b border-slate-100">
    <div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
          <?= icon('Clock', 'w-5 h-5') ?>
        </div>
        <h2 class="text-base font-semibold tracking-tight text-slate-900">
          Staggered Email Dispatcher
        </h2>
        <span class="hidden md:inline-flex text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-full whitespace-nowrap">
          Anti-Spam Throttling
        </span>
      </div>
      <p class="text-sm text-slate-500 mt-1 lg:pl-[52px]">
        Sends personalized festival emails to recipients every <?= e($campaign['intervalMinutes']) ?> minutes
      </p>
    </div>

    <!-- Interval Selection presets -->
    <div class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-lg">
      <span class="text-[11px] font-semibold text-slate-500 px-2">Every:</span>
      <?php foreach ($intervalPresets as $item): ?>
        <?php
        $isActive = $campaign['intervalMinutes'] == $item['value'];
        $btnClass = $isActive
            ? ($item['highlight'] ? 'bg-brand-600 text-white font-semibold shadow-sm' : 'bg-slate-300 text-slate-900 shadow-sm')
            : 'text-slate-500 hover:text-slate-900 disabled:opacity-50';
        ?>
        <?= post_button(
            'dispatch.interval',
            ['minutes' => $item['value']],
            e($item['label']),
            'px-2.5 py-1 text-xs rounded-md font-medium transition ' . $btnClass,
            ['disabled' => $isRunning]
        ) ?>
      <?php endforeach; ?>
      <form method="post" action="./" class="flex items-center gap-1 pl-1.5 ml-0.5 border-l border-slate-200" data-autosubmit>
        <?= form_action('dispatch.interval') ?>
        <input
          type="number"
          name="minutes"
          step="0.5"
          min="0.5"
          value="<?= e($campaign['intervalMinutes']) ?>"
          <?= disabled($isRunning) ?>
          class="w-16 px-1.5 py-1 text-xs bg-surface border border-slate-300 rounded-md text-slate-900 focus:outline-none focus:border-brand-500 disabled:opacity-50"
          title="Custom interval in minutes"
        >
        <button type="submit" <?= disabled($isRunning) ?> class="px-2 py-1 text-xs rounded-md font-medium text-slate-500 hover:text-slate-900 disabled:opacity-50" title="Use this custom interval (minutes)">
          Set
        </button>
      </form>
    </div>
  </div>

  <!-- Primary Action Card: Countdown & Big Controls -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <!-- Countdown Box -->
    <div class="bg-gradient-to-b from-brand-50 to-surface border border-brand-100 rounded-xl p-5 flex flex-col items-center justify-center text-center">
      <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1 flex items-center gap-1.5">
        <?= icon('Clock', 'w-3.5 h-3.5 text-brand-700') ?> Next Email Dispatch In
      </div>

      <div class="text-4xl md:text-5xl font-bold font-mono tracking-tight text-slate-900 tabular-nums my-2">
        <?php if ($campaign['status'] === 'running' || $campaign['status'] === 'paused'): ?>
          <span data-countdown data-seconds="<?= (int) $campaign['remainingSeconds'] ?>" data-total="<?= (int) $campaign['intervalSeconds'] ?>"><?= e(format_time_remaining($campaign['remainingSeconds'])) ?></span>
        <?php else: ?>
          <?= e(format_time_remaining((int) round($campaign['intervalMinutes'] * 60))) ?>
        <?php endif; ?>
      </div>

      <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
        <?php if ($campaign['status'] === 'running'): ?>
          <span class="text-brand-700 font-medium flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse inline-block"></span>
            Active countdown ticking...
          </span>
        <?php elseif ($campaign['status'] === 'paused'): ?>
          <span class="text-amber-700 font-medium">Paused at current timer</span>
        <?php else: ?>
          <span class="text-slate-500">Waiting for start trigger</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Next Recipient in Queue -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between">
      <div>
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1 flex items-center justify-between">
          <span class="flex items-center gap-1.5">
            <?= icon('Users', 'w-3.5 h-3.5 text-brand-700') ?> Next In Queue
          </span>
          <span class="text-[11px] text-brand-700 bg-brand-50 border border-brand-100 px-1.5 py-0.5 rounded font-semibold font-mono normal-case tracking-normal">
            #<?= (int) ($total - $pending + 1) ?> of <?= (int) $total ?>
          </span>
        </div>

        <?php if ($nextRecipient): ?>
          <div class="mt-2 space-y-1">
            <div class="text-sm font-semibold text-slate-900 truncate">
              <?= e($nextRecipient['name']) ?>
            </div>
            <div class="text-xs text-slate-500 font-mono truncate">
              <?= e($nextRecipient['email']) ?>
            </div>
            <div class="text-xs text-slate-600 truncate pt-1.5">
              Preview Subject: "<?= e(replace_placeholders($campaign['subject'], $nextRecipient, $campaign)) ?>"
            </div>
          </div>
        <?php else: ?>
          <div class="text-sm text-brand-700 font-medium py-4 flex items-center gap-2">
            <?= icon('CheckCircle2', 'w-4 h-4') ?> All queued emails have been dispatched!
          </div>
        <?php endif; ?>
      </div>

      <?php if ($nextRecipient): ?>
        <?= post_button(
            'dispatch.sendNext',
            [],
            icon('Zap', 'w-3.5 h-3.5 text-amber-500') . '<span>Send To This User Immediately (Skip Timer)</span>',
            'mt-3 w-full py-2 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 shadow-xs rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition',
            ['title' => 'Skip remaining minutes and dispatch to this recipient immediately', 'disabled' => $total === 0, 'form_class' => 'w-full mt-3']
        ) ?>
      <?php endif; ?>
    </div>

    <!-- Master Execution Controls -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between gap-3">
      <div>
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2 flex items-center justify-between">
          <span>Campaign Controls</span>
          <span class="text-[10px] font-medium normal-case tracking-normal text-slate-500">
            <?= e(['live' => '🟢 Live', 'incomplete' => '🟠 Setup needed', 'sandbox' => '🟠 Sandbox'][$smtpStatusValue]) ?>
          </span>
        </div>

        <!-- Outgoing Mail Sender Display -->
        <div class="mb-3 px-2.5 py-2 rounded-lg bg-surface border border-slate-200 text-[11px] flex items-center justify-between">
          <span class="text-slate-500">Sender:</span>
          <span class="font-mono text-slate-900 font-semibold truncate max-w-[160px]" title="<?= e($smtp['fromEmail'] ?: ($smtp['username'] ?: 'Sandbox')) ?>">
            <?= e($smtpStatusValue === 'live' ? ($smtp['fromEmail'] ?: $smtp['username']) : ($smtpStatusValue === 'incomplete' ? 'SMTP not configured' : 'Sandbox (Simulated)')) ?>
          </span>
        </div>

        <div class="space-y-2">
          <?php if (!$isRunning): ?>
            <?= post_button(
                'dispatch.start',
                [],
                icon('Play', 'w-4 h-4 fill-white') . '<span>' . ($campaign['status'] === 'paused' ? 'Resume 5m Campaign' : 'Start 5m Campaign') . '</span>',
                'w-full py-2.5 px-4 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm rounded-lg flex items-center justify-center gap-2 transition shadow-button',
                ['disabled' => $total === 0 || $pending === 0, 'form_class' => 'w-full']
            ) ?>
          <?php else: ?>
            <?= post_button(
                'dispatch.pause',
                [],
                icon('Pause', 'w-4 h-4') . '<span>Pause Campaign</span>',
                'w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm rounded-lg flex items-center justify-center gap-2 transition shadow-button',
                ['form_class' => 'w-full']
            ) ?>
          <?php endif; ?>

          <?= post_button(
              'dispatch.reset',
              [],
              icon('RotateCcw', 'w-3.5 h-3.5 text-slate-400') . '<span>Reset All Statuses to Pending</span>',
              'w-full py-2 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 shadow-xs rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition',
              ['confirm' => 'Reset all recipients back to Pending status so they can be re-sent?', 'form_class' => 'w-full']
          ) ?>
        </div>
      </div>

      <div class="text-[11px] text-slate-500 flex items-center justify-between border-t border-slate-200 pt-2.5">
        <span>Estimated Total Time:</span>
        <span class="text-slate-900 font-mono font-semibold">
          ~<?= e($estMinutesRemaining) ?> mins remaining
        </span>
      </div>
    </div>

  </div>

  <!-- Progress Bar & Metric Pills -->
  <div class="space-y-2">
    <div class="flex items-center justify-between text-xs">
      <span class="text-slate-600 font-medium">
        Overall Progress: <strong class="text-slate-900"><?= (int) $progressPercent ?>%</strong> (<?= (int) $delivered ?> of <?= (int) $total ?> sent)
      </span>
      <div class="flex items-center gap-3 font-mono text-[11px]">
        <span class="text-brand-700 font-semibold">✓ <?= (int) $delivered ?> Delivered</span>
        <span class="text-slate-600">⏳ <?= (int) $pending ?> Pending</span>
        <?php if ($failed > 0): ?><span class="text-red-600">✕ <?= (int) $failed ?> Failed</span><?php endif; ?>
      </div>
    </div>

    <!-- Progress track -->
    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden flex">
      <div
        class="h-full bg-brand-500 rounded-full transition-all duration-500"
        style="width: <?= (int) $progressPercent ?>%"
      ></div>
    </div>
  </div>

</div>
