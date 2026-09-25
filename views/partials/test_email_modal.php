<?php
/**
 * "Send Single Test Email" modal (was src/components/TestEmailModal.tsx).
 * Rendered by views/layout/app.php when ?modal=test. Vars: $campaign, $smtp, $closeUrl.
 * Submits to smtp.sendTest (actions/smtp.php); the handler redirects back to the exact URL the form
 * was posted from (which already includes modal=test), so the modal stays open with its result.
 */
$result = take_flash_data('test_email_result');
$status = smtp_status($smtp);
?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in" data-modal-root>
  <div class="relative w-full max-w-md bg-surface border border-slate-200 rounded-2xl shadow-modal text-slate-700 p-6">

    <a href="<?= e($closeUrl) ?>" data-modal-close class="absolute top-4 right-4 p-2 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition">
      <?= icon('X', 'w-5 h-5') ?>
    </a>

    <div class="flex items-center gap-3 mb-5 border-b border-slate-200 pb-3">
      <div class="p-2.5 bg-brand-50 text-brand-700 rounded-xl">
        <?= icon('Mail', 'w-5 h-5') ?>
      </div>
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Send Single Test Email</h2>
        <p class="text-sm text-slate-500">Verify rendering in your personal inbox before bulk run</p>
      </div>
    </div>

    <form method="post" action="./" class="space-y-4">
      <?= form_action('smtp.sendTest') ?>

      <div>
        <label class="block text-xs text-slate-500 mb-1">Test Recipient Email</label>
        <input
          type="email"
          name="testEmail"
          value="<?= e(old('testEmail')) ?>"
          placeholder="e.g. yourname@gmail.com"
          required
          class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
        >
      </div>

      <div>
        <label class="block text-xs text-slate-500 mb-1">Sample Recipient Name</label>
        <input
          type="text"
          name="testName"
          value="<?= e(old('testName', 'Campaign Reviewer')) ?>"
          placeholder="e.g. Aarav Sharma"
          class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
        >
      </div>

      <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
        <span class="font-semibold text-slate-900 block mb-0.5">Mode:</span>
        <?php if ($status === 'live'): ?>
          <span class="text-brand-700 font-medium">
            ● Live SMTP (<?= e($smtp['host']) ?>) - a real email will be sent
          </span>
        <?php elseif ($status === 'incomplete'): ?>
          <span class="text-amber-700 font-medium">
            ● SMTP not configured - add your username and password in SMTP Settings
          </span>
        <?php else: ?>
          <span class="text-amber-700 font-medium">
            ● Sandbox / Simulation Active - Safe test delivery verification
          </span>
        <?php endif; ?>
      </div>

      <?php if ($result): ?>
        <div class="p-3 rounded-lg text-xs flex items-center gap-2 <?= $result['success'] ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
          <?= icon($result['success'] ? 'CheckCircle2' : 'AlertCircle', 'w-4 h-4 shrink-0 ' . ($result['success'] ? 'text-brand-700' : 'text-red-600')) ?>
          <span><?= e($result['message']) ?></span>
        </div>
      <?php endif; ?>

      <div class="pt-2 flex justify-end gap-2">
        <a href="<?= e($closeUrl) ?>" data-modal-close class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition">
          Close
        </a>
        <button
          type="submit"
          class="px-4 py-2 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs rounded-xl flex items-center gap-1.5 transition shadow-button"
        >
          <?= icon('Send', 'w-3.5 h-3.5') ?>
          <span>Send Test Email</span>
        </button>
      </div>
    </form>

  </div>
</div>
