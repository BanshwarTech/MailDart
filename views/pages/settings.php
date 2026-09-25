<?php
/**
 * Step 1: SMTP Settings (was src/components/SmtpSettingsPage.tsx).
 * Vars from the dashboard layout: $page, $currentUser, $userRow, $campaign, $smtp, $stepDone.
 *
 * The whole page is ONE <form>. Every button (Save, Test Connection, Send Live Test, the mode-switch
 * cards, the Gmail/Elastic help toggles, the quick-setup presets) is a submit button that overrides the
 * form's default hidden "action" field with its own name="action" value="smtp.xxx" — the browser only
 * sends the one that was actually clicked, together with every other field currently in the form. That
 * is how "clicking a preset keeps whatever you already typed" and "the result box survives Test/Send"
 * work without any JavaScript.
 */
$stored = $smtp;
$formData = [
    'enabled' => old('enabled', $stored['enabled'] ? '1' : '0') === '1',
    'host' => (string) old('host', $stored['host']),
    'port' => (int) old('port', $stored['port']),
    'secure' => old('secure', $stored['secure'] ? '1' : '') === '1',
    'username' => (string) old('username', $stored['username']),
    'password' => (string) old('password', $stored['password']),
    'fromName' => (string) old('fromName', $stored['fromName']),
    'fromEmail' => (string) old('fromEmail', $stored['fromEmail']),
    'replyTo' => (string) old('replyTo', $stored['replyTo']),
    'help' => (string) old('help', $stored['password'] === '' ? 'gmail' : ''),
];
$verifyStatus = take_flash_data('smtp_result');
$status = smtp_status($formData);
?>
<div class="max-w-5xl mx-auto">
  <div class="relative bg-surface border border-slate-200/80 rounded-2xl shadow-card text-slate-700 p-6 md:p-8">

    <div class="flex items-center gap-4 mb-6 border-b border-slate-200/80 pb-5">
      <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
        <?= icon('Server', 'w-5 h-5') ?>
      </div>
      <div>
        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Sender &amp; SMTP Settings</h2>
        <p class="text-sm text-slate-500">
          Enter your credentials so that all campaign emails are sent directly from your own email address
        </p>
      </div>
    </div>

    <!-- Account Storage Status Banner (always logged in in this app) -->
    <div class="mb-5 p-3 rounded-xl bg-sky-50 border border-sky-200 flex items-center justify-between text-xs text-sky-700">
      <div class="flex items-center gap-2">
        <?= icon('ShieldCheck', 'w-4 h-4 text-sky-600 shrink-0') ?>
        <span>
          Logged in as <strong class="text-slate-900"><?= e($currentUser['email']) ?></strong>. Your SMTP credentials are stored securely in your own account.
        </span>
      </div>
      <span class="text-[10px] bg-sky-100 text-sky-700 font-mono px-2 py-0.5 rounded border border-sky-200 shrink-0">
        Account Storage Active
      </span>
    </div>

    <form method="post" action="./">
      <?= form_action('smtp.save') ?>
      <input type="hidden" name="enabled" value="<?= $formData['enabled'] ? '1' : '0' ?>">
      <input type="hidden" name="help" value="<?= e($formData['help']) ?>">

      <!-- Mode Switcher -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
        <button
          type="submit"
          name="action"
          value="smtp.setModeLive"
          class="<?= $formData['enabled']
            ? 'p-4 rounded-xl border text-left transition flex flex-col justify-between bg-brand-50 border-brand-500 text-slate-900 shadow-sm'
            : 'p-4 rounded-xl border text-left transition flex flex-col justify-between bg-surface border-slate-200 text-slate-500 hover:bg-slate-50' ?>"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-brand-700 text-sm flex items-center gap-1.5">
              <?= icon('Server', 'w-4 h-4') ?> Real SMTP (Your Email)
            </span>
            <?php if ($formData['enabled']): ?><?= icon('CheckCircle2', 'w-4 h-4 text-brand-700') ?><?php endif; ?>
          </div>
          <p class="text-xs text-slate-500">
            Live mode. Real emails are delivered to inboxes through your Gmail or custom mail server.
          </p>
        </button>

        <button
          type="submit"
          name="action"
          value="smtp.setModeSandbox"
          class="<?= !$formData['enabled']
            ? 'p-4 rounded-xl border text-left transition flex flex-col justify-between bg-amber-50 border-amber-500 text-slate-900 shadow-sm'
            : 'p-4 rounded-xl border text-left transition flex flex-col justify-between bg-surface border-slate-200 text-slate-500 hover:bg-slate-50' ?>"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-amber-700 text-sm flex items-center gap-1.5">
              <?= icon('ShieldAlert', 'w-4 h-4') ?> Sandbox / Simulation Mode
            </span>
            <?php if (!$formData['enabled']): ?><?= icon('CheckCircle2', 'w-4 h-4 text-amber-600') ?><?php endif; ?>
          </div>
          <p class="text-xs text-slate-500">
            Safe simulation mode. Practise with the 5-minute timer and your full HTML without entering a password.
          </p>
        </button>
      </div>

      <!-- Sender Info -->
      <div class="space-y-4 mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
        <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
          <?= icon('Mail', 'w-4 h-4 text-sky-600') ?> Sender Information
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-slate-500 mb-1">
              Company / Sender Name
            </label>
            <input
              type="text"
              name="fromName"
              value="<?= e($formData['fromName']) ?>"
              placeholder="e.g. Alekh Banshwar / Acme Corp"
              class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            >
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">
              From Email Address
            </label>
            <input
              type="email"
              name="fromEmail"
              value="<?= e($formData['fromEmail']) ?>"
              placeholder="e.g. you@yourcompany.com"
              class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
            >
          </div>
        </div>
      </div>

      <!-- SMTP Server Credentials -->
      <?php if ($formData['enabled']): ?>
        <div class="space-y-4 mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200 animate-fade-in">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
              <?= icon('Key', 'w-4 h-4 text-brand-700') ?> SMTP Server Credentials
            </h3>

            <!-- Presets -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
              <span class="text-slate-500 text-[11px]">Quick Setup:</span>
              <button
                type="submit"
                name="action"
                value="smtp.applyPresetGmail"
                class="px-3 py-1 bg-brand-600 hover:bg-brand-500 text-white rounded-lg border border-brand-600 font-semibold flex items-center gap-1.5 transition shadow-button"
                title="Gmail: Default Recommended (smtp.gmail.com:587 using 16-letter App Password)"
              >
                <span>★ Gmail (Default)</span>
                <span class="text-[10px] bg-white/20 text-white px-1.5 py-0.5 rounded font-mono font-semibold">16-Letter App Pass</span>
              </button>
              <button
                type="submit"
                name="action"
                value="smtp.applyPresetElastic"
                class="px-2.5 py-1 bg-surface hover:bg-slate-50 text-sky-700 rounded-lg border border-slate-300 hover:border-slate-400 font-medium shadow-xs transition"
                title="Elastic Email: Most affordable high-volume SMTP ($0.50/1k)"
              >
                <span>⚡ Elastic Email</span>
              </button>
              <button
                type="submit"
                name="action"
                value="smtp.applyPresetBrevo"
                class="px-2 py-1 bg-surface hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
              >
                Brevo
              </button>
              <button
                type="submit"
                name="action"
                value="smtp.applyPresetSendgrid"
                class="px-2 py-1 bg-surface hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
              >
                SendGrid
              </button>
              <button
                type="submit"
                name="action"
                value="smtp.applyPresetSes"
                class="px-2 py-1 bg-surface hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
              >
                AWS SES
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
              <label class="block text-xs text-slate-500 mb-1">SMTP Host</label>
              <input
                type="text"
                name="host"
                value="<?= e($formData['host']) ?>"
                placeholder="smtp.elasticemail.com or smtp.gmail.com"
                class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
              >
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Port</label>
              <input
                type="number"
                name="port"
                value="<?= e($formData['port']) ?>"
                placeholder="2525, 587, or 465"
                class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
              >
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-slate-500 mb-1">
                SMTP Username / Email <span class="text-brand-700">*</span>
              </label>
              <input
                type="text"
                name="username"
                value="<?= e($formData['username']) ?>"
                placeholder="you@gmail.com"
                class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
              >
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1 flex items-center justify-between">
                <span>Password / API Key</span>
                <div class="flex items-center gap-1.5">
                  <button
                    type="submit"
                    name="action"
                    value="<?= $formData['help'] === 'elastic' ? 'smtp.setHelpNone' : 'smtp.setHelpElastic' ?>"
                    class="text-[11px] text-sky-600 hover:underline flex items-center gap-0.5"
                  >
                    <?= icon('HelpCircle', 'w-3 h-3') ?> Elastic Help
                  </button>
                  <span class="text-slate-400">|</span>
                  <button
                    type="submit"
                    name="action"
                    value="<?= $formData['help'] === 'gmail' ? 'smtp.setHelpNone' : 'smtp.setHelpGmail' ?>"
                    class="text-[11px] text-amber-600 hover:underline flex items-center gap-0.5"
                  >
                    <?= icon('HelpCircle', 'w-3 h-3') ?> Gmail
                  </button>
                </div>
              </label>
              <input
                type="password"
                name="password"
                value="<?= e($formData['password']) ?>"
                placeholder="Elastic API Key or Gmail App Password"
                class="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
              >
            </div>
          </div>

          <!-- Elastic Email Step-by-Step Guide -->
          <?php if ($formData['help'] === 'elastic'): ?>
            <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-xs text-slate-700 space-y-2 animate-fade-in">
              <div class="font-semibold text-sky-700 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                  <span>⚡ Elastic Email setup guide (affordable and reliable):</span>
                </span>
                <button type="submit" name="action" value="smtp.setHelpNone" class="text-slate-400 hover:text-slate-900">
                  ✕
                </button>
              </div>
              <ol class="list-decimal list-inside space-y-1.5 text-slate-600 text-[11px] leading-relaxed">
                <li>
                  <a href="https://elasticemail.com" target="_blank" rel="noopener noreferrer" class="text-sky-700 underline font-semibold inline-flex items-center gap-0.5">
                    Elastic Email <?= icon('ExternalLink', 'w-2.5 h-2.5') ?>
                  </a>
                  — create a free account or log in.
                </li>
                <li>
                  In the Elastic Email dashboard, open <strong>Settings → Manage API Keys</strong>.
                </li>
                <li>
                  Click <strong>&quot;Create API Key&quot;</strong>, give it <em>Full Access</em> and copy the generated <strong>API Key</strong>.
                </li>
                <li>
                  Fill in these details here:
                  <ul class="list-disc list-inside pl-4 mt-1 text-slate-600 space-y-0.5">
                    <li><strong>SMTP Host:</strong> <code class="bg-slate-100 border border-slate-200 text-brand-700 px-1.5 py-0.5 rounded">smtp.elasticemail.com</code></li>
                    <li><strong>Port:</strong> <code class="bg-slate-100 border border-slate-200 text-brand-700 px-1.5 py-0.5 rounded">2525</code> (or <code class="bg-slate-100 border border-slate-200 text-brand-700 px-1.5 py-0.5 rounded">587</code>)</li>
                    <li><strong>SMTP Username:</strong> the email you registered with Elastic Email</li>
                    <li><strong>Password:</strong> your generated <strong>API Key</strong></li>
                  </ul>
                </li>
                <li class="text-brand-700">
                  💡 <em>Benefit:</em> Elastic Email is one of the most affordable providers ($0.50 per 1,000 emails), and with 5-minute staggered sending its inbox delivery rate stays very high.
                </li>
              </ol>
            </div>
          <?php endif; ?>

          <!-- Gmail App Password Step-by-Step Guide -->
          <?php if ($formData['help'] === 'gmail'): ?>
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-slate-700 space-y-2">
              <div class="font-semibold text-amber-800 flex items-center justify-between">
                <span>📌 How to create a Gmail App Password (30 seconds):</span>
                <button type="submit" name="action" value="smtp.setHelpNone" class="text-slate-400 hover:text-slate-900">
                  ✕
                </button>
              </div>
              <ol class="list-decimal list-inside space-y-1 text-slate-600 text-[11px] leading-relaxed">
                <li>
                  Open your Google Account
                  <a href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer" class="text-brand-700 underline font-semibold">
                    Security Settings
                  </a>.
                </li>
                <li>Make sure <strong>2-Step Verification</strong> is turned ON.</li>
                <li>
                  Type <strong>&quot;App passwords&quot;</strong> in the search bar, or open App passwords under Security.
                </li>
                <li>Enter the app name <code>MailDart</code> and click <strong>Create</strong>.</li>
                <li>Copy the 16-letter code shown (e.g. <code>abcd efgh ijkl mnop</code>) and paste it in the Password field here.</li>
              </ol>
            </div>
          <?php endif; ?>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
              <input
                type="checkbox"
                name="secure"
                value="1"
                <?= checked($formData['secure']) ?>
                class="rounded border-slate-300 accent-brand-600 focus:ring-0"
              >
              Use SSL / TLS (only for port 465; keep off for 587)
            </label>

            <div class="flex items-center gap-2">
              <button
                type="submit"
                name="action"
                value="smtp.verify"
                class="px-3 py-1.5 bg-surface hover:bg-slate-50 text-xs font-semibold text-slate-700 border border-slate-300 hover:border-slate-400 rounded-lg shadow-xs flex items-center gap-1.5 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <?= icon('Server', 'w-3.5 h-3.5 text-brand-700') ?> Test Connection
              </button>

              <button
                type="submit"
                name="action"
                value="smtp.sendLiveTest"
                class="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-xs font-semibold text-white rounded-lg flex items-center gap-1.5 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-button"
                title="Send a real test email directly to your own address"
              >
                <?= icon('Send', 'w-3.5 h-3.5 text-white') ?> Send Live Test to My Email
              </button>
            </div>
          </div>

          <?php if ($verifyStatus): ?>
            <div class="p-3 rounded-xl text-xs flex items-start gap-2.5 <?= $verifyStatus['success'] ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
              <?= icon($verifyStatus['success'] ? 'CheckCircle2' : 'AlertTriangle', 'w-4 h-4 shrink-0 mt-0.5 ' . ($verifyStatus['success'] ? 'text-brand-700' : 'text-red-600')) ?>
              <span class="leading-relaxed"><?= e($verifyStatus['message']) ?></span>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Footer -->
      <div class="sticky bottom-0 z-10 -mx-6 md:-mx-8 -mb-6 md:-mb-8 mt-6 px-6 md:px-8 py-4 bg-surface/90 backdrop-blur-xl border-t border-slate-200/80 rounded-b-2xl flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-xs text-slate-500 text-center sm:text-left">
          <span>Active mode: </span>
          <strong class="<?= $status === 'live' ? 'text-brand-700 font-semibold' : 'text-amber-700' ?>">
            <?= e([
              'live' => '● Real SMTP (' . ($formData['fromEmail'] ?: $formData['username']) . ')',
              'incomplete' => '● Real SMTP (username / password missing)',
              'sandbox' => '● Safe Sandbox Simulation',
            ][$status]) ?>
          </strong>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
          <a href="<?= e(url('settings')) ?>" class="px-4 py-2 text-xs text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition">
            Discard changes
          </a>
          <button
            type="submit"
            name="action"
            value="smtp.save"
            class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-xl transition flex items-center gap-1.5 shadow-button"
          >
            <?= icon('CheckCircle2', 'w-4 h-4') ?>
            <span>Save Settings</span>
          </button>
        </div>
      </div>
    </form>

  </div>
</div>
