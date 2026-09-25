<?php
/**
 * Guide / documentation dashboard page (was src/components/GuidePage.tsx).
 * Vars from the app layout: $campaign, $smtp, $stepDone, $activeNav, $pageSubtitle, $currentUser, $userRow.
 * The scroll-spy table of contents (was a useEffect + IntersectionObserver) is plain <a href="#id"> anchors
 * enhanced by assets/js/guide.js; without JS the links still jump to each section, they just don't highlight.
 */
$intervalMinutes = $campaign['intervalMinutes'];
$pageScripts[] = 'js/guide.js';

$sections = [
    ['id' => 'getting-started', 'label' => 'Getting started', 'icon' => 'Rocket'],
    ['id' => 'templates', 'label' => 'Templates & variables', 'icon' => 'FileCode'],
    ['id' => 'excel', 'label' => 'Importing from Excel', 'icon' => 'FileSpreadsheet'],
    ['id' => 'interval', 'label' => 'Why one email every 5 min', 'icon' => 'Clock'],
    ['id' => 'delivery', 'label' => 'Delivery settings', 'icon' => 'Server'],
    ['id' => 'controls', 'label' => 'Campaign controls', 'icon' => 'SlidersHorizontal'],
    ['id' => 'faq', 'label' => 'FAQ & troubleshooting', 'icon' => 'HelpCircle'],
];

$providers = [
    ['name' => 'Gmail', 'host' => 'smtp.gmail.com', 'port' => '587 (STARTTLS)', 'username' => 'Your Gmail address', 'password' => '16-letter App Password', 'note' => 'Recommended for getting started'],
    ['name' => 'Elastic Email', 'host' => 'smtp.elasticemail.com', 'port' => '2525 or 587', 'username' => 'Registered login email', 'password' => 'API Key', 'note' => 'Most affordable (~$0.50 / 1,000 emails)'],
    ['name' => 'Brevo', 'host' => 'smtp-relay.brevo.com', 'port' => '587', 'username' => 'Brevo login email', 'password' => 'SMTP key', 'note' => 'Generous free tier'],
    ['name' => 'SendGrid', 'host' => 'smtp.sendgrid.net', 'port' => '587', 'username' => 'apikey', 'password' => 'SendGrid API Key', 'note' => 'Good for high volume'],
    ['name' => 'Amazon SES', 'host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => '587', 'username' => 'SES SMTP username', 'password' => 'SES SMTP password', 'note' => 'Region-specific host'],
];

$variables = [
    ['tag' => 'name', 'meaning' => 'Recipient name'],
    ['tag' => 'email', 'meaning' => 'Recipient email address'],
    ['tag' => 'company', 'meaning' => 'Your company name (or the Company column from Excel)'],
    ['tag' => 'discount', 'meaning' => 'Discount / coupon code'],
    ['tag' => 'festival', 'meaning' => 'Campaign category title, e.g. "Diwali (Deepavali)"'],
];

$faqs = [
    ['q' => 'My test email went to the spam folder. What should I do?', 'a' => 'Use a proper sender name and a real From address, personalise the subject with {{name}}, and avoid too many images or words like "FREE" in capitals. Keep the 5-minute interval for real campaigns. A verified domain (SPF / DKIM) with providers like Elastic Email or Brevo helps a lot.'],
    ['q' => 'Gmail says "Username and Password not accepted".', 'a' => 'Gmail does not allow your normal password over SMTP. Turn on 2-Step Verification, create an App Password and paste the 16-letter code (without spaces is fine) in the Password field. Use host smtp.gmail.com with port 587 and keep SSL off (587 uses STARTTLS automatically).'],
    ['q' => 'What is the difference between Sandbox and Real SMTP?', 'a' => 'Sandbox only simulates sending: the timer, logs and statuses work, but no email leaves the app. Real SMTP actually delivers emails through your mail account. Always run a quick Sandbox or test send before a real campaign.'],
    ['q' => 'Can I close the browser while the campaign is running?', 'a' => 'No. The dispatcher runs in this browser tab, so keep the tab open until the campaign is complete. If you close it, the campaign pauses and you can resume from where it stopped.'],
    ['q' => 'Some rows from my Excel file were not imported.', 'a' => 'Rows without a valid email address and duplicate emails are skipped. Check that your sheet has a column named Email, Mail or Receiver, and that each row has a proper email address.'],
    ['q' => 'Where is my data stored?', 'a' => 'Your campaign, recipients and settings are saved securely on the server. Your SMTP settings are stored securely with your account so you can use them on another device.'],
];

$quickStart = [
    ['title' => 'Connect your email', 'text' => 'Add Gmail, Elastic Email or any SMTP account, or stay in Sandbox mode to practise.', 'label' => 'SMTP Settings', 'href' => url('settings')],
    ['title' => 'Add recipients', 'text' => 'Upload an Excel / CSV sheet or add people one by one.', 'label' => 'Recipients', 'href' => url('recipients')],
    ['title' => 'Design the email', 'text' => 'Pick a ready template, generate one with AI, or paste your own HTML.', 'label' => 'Template Studio', 'href' => url('editor')],
    ['title' => 'Send a test', 'text' => 'Send one email to yourself and check how it looks in a real inbox.', 'label' => 'Send Test Email', 'href' => url('guide', ['modal' => 'test'])],
    ['title' => 'Start the campaign', 'text' => "Press Start. One email goes out every {$intervalMinutes} min until everyone is covered.", 'label' => 'Dispatcher', 'href' => url('dispatch')],
];

$controls = [
    ['icon' => 'Play', 'name' => 'Start / Resume', 'text' => 'Begins sending from the next pending recipient.', 'tone' => 'text-brand-700 bg-brand-50'],
    ['icon' => 'Pause', 'name' => 'Pause', 'text' => 'Stops the timer. Resume any time from the same point.', 'tone' => 'text-amber-600 bg-amber-50'],
    ['icon' => 'Zap', 'name' => 'Send Immediately', 'text' => 'Skips the wait and sends to the next recipient right now.', 'tone' => 'text-brand-700 bg-brand-50'],
    ['icon' => 'RotateCcw', 'name' => 'Reset Statuses', 'text' => 'Marks everyone as pending again so the campaign can be re-sent.', 'tone' => 'text-slate-600 bg-slate-100'],
    ['icon' => 'RotateCcw', 'name' => 'Retry Failed', 'text' => 'Re-sends to a single recipient whose delivery failed.', 'tone' => 'text-red-600 bg-red-50'],
    ['icon' => 'Download', 'name' => 'Export CSV', 'text' => 'Downloads the delivery logs as a report from Delivery Logs.', 'tone' => 'text-sky-600 bg-sky-50'],
];

$card = 'bg-surface border border-slate-200/80 rounded-2xl shadow-card p-6 md:p-8 scroll-mt-24';
$tagClass = 'px-1.5 py-0.5 bg-slate-100 ring-1 ring-inset ring-slate-200 text-brand-700 font-mono text-xs rounded-md whitespace-nowrap';
?>
<div class="space-y-6">
  <!-- Hero -->
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0A1B2E] via-[#112A46] to-[#1B3B5A] ring-1 ring-inset ring-[#34506D]/60 text-white shadow-card">
    <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(rgb(255_255_255/0.12)_1px,transparent_1px)] [background-size:18px_18px] [mask-image:linear-gradient(to_left,black,transparent_70%)]"></div>
    <div class="absolute -top-24 -right-16 w-80 h-80 rounded-full bg-brand-500/30 blur-3xl"></div>
    <div class="relative p-6 md:p-10">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-inset ring-white/15 text-[11px] font-semibold uppercase tracking-[0.08em] text-[#E1E8F0]">
        <?= icon('BookOpen', 'w-3.5 h-3.5') ?>
        Documentation
      </span>
      <h2 class="mt-4 text-2xl md:text-4xl font-semibold tracking-tight">MailDart Campaign Guide</h2>
      <p class="mt-3 max-w-2xl text-sm md:text-base text-[#C4D2E1] leading-relaxed">
        Everything you need to plan, personalise and send email campaigns that land in the inbox, from your first test email
        to a full campaign with hundreds of recipients.
      </p>
      <div class="mt-6 flex flex-wrap gap-2">
        <a href="#getting-started" class="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-[#FFFFFF] rounded-lg shadow-button transition">
          <?= icon('Rocket', 'w-4 h-4 text-[#1B3B5A]') ?>
          Quick start
        </a>
        <a href="#faq" class="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium text-white bg-white/10 hover:bg-white/15 ring-1 ring-inset ring-white/20 rounded-lg transition">
          <?= icon('HelpCircle', 'w-4 h-4') ?>
          FAQ &amp; troubleshooting
        </a>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-6 items-start">
    <!-- Table of contents -->
    <aside class="hidden lg:block sticky top-24">
      <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">On this page</p>
      <nav class="space-y-0.5 border-l border-slate-200">
        <?php foreach ($sections as $i => $s): ?>
          <a
            href="#<?= e($s['id']) ?>"
            data-guide-toc-link="<?= e($s['id']) ?>"
            class="-ml-px block w-full text-left pl-4 pr-2 py-1.5 text-sm border-l-2 transition <?= $i === 0 ? 'border-brand-600 text-brand-700 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' ?>"
          >
            <?= e($s['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <!-- Mobile table of contents -->
    <div class="lg:hidden -mx-1 flex gap-2 overflow-x-auto pb-1">
      <?php foreach ($sections as $s): ?>
        <a href="#<?= e($s['id']) ?>" class="shrink-0 px-3 py-1.5 text-xs font-medium text-slate-600 bg-surface ring-1 ring-inset ring-slate-200 rounded-full">
          <?= e($s['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Content -->
    <div class="space-y-6 min-w-0">
      <!-- 1. Getting started -->
      <section id="getting-started" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('Rocket', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">Step by step</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Getting started</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Your first campaign in five simple steps. Each step has a shortcut to the right screen.</p>
          </div>
        </div>
        <ol class="relative space-y-4">
          <?php foreach ($quickStart as $i => $step): ?>
            <li class="relative flex gap-4">
              <?php if ($i < count($quickStart) - 1): ?>
                <span class="absolute left-4 top-9 bottom-[-16px] w-px bg-slate-200"></span>
              <?php endif; ?>
              <span class="relative z-10 w-8 h-8 shrink-0 rounded-full bg-brand-600 text-white text-sm font-semibold flex items-center justify-center shadow-button"><?= $i + 1 ?></span>
              <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 px-4 py-3">
                <div>
                  <p class="text-sm font-semibold text-slate-900"><?= e($step['title']) ?></p>
                  <p class="text-sm text-slate-500"><?= e($step['text']) ?></p>
                </div>
                <a href="<?= e($step['href']) ?>" class="shrink-0 self-start sm:self-auto inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:bg-brand-50 px-2.5 py-1.5 rounded-lg transition">
                  <?= e($step['label']) ?>
                  <?= icon('ArrowRight', 'w-3.5 h-3.5') ?>
                </a>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <!-- 2. Templates -->
      <section id="templates" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('FileCode', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">Template Studio</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Templates &amp; variables</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Paste any HTML, choose a ready-made festive or business template, or generate a new one with AI in seconds.</p>
          </div>
        </div>
        <div class="grid sm:grid-cols-3 gap-3 mb-6">
          <div class="rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 p-4">
            <p class="text-sm font-semibold text-slate-900">Ready-made templates</p>
            <p class="text-sm text-slate-500 mt-1">Diwali, Eid, Christmas, Holi, newsletters, product launches and more.</p>
          </div>
          <div class="rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 p-4">
            <p class="text-sm font-semibold text-slate-900">AI Studio</p>
            <p class="text-sm text-slate-500 mt-1">Describe your campaign and get a complete HTML email generated for you.</p>
          </div>
          <div class="rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 p-4">
            <p class="text-sm font-semibold text-slate-900">Custom HTML</p>
            <p class="text-sm text-slate-500 mt-1">Paste your own code and preview it on desktop and mobile instantly.</p>
          </div>
        </div>
        <p class="text-sm font-semibold text-slate-900 mb-2">Personalisation tags</p>
        <p class="text-sm text-slate-500 mb-3">Use these in the subject line or the HTML. Each tag is replaced with the recipient&apos;s own data.</p>
        <div class="border border-slate-200 rounded-xl overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 w-40">Tag</th>
                <th class="text-left px-4 py-2.5">Replaced with</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($variables as $v): ?>
                <tr class="border-t border-slate-100">
                  <td class="px-4 py-2.5"><code class="<?= $tagClass ?>"><?= e('{{' . $v['tag'] . '}}') ?></code></td>
                  <td class="px-4 py-2.5 text-slate-700"><?= e($v['meaning']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-5 rounded-xl ring-1 ring-inset px-4 py-3.5 flex gap-3 bg-brand-50 ring-brand-200 text-brand-900">
          <div class="mt-0.5 shrink-0"><?= icon('CheckCircle2', 'w-4 h-4 text-brand-700') ?></div>
          <div class="text-sm leading-relaxed">
            <p class="font-semibold">Example</p>
            <div class="opacity-90 mt-0.5">Subject: <code class="<?= $tagClass ?>">Happy Diwali, {{name}}! A gift from {{company}}</code> becomes &#8220;Happy Diwali, Priya! A gift from Acme Global&#8221;.</div>
          </div>
        </div>
      </section>

      <!-- 3. Excel -->
      <section id="excel" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('FileSpreadsheet', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">Recipients</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Importing from Excel</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">No need to type anything by hand. Upload a .xlsx, .xls or .csv file and MailDart reads the columns for you.</p>
          </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <div class="rounded-xl ring-1 ring-inset ring-brand-200 bg-brand-50/60 p-4">
            <p class="text-sm font-semibold text-brand-900">Email column (required)</p>
            <p class="text-sm text-brand-800/80 mt-1">Name it <code class="<?= $tagClass ?>">Email</code>, <code class="<?= $tagClass ?>">Mail</code> or <code class="<?= $tagClass ?>">Receiver</code>.</p>
          </div>
          <div class="rounded-xl ring-1 ring-inset ring-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-900">Name column (recommended)</p>
            <p class="text-sm text-slate-500 mt-1">Name it <code class="<?= $tagClass ?>">Name</code> or <code class="<?= $tagClass ?>">Full Name</code>, used by <code class="<?= $tagClass ?>">{{name}}</code>.</p>
          </div>
        </div>
        <p class="text-sm font-semibold text-slate-900 mt-6 mb-2">Every extra column becomes a variable</p>
        <div class="border border-slate-200 rounded-xl overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5">Excel column</th>
                <th class="text-left px-4 py-2.5">Becomes tag</th>
                <th class="text-left px-4 py-2.5">Example value</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ([['City', '{{city}}', 'Mumbai'], ['Gift Item', '{{gift_item}}', 'Dry Fruits Box'], ['Order ID', '{{order_id}}', 'ORD-10452']] as [$col, $tag, $ex]): ?>
                <tr class="border-t border-slate-100">
                  <td class="px-4 py-2.5 font-medium text-slate-900"><?= e($col) ?></td>
                  <td class="px-4 py-2.5"><code class="<?= $tagClass ?>"><?= e($tag) ?></code></td>
                  <td class="px-4 py-2.5 text-slate-500"><?= e($ex) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-5 rounded-xl ring-1 ring-inset px-4 py-3.5 flex gap-3 bg-sky-50 ring-sky-200 text-sky-900">
          <div class="mt-0.5 shrink-0"><?= icon('Info', 'w-4 h-4 text-sky-600') ?></div>
          <div class="text-sm leading-relaxed">
            <p class="font-semibold">Tip</p>
            <div class="opacity-90 mt-0.5">Use the <strong>Excel Format</strong> button on the Recipients page to download a ready sample sheet. Rows without a valid email
              and duplicate emails are skipped automatically.</div>
          </div>
        </div>
        <a href="<?= e(url('recipients')) ?>" class="mt-5 inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-slate-700 bg-surface hover:bg-slate-50 border border-slate-300 rounded-lg shadow-xs transition">
          Go to Recipients <?= icon('ArrowRight', 'w-3.5 h-3.5') ?>
        </a>
      </section>

      <!-- 4. Interval -->
      <section id="interval" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('Clock', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">Deliverability</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Why one email every 5 minutes?</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Sending slowly is the simplest way to stay out of the spam folder.</p>
          </div>
        </div>
        <div class="grid md:grid-cols-2 gap-3">
          <div class="rounded-xl ring-1 ring-inset ring-red-200 bg-red-50/60 p-5">
            <p class="text-sm font-semibold text-red-900">Bulk blast (all at once)</p>
            <p class="text-sm text-red-800/80 mt-1 leading-relaxed">
              Hundreds of emails in the same second look like a bot to <strong>Gmail, Yahoo and Outlook</strong>. Their spam filters
              block the sender, and your emails land in spam instead of the inbox.
            </p>
          </div>
          <div class="rounded-xl ring-1 ring-inset ring-brand-200 bg-brand-50/60 p-5">
            <p class="text-sm font-semibold text-brand-900">MailDart staggered sending</p>
            <p class="text-sm text-brand-800/80 mt-1 leading-relaxed">
              One personalised email goes to one recipient at a time, with a gap of 5 minutes (300 seconds). The load stays low and
              your emails look like normal, one-to-one mail.
            </p>
          </div>
        </div>
        <div class="mt-5 rounded-xl ring-1 ring-inset px-4 py-3.5 flex gap-3 bg-amber-50 ring-amber-200 text-amber-900">
          <div class="mt-0.5 shrink-0"><?= icon('AlertTriangle', 'w-4 h-4 text-amber-600') ?></div>
          <div class="text-sm leading-relaxed">
            <p class="font-semibold">Testing only</p>
            <div class="opacity-90 mt-0.5">You can set the interval to 1 minute or 30 seconds while testing, but keep 5 minutes for real campaigns.</div>
          </div>
        </div>
      </section>

      <!-- 5. Delivery -->
      <section id="delivery" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('Server', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">SMTP</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Delivery settings</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Choose how emails are sent. Start in Sandbox, then switch to your real mail account when you are ready.</p>
          </div>
        </div>
        <div class="grid md:grid-cols-2 gap-3 mb-6">
          <div class="rounded-xl ring-1 ring-inset ring-amber-200 bg-amber-50/60 p-5">
            <div class="flex items-center gap-2 text-amber-900">
              <?= icon('FlaskConical', 'w-4 h-4') ?>
              <p class="text-sm font-semibold">Sandbox mode</p>
            </div>
            <p class="text-sm text-amber-900/75 mt-1.5 leading-relaxed">
              Practise the complete flow (timer, statuses and logs) safely, without any password. No real email is sent.
            </p>
          </div>
          <div class="rounded-xl ring-1 ring-inset ring-brand-200 bg-brand-50/60 p-5">
            <div class="flex items-center gap-2 text-brand-900">
              <?= icon('ShieldCheck', 'w-4 h-4') ?>
              <p class="text-sm font-semibold">Real SMTP mode</p>
            </div>
            <p class="text-sm text-brand-900/75 mt-1.5 leading-relaxed">
              Real emails are delivered to inboxes through your Gmail or email provider, from your own address.
            </p>
          </div>
        </div>

        <p class="text-sm font-semibold text-slate-900 mb-2">Supported providers</p>
        <div class="border border-slate-200 rounded-xl overflow-x-auto">
          <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5">Provider</th>
                <th class="text-left px-4 py-2.5">Host</th>
                <th class="text-left px-4 py-2.5">Port</th>
                <th class="text-left px-4 py-2.5">Username / Password</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($providers as $p): ?>
                <tr class="border-t border-slate-100 align-top">
                  <td class="px-4 py-3">
                    <p class="font-semibold text-slate-900"><?= e($p['name']) ?></p>
                    <p class="text-xs text-slate-500"><?= e($p['note']) ?></p>
                  </td>
                  <td class="px-4 py-3"><code class="<?= $tagClass ?>"><?= e($p['host']) ?></code></td>
                  <td class="px-4 py-3 text-slate-700 whitespace-nowrap"><?= e($p['port']) ?></td>
                  <td class="px-4 py-3 text-slate-700">
                    <?= e($p['username']) ?>
                    <span class="block text-xs text-slate-500"><?= e($p['password']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-6 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 p-5">
          <p class="text-sm font-semibold text-slate-900">How to create a Gmail App Password</p>
          <ol class="mt-3 space-y-2 text-sm text-slate-600 list-decimal list-inside">
            <li>
              Open your
              <a href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer" class="text-brand-700 font-medium underline underline-offset-2">Google Account security settings</a>.
            </li>
            <li>Make sure <strong class="text-slate-900">2-Step Verification</strong> is turned on.</li>
            <li>Search for <strong class="text-slate-900">App passwords</strong> and open it.</li>
            <li>Enter the app name <code class="<?= $tagClass ?>">MailDart</code> and click <strong class="text-slate-900">Create</strong>.</li>
            <li>Copy the 16-letter code and paste it in the Password field of SMTP Settings.</li>
          </ol>
        </div>

        <a href="<?= e(url('settings')) ?>" class="mt-5 inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
          <?= icon('Server', 'w-3.5 h-3.5') ?>
          Open SMTP Settings
        </a>
      </section>

      <!-- 6. Controls -->
      <section id="controls" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('SlidersHorizontal', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">Dispatcher</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Campaign controls</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">You stay in full control while the campaign is running.</p>
          </div>
        </div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <?php foreach ($controls as $c): ?>
            <div class="rounded-xl ring-1 ring-inset ring-slate-200/80 p-4 hover:shadow-card-hover transition">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center <?= $c['tone'] ?>">
                <?= icon($c['icon'], 'w-4 h-4') ?>
              </div>
              <p class="mt-3 text-sm font-semibold text-slate-900"><?= e($c['name']) ?></p>
              <p class="text-sm text-slate-500 mt-0.5"><?= e($c['text']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-5 rounded-xl ring-1 ring-inset px-4 py-3.5 flex gap-3 bg-amber-50 ring-amber-200 text-amber-900">
          <div class="mt-0.5 shrink-0"><?= icon('AlertTriangle', 'w-4 h-4 text-amber-600') ?></div>
          <div class="text-sm leading-relaxed">
            <p class="font-semibold">Keep this tab open</p>
            <div class="opacity-90 mt-0.5">The dispatcher runs in your browser. Keep the MailDart tab open until the campaign is complete.</div>
          </div>
        </div>
      </section>

      <!-- 7. FAQ -->
      <section id="faq" data-guide-section class="<?= $card ?>">
        <div class="flex items-start gap-4 mb-6">
          <div class="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <?= icon('HelpCircle', 'w-5 h-5') ?>
          </div>
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">Help</p>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">FAQ &amp; troubleshooting</h2>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Quick answers to the most common questions.</p>
          </div>
        </div>
        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
          <?php foreach ($faqs as $i => $f): ?>
            <details name="guide-faq" class="group"<?= $i === 0 ? ' open' : '' ?>>
              <summary class="list-none [&amp;::-webkit-details-marker]:hidden cursor-pointer w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 transition">
                <span class="text-sm font-semibold text-slate-900"><?= e($f['q']) ?></span>
                <?= icon('ChevronDown', 'w-4 h-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180') ?>
              </summary>
              <p class="px-5 pb-4 -mt-1 text-sm text-slate-600 leading-relaxed"><?= e($f['a']) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Footer CTA -->
      <div class="rounded-2xl bg-surface border border-slate-200/80 shadow-card p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <p class="text-base font-semibold text-slate-900">Got it? Let&apos;s start your campaign.</p>
          <p class="text-sm text-slate-500">Head to the Dispatcher when your SMTP, recipients and template are ready.</p>
        </div>
        <a href="<?= e(url('dispatch')) ?>" class="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
          Go to Dispatcher <?= icon('ArrowRight', 'w-4 h-4') ?>
        </a>
      </div>
    </div>
  </div>
</div>
