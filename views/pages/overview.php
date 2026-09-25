<?php
/**
 * Overview page (was OverviewPanel.tsx). Vars from the dashboard layout: $campaign, $smtp, $stepDone.
 */
$steps = step_items();
$totalSteps = total_steps();

$total = count($campaign['recipients']);
$delivered = count(array_filter($campaign['recipients'], fn ($r) => $r['status'] === 'delivered'));
$failed = count(array_filter($campaign['recipients'], fn ($r) => $r['status'] === 'failed'));
$pending = count(array_filter($campaign['recipients'], fn ($r) => $r['status'] === 'pending'));
$progressPercent = $total > 0 ? (int) round($delivered / $total * 100) : 0;

$doneCount = count(array_filter($steps, fn ($s) => !empty($stepDone[$s['id']])));
$nextStep = null;
foreach ($steps as $s) {
    if (empty($stepDone[$s['id']])) {
        $nextStep = $s;
        break;
    }
}

$statusMeta = [
    'idle' => ['label' => 'Draft / Ready', 'className' => 'bg-slate-100 ring-slate-300 text-slate-600'],
    'running' => ['label' => 'Sending', 'className' => 'bg-amber-50 ring-amber-200 text-amber-800'],
    'paused' => ['label' => 'Paused', 'className' => 'bg-amber-50 ring-amber-200 text-amber-800'],
    'completed' => ['label' => 'Completed', 'className' => 'bg-brand-50 ring-brand-200 text-brand-700'],
];
$status = $statusMeta[$campaign['status']] ?? $statusMeta['idle'];
$smtpStatus = smtp_status($smtp);

/** was formatDuration() in OverviewPanel.tsx */
$formatDuration = function (float $minutes): string {
    if ($minutes <= 0) {
        return '—';
    }
    if ($minutes < 60) {
        return round($minutes) . ' min';
    }
    $h = (int) floor($minutes / 60);
    $m = (int) round(fmod($minutes, 60));
    return $m ? "{$h} h {$m} min" : "{$h} h";
};

$stepDetail = [
    'settings' => $smtpStatus === 'live'
        ? 'Sending from ' . ($smtp['fromEmail'] ?: $smtp['username']) . ' via ' . $smtp['host'] . ':' . $smtp['port']
        : ($smtpStatus === 'incomplete'
            ? 'Add your email and App Password so real emails can be delivered.'
            : 'Sandbox mode is on: emails are only simulated.'),
    'recipients' => $total > 0 ? "{$total} recipients loaded, {$pending} waiting to be sent." : 'Upload an Excel / CSV sheet or add people one by one.',
    'editor' => trim($campaign['subject']) !== '' ? "Subject: “{$campaign['subject']}”" : 'Pick a template, generate one with AI, or paste your own HTML.',
    'dispatch' => $campaign['status'] === 'running'
        ? "Sending now: one email every {$campaign['intervalMinutes']} min."
        : ($campaign['status'] === 'completed' ? 'Campaign completed.' : "Start sending: one email every {$campaign['intervalMinutes']} min."),
    'logs' => count($campaign['logs']) > 0 ? count($campaign['logs']) . ' dispatches recorded.' : 'Every sent email will appear here.',
];

$stats = [
    ['label' => 'Total recipients', 'value' => $total, 'className' => 'text-slate-900'],
    ['label' => 'Delivered', 'value' => $delivered, 'className' => 'text-brand-800'],
    ['label' => 'Pending', 'value' => $pending, 'className' => 'text-slate-900'],
    ['label' => 'Failed', 'value' => $failed, 'className' => $failed > 0 ? 'text-red-600' : 'text-slate-900'],
];
?>
<div class="space-y-6">
  <!-- Hero: overall setup progress + next step -->
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0A1B2E] via-[#112A46] to-[#1B3B5A] ring-1 ring-inset ring-[#34506D]/60 text-white shadow-card">
    <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(rgb(255_255_255/0.12)_1px,transparent_1px)] [background-size:18px_18px] [mask-image:linear-gradient(to_left,black,transparent_70%)]"></div>
    <div class="relative p-6 md:p-8 flex flex-col xl:flex-row xl:items-end justify-between gap-6">
      <div class="max-w-2xl min-w-0">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-inset ring-white/15 text-[11px] font-semibold uppercase tracking-[0.08em] text-[#E1E8F0]">
          <?= icon('LayoutDashboard', 'w-3.5 h-3.5') ?>
          <?= e(festival_title($campaign['festival'])) ?>
        </span>
        <h2 class="mt-4 text-2xl md:text-3xl font-semibold tracking-tight"><?= e($campaign['companyName'] ?: 'Your campaign') ?></h2>
        <p class="mt-2 text-sm md:text-base text-[#C4D2E1] leading-relaxed">
          <?php if ($nextStep): ?>
            <?= $doneCount ?> of <?= $totalSteps ?> steps done. Next up: step <?= (int) $nextStep['step'] ?>, <?= e($nextStep['label']) ?>.
          <?php else: ?>
            All steps are done. Your campaign is set up and on its way.
          <?php endif; ?>
        </p>
        <div class="mt-4 flex items-center gap-3 max-w-md">
          <div class="flex-1 h-1.5 rounded-full bg-white/10 overflow-hidden">
            <div class="h-full rounded-full bg-[#A1B2C4] transition-all duration-500" style="width: <?= (int) round($doneCount / $totalSteps * 100) ?>%"></div>
          </div>
          <span class="text-xs font-semibold tabular-nums text-[#E1E8F0]">
            <?= $doneCount ?>/<?= $totalSteps ?>
          </span>
        </div>
      </div>
      <a
        href="<?= e(url($nextStep ? $nextStep['id'] : 'logs')) ?>"
        class="shrink-0 self-start xl:self-auto inline-flex items-center gap-2 h-11 px-5 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-[#FFFFFF] rounded-lg shadow-button transition"
      >
        <?= $nextStep ? 'Continue: ' . e($nextStep['label']) : 'View Delivery Logs' ?>
        <?= icon('ArrowRight', 'w-4 h-4') ?>
      </a>
    </div>
  </div>

  <!-- Campaign stats -->
  <div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card p-5">
    <div class="flex items-center justify-between gap-3 mb-4">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Campaign progress</p>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold ring-1 ring-inset rounded-full <?= $status['className'] ?>">
        <?php if ($campaign['status'] === 'running'): ?><span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span><?php endif; ?>
        <?= e($status['label']) ?>
      </span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <?php foreach ($stats as $s): ?>
        <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5">
          <p class="text-[11px] font-medium text-slate-500"><?= e($s['label']) ?></p>
          <p class="text-2xl font-bold tabular-nums <?= $s['className'] ?>"><?= (int) $s['value'] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 space-y-1.5">
      <div class="flex items-center justify-between text-xs text-slate-500">
        <span>Sent <strong class="text-slate-900"><?= $progressPercent ?>%</strong></span>
        <span class="flex items-center gap-1">
          <?= icon('Clock', 'w-3.5 h-3.5') ?>
          Every <?= e((string) $campaign['intervalMinutes']) ?> min · ~<?= e($formatDuration($pending * $campaign['intervalMinutes'])) ?> remaining
        </span>
      </div>
      <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
        <div class="h-full bg-brand-600 rounded-full transition-all duration-500" style="width: <?= $progressPercent ?>%"></div>
      </div>
    </div>
  </div>

  <!-- The five steps -->
  <div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Campaign steps</h3>
        <p class="text-sm text-slate-500">Follow these in order. Each one opens the right page.</p>
      </div>
      <span class="text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-slate-100 ring-slate-300 text-slate-600 tabular-nums">
        <?= $doneCount ?> of <?= $totalSteps ?> done
      </span>
    </div>
    <ol class="divide-y divide-slate-100">
      <?php foreach ($steps as $step): $done = !empty($stepDone[$step['id']]); $isNext = $nextStep && $nextStep['id'] === $step['id']; ?>
        <li>
          <a href="<?= e(url($step['id'])) ?>" class="w-full text-left px-5 py-4 flex items-center gap-4 transition hover:bg-slate-50 <?= $isNext ? 'bg-brand-50/60' : '' ?>">
            <span class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-sm font-semibold <?= $done ? 'bg-brand-600 text-white' : ($isNext ? 'ring-2 ring-inset ring-brand-400 text-brand-800' : 'ring-1 ring-inset ring-slate-300 text-slate-500') ?>">
              <?= $done ? icon('CheckCircle2', 'w-4 h-4') : (int) $step['step'] ?>
            </span>
            <span class="w-9 h-9 shrink-0 rounded-lg bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center">
              <?= icon($step['icon'], 'w-4 h-4') ?>
            </span>
            <span class="min-w-0 flex-1">
              <span class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-900"><?= e($step['label']) ?></span>
                <?php if ($isNext): ?>
                  <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-brand-600 text-white">Next</span>
                <?php endif; ?>
              </span>
              <span class="block text-xs text-slate-500 truncate"><?= e($stepDetail[$step['id']]) ?></span>
            </span>
            <span class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-brand-700">
              <?= $done ? 'Review' : 'Open' ?>
              <?= icon('ArrowRight', 'w-3.5 h-3.5') ?>
            </span>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</div>
