<?php
$settings = contact_mail_settings();
$submissions = contact_submissions_list();
$unreadCount = contact_submissions_unread_count();
$inputClass = 'w-full h-10 px-3 bg-page border border-slate-200/80 rounded-lg text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10';
$labelClass = 'block mb-1.5 text-xs font-medium text-slate-600';
?>
<section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,.8fr)]">
  <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-surface shadow-card">
    <div class="flex items-center justify-between gap-4 border-b border-slate-200/80 px-5 py-4 sm:px-6">
      <div><h2 class="text-base font-semibold text-slate-900">Contact email delivery</h2><p class="mt-1 text-xs text-slate-500">SMTP credentials are encrypted before they are stored.</p></div>
      <span class="rounded-full px-3 py-1 text-xs font-semibold <?= contact_mail_is_configured() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>"><?= contact_mail_is_configured() ? 'Configured' : 'Setup needed' ?></span>
    </div>
    <form method="post" action="./" class="space-y-4 p-5 sm:p-6">
      <?= form_action('admin.saveContactSmtp', url('contact-inbox')) ?>
      <div class="grid gap-4 sm:grid-cols-2">
        <label><span class="<?= $labelClass ?>">SMTP host</span><input class="<?= $inputClass ?>" name="host" value="<?= e(old('host', $settings['host'])) ?>" placeholder="smtp.gmail.com" required></label>
        <label><span class="<?= $labelClass ?>">Port</span><select class="<?= $inputClass ?>" name="port"><option value="587" <?= (int) old('port', $settings['port']) === 587 ? 'selected' : '' ?>>587 · STARTTLS</option><option value="465" <?= (int) old('port', $settings['port']) === 465 ? 'selected' : '' ?>>465 · SSL</option></select></label>
        <label><span class="<?= $labelClass ?>">SMTP username</span><input class="<?= $inputClass ?>" type="text" name="username" value="<?= e(old('username', $settings['username'])) ?>" autocomplete="username" required></label>
        <label><span class="<?= $labelClass ?>">SMTP password / app password</span><input class="<?= $inputClass ?>" type="password" name="password" value="" autocomplete="new-password" placeholder="<?= $settings['password_set'] ? 'Saved — leave blank to keep it' : 'Enter SMTP password' ?>" <?= $settings['password_set'] ? '' : 'required' ?>></label>
        <label><span class="<?= $labelClass ?>">Sender name</span><input class="<?= $inputClass ?>" name="from_name" value="<?= e(old('from_name', $settings['from_name'])) ?>" maxlength="120" required></label>
        <label><span class="<?= $labelClass ?>">Sender email</span><input class="<?= $inputClass ?>" type="email" name="from_email" value="<?= e(old('from_email', $settings['from_email'])) ?>" placeholder="Defaults to SMTP username"></label>
        <label class="sm:col-span-2"><span class="<?= $labelClass ?>">Receive contact messages at</span><input class="<?= $inputClass ?>" type="email" name="recipient" value="<?= e(old('recipient', $settings['recipient'])) ?>" required></label>
      </div>
      <p class="text-xs leading-5 text-slate-400">Use port 587 for STARTTLS or port 465 for SSL. The sender address should be allowed by your SMTP provider.</p>
      <div class="flex flex-wrap gap-3">
        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white shadow-button transition hover:bg-brand-500">Save SMTP settings</button>
        <button type="submit" name="action" value="admin.testContactSmtp" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Save &amp; send test email</button>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200/80 bg-surface p-5 shadow-card sm:p-6">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Inbox</p>
    <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-900"><?= $unreadCount ?></p>
    <p class="text-sm text-slate-500">unread message<?= $unreadCount === 1 ? '' : 's' ?></p>
    <p class="mt-5 text-xs leading-5 text-slate-400">Form messages are saved here even if SMTP delivery is temporarily unavailable.</p>
  </div>
</section>

<section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-surface shadow-card">
  <div class="flex items-center justify-between border-b border-slate-200/80 px-5 py-4 sm:px-6"><div><h2 class="text-base font-semibold text-slate-900">Recent messages</h2><p class="mt-1 text-xs text-slate-500">Latest <?= count($submissions) ?> submissions</p></div></div>
  <?php if (!$submissions): ?>
    <div class="px-5 py-12 text-center text-sm text-slate-500">No contact messages yet.</div>
  <?php else: ?>
    <div class="divide-y divide-slate-200/70">
      <?php foreach ($submissions as $item): ?>
        <details class="group px-5 py-4 sm:px-6" <?= empty($item['read_at']) ? 'data-unread="true"' : '' ?>>
          <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-4 gap-y-2 [&::-webkit-details-marker]:hidden">
            <span class="h-2 w-2 shrink-0 rounded-full <?= empty($item['read_at']) ? 'bg-brand-500' : 'bg-transparent' ?>"></span>
            <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-slate-900"><?= e($item['name']) ?></span><span class="block truncate text-xs text-slate-500"><?= e($item['email']) ?> · <?= e($item['topic']) ?></span></span>
            <span class="text-xs text-slate-400"><?= e(date('d M Y, H:i', strtotime($item['created_at']))) ?></span>
            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold <?= $item['mail_status'] === 'sent' ? 'bg-emerald-50 text-emerald-700' : ($item['mail_status'] === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') ?>">Email <?= e(ucfirst($item['mail_status'])) ?></span>
          </summary>
          <div class="ml-6 mt-4 rounded-xl border border-slate-200/80 bg-page/60 p-4">
            <p class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-700"><?= e($item['message']) ?></p>
            <?php if ($item['mail_status'] === 'failed' && $item['mail_error'] !== ''): ?><p class="mt-3 text-xs text-red-700">SMTP: <?= e($item['mail_error']) ?></p><?php endif; ?>
            <div class="mt-4 flex flex-wrap gap-2">
              <a href="mailto:<?= e($item['email']) ?>" class="inline-flex h-8 items-center rounded-lg bg-brand-600 px-3 text-xs font-semibold text-white">Reply</a>
              <form method="post" action="./"><?= form_action('admin.markContactRead', url('contact-inbox')) ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="read" value="<?= empty($item['read_at']) ? '1' : '0' ?>"><button class="inline-flex h-8 items-center rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-600"><?= empty($item['read_at']) ? 'Mark read' : 'Mark unread' ?></button></form>
              <form method="post" action="./"><?= form_action('admin.deleteContactSubmission', url('contact-inbox')) ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="inline-flex h-8 items-center rounded-lg border border-red-200 px-3 text-xs font-semibold text-red-700" data-confirm="Delete this contact message?">Delete</button></form>
            </div>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
