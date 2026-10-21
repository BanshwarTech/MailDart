<?php
/** Shared public legal page. Vars: $legalTitle, $legalIntro, $legalSections. */
$legalContact = env('LEGAL_CONTACT_EMAIL') ?: env('MAIL_FROM_EMAIL');
$legalEntity = env('LEGAL_ENTITY_NAME');
$legalAddress = env('LEGAL_ADDRESS');
$grievanceOfficer = env('LEGAL_GRIEVANCE_OFFICER');
$isLoggedIn = isset($currentUser) && $currentUser !== null;
$footerPlans = plans_enabled() ? plans_active() : [];
$legalPagesEnabled = seo_pricing_is_public();
$legalEyebrow = $legalEyebrow ?? 'Legal';
$legalWordCount = str_word_count(strip_tags($legalIntro));
foreach ($legalSections as $section) {
  $legalWordCount += str_word_count(strip_tags(implode(' ', $section['paragraphs'] ?? [])));
  $legalWordCount += str_word_count(strip_tags(implode(' ', $section['items'] ?? [])));
}
$legalReadMinutes = max(2, (int) ceil($legalWordCount / 220));
?>
<style>
  .legal-main{max-width:1120px;margin-inline:auto;padding:40px 24px 64px}
  .legal-hero{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 190px;align-items:center;gap:28px;overflow:hidden;border:1px solid rgba(52,80,109,.8);border-radius:28px;padding:40px;background-image:radial-gradient(ellipse at 90% 0%,rgba(85,115,146,.30),transparent 42%),linear-gradient(135deg,#112a46,#0d2239);box-shadow:0 18px 50px rgba(0,0,0,.16)}
  .legal-hero:before{content:"";position:absolute;inset:0;pointer-events:none;opacity:.3;background-image:linear-gradient(rgba(122,140,166,.16) 1px,transparent 1px),linear-gradient(90deg,rgba(122,140,166,.16) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(to bottom right,#000,transparent 75%)}
  .legal-hero-content{position:relative;max-width:760px}
  .legal-hero-contact{grid-template-columns:minmax(0,1fr);padding:32px}
  .legal-cover{position:relative;z-index:1;justify-self:end;width:170px;min-height:200px;padding:18px;border:1px solid rgba(143,180,214,.34);border-radius:14px;background:linear-gradient(145deg,rgba(225,232,240,.12),rgba(10,27,46,.75));box-shadow:10px 12px 0 -5px rgba(85,115,146,.24),0 18px 32px rgba(0,0,0,.2);transform:rotate(3deg)}
  .legal-cover-mark{display:flex;width:42px;height:42px;align-items:center;justify-content:center;border-radius:12px;background:#557392;color:#f2f6f8;font-family:var(--font-mono);font-size:12px;font-weight:700;letter-spacing:.08em}
  .legal-cover-lines{display:grid;gap:7px;margin-top:24px}.legal-cover-lines i{display:block;height:4px;border-radius:99px;background:rgba(179,194,210,.38)}.legal-cover-lines i:nth-child(2){width:78%}.legal-cover-lines i:nth-child(3){width:55%}
  .legal-layout{display:grid;grid-template-columns:220px minmax(0,1fr);align-items:start;gap:24px;margin-top:32px}
  .legal-index{position:sticky;top:96px;border:1px solid #1f4060;border-radius:16px;background:#112a46;padding:20px}
  .legal-index nav a{display:flex;align-items:flex-start;gap:8px;padding:9px 10px;line-height:1.5}.legal-index nav a span{display:block;flex:0 0 20px;margin:0!important;text-align:right;white-space:nowrap}
  .legal-entries{display:flex;min-width:0;flex-direction:column;gap:16px}
  .legal-entry{position:relative;scroll-margin-top:96px;border:1px solid #1f4060;border-radius:16px;background:linear-gradient(120deg,rgba(85,115,146,.07),transparent 55%),#112a46;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.08);transition:border-color .2s ease,transform .2s ease}.legal-entry:hover{border-color:#34506d;transform:translateY(-1px)}
  .legal-entry-number{border:1px solid #34506d!important;background:linear-gradient(145deg,#1b3b5a,#0a1b2e)!important;color:#b3c2d2!important}
  .legal-contact{scroll-margin-top:96px;border:1px solid #34506d;border-radius:16px;background:#16324f;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.1)}
  .contact-contact-layout{display:grid;grid-template-columns:minmax(220px,.72fr) minmax(0,1.65fr);align-items:start;gap:20px;margin:24px auto 0}
  .contact-detail-card,.contact-form-card{border:1px solid #1f4060;border-radius:18px;background:#112a46;padding:clamp(20px,3vw,28px)}
  .contact-detail-card{background:linear-gradient(160deg,#16324f,#112a46 60%)}.contact-detail-list{display:grid;gap:0;margin-top:22px}.contact-detail-item{padding:15px 0;border-top:1px solid rgba(52,80,109,.65)}.contact-detail-item:first-child{border-top:0;padding-top:0}.contact-detail-item:last-child{padding-bottom:0}.contact-detail-dot{display:inline-flex;width:25px;height:25px;align-items:center;justify-content:center;border:1px solid #34506d;border-radius:8px;background:#0a1b2e;color:#8fb4d6;font-size:12px;font-weight:700}.contact-detail-email{overflow-wrap:anywhere;color:#d6e4f1;text-decoration:underline;text-decoration-color:#557392;text-underline-offset:3px}
  .contact-form-card{min-width:0}
  .contact-form-header{padding-bottom:20px;border-bottom:1px solid #1f4060}.contact-form-header-copy{min-width:0}.contact-form-badge{color:#8fb4d6;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
  .contact-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px;margin-top:22px}.contact-field{display:grid;gap:7px}.contact-field-wide{grid-column:1/-1}.contact-input{width:100%;min-height:46px;border:1px solid #34506d;border-radius:9px;background:#0a1b2e;padding:11px 13px;color:#f2f6f8;font:inherit;font-size:14px;outline:none;transition:border-color .18s ease,box-shadow .18s ease}.contact-input:hover{border-color:#557392}.contact-input:focus{border-color:#8fb4d6;box-shadow:0 0 0 3px rgba(143,180,214,.12)}.contact-input::placeholder{color:#7a8ca6}.contact-field textarea{min-height:144px;line-height:1.6;resize:vertical}.contact-field select{appearance:none;background-image:linear-gradient(45deg,transparent 50%,#a1b2c4 50%),linear-gradient(135deg,#a1b2c4 50%,transparent 50%);background-position:calc(100% - 19px) 20px,calc(100% - 14px) 20px;background-size:5px 5px,5px 5px;background-repeat:no-repeat}.contact-honeypot{position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden}
  .contact-submit{display:inline-flex;min-height:44px;align-items:center;justify-content:center;gap:9px;border:1px solid #7896b2;border-radius:9px;background:#557392;padding:0 17px;color:#fff;font-size:13px;font-weight:700;transition:background .18s ease,border-color .18s ease}.contact-submit:hover{border-color:#8fb4d6;background:#6584a3}.contact-submit:focus-visible{outline:3px solid #8fb4d6;outline-offset:3px}
  @media(max-width:760px){.legal-main{padding:28px 16px 44px}.legal-hero{grid-template-columns:minmax(0,1fr);padding:26px 22px;border-radius:22px}.legal-cover{display:none}.legal-layout{grid-template-columns:minmax(0,1fr);gap:18px}.legal-index{position:static;padding:16px}.legal-index ol{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px}.legal-entry,.legal-contact{padding:20px}.contact-contact-layout{grid-template-columns:minmax(0,1fr);gap:14px}.contact-detail-card,.contact-form-card{padding:20px}.contact-detail-list{grid-template-columns:repeat(2,minmax(0,1fr));column-gap:18px}.contact-detail-item:nth-child(2){border-top:0;padding-top:0}.contact-fields{grid-template-columns:minmax(0,1fr)}.contact-field-wide{grid-column:auto}}
  @media(max-width:420px){.legal-index ol{grid-template-columns:minmax(0,1fr)}}
</style>
<div class="min-h-screen bg-page text-slate-900">
  <header class="sticky top-0 z-40 border-b border-slate-200/60 bg-page/70 backdrop-blur-xl">
    <div class="mx-auto flex h-16 items-center justify-between gap-4 px-4 sm:px-6" style="width: min(1400px, calc(100% - 80px)); max-width: 100%">
      <a href="<?= e(url('home')) ?>" aria-label="MailDart home"><?php partial('app_logo', ['size' => 'md', 'showTagline' => false]); ?></a>
      <nav class="hidden items-center gap-1 text-sm md:flex" aria-label="Main">
        <a href="<?= e(url('home')) ?>#features" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">Features</a>
        <a href="<?= e(url('home')) ?>#how" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">How it works</a>
        <a href="<?= e(url('home')) ?>#why" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">Why MailDart</a>
        <?php if ($footerPlans): ?><a href="<?= e(url('pricing')) ?>" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">Pricing</a><?php endif; ?>
        <a href="<?= e(url('home')) ?>#faq" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">FAQ</a>
      </nav>
      <?php if ($isLoggedIn): ?>
        <a href="<?= e(url('overview')) ?>" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-4 text-xs font-semibold text-slate-900 shadow-button transition hover:bg-slate-100">Open Dashboard <?= icon('ArrowRight', 'w-3.5 h-3.5') ?></a>
      <?php else: ?>
        <div class="flex items-center gap-1.5">
          <a href="<?= e(url('login')) ?>" class="inline-flex h-9 items-center rounded-lg px-3.5 text-xs font-semibold text-slate-300 transition hover:text-white">Log in</a>
          <a href="<?= e(url('register')) ?>" class="inline-flex h-9 items-center rounded-lg bg-[#E1E8F0] px-4 text-xs font-semibold text-[#0A1B2E] transition hover:bg-white">Sign up free</a>
        </div>
      <?php endif; ?>
    </div>
  </header>
  <main class="legal-main">
    <section class="legal-hero <?= !empty($contactFormEnabled) ? 'legal-hero-contact' : '' ?>">
      <div class="legal-hero-content">
        <p class="inline-flex items-center gap-2 rounded-full border border-slate-300/70 bg-page/50 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400"><?= icon('ShieldCheck', 'h-3.5 w-3.5 text-[#8fb4d6]') ?>MailDart Pro <span class="text-slate-500">/</span><?= e($legalEyebrow) ?></p>
        <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight text-slate-900 sm:text-5xl"><?= e($legalTitle) ?></h1>
        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8"><?= e($legalIntro) ?></p>
        <?php if (empty($contactFormEnabled)): ?>
          <div class="mt-7 inline-flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-slate-300/70 bg-page/60 px-4 py-2.5 text-xs text-slate-500"><span class="font-semibold text-slate-700">21 October 2026</span><span class="text-slate-400">·</span><span><?= $legalReadMinutes ?> min read</span></div>
        <?php endif; ?>
      </div>
      <?php if (empty($contactFormEnabled)): ?><div class="legal-cover" aria-hidden="true"><span class="legal-cover-mark">MD</span><p class="mt-5 text-[10px] font-semibold uppercase tracking-[.18em] text-slate-400">Public page</p><p class="mt-1 text-xs font-semibold text-slate-700">MailDart Pro</p><div class="legal-cover-lines"><i></i><i></i><i></i></div><p class="mt-5 font-mono-code text-[10px] uppercase tracking-wider text-slate-400"><?= e(strtoupper($legalEyebrow)) ?> / <?= date('Y') ?></p></div><?php endif; ?>
    </section>
    <?php if (!empty($contactFormEnabled)): ?>
      <?php partial('flash_messages', ['flashes' => take_flashes()]); ?>
      <div class="contact-contact-layout">
      <aside class="contact-detail-card" aria-labelledby="contact-detail-title">
        <p class="contact-form-badge">Support</p>
        <h2 id="contact-detail-title" class="mt-2 text-xl font-semibold tracking-tight text-slate-900">How we can help</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Choose the closest topic and include enough context for us to understand your request.</p>
        <div class="contact-detail-list">
          <div class="contact-detail-item"><div class="flex items-center gap-2.5"><span class="contact-detail-dot">01</span><h3 class="text-sm font-semibold text-slate-800">Technical support</h3></div><p class="mt-2 pl-[35px] text-xs leading-5 text-slate-400">Mention the page or campaign step and any error you saw.</p></div>
          <div class="contact-detail-item"><div class="flex items-center gap-2.5"><span class="contact-detail-dot">02</span><h3 class="text-sm font-semibold text-slate-800">Account or billing</h3></div><p class="mt-2 pl-[35px] text-xs leading-5 text-slate-400">Use the email address connected to your MailDart account.</p></div>
          <div class="contact-detail-item"><div class="flex items-center gap-2.5"><span class="contact-detail-dot">03</span><h3 class="text-sm font-semibold text-slate-800">Privacy requests</h3></div><p class="mt-2 pl-[35px] text-xs leading-5 text-slate-400">Tell us what data or account your request concerns.</p></div>
        </div>
        <?php if ($legalContact): ?><div class="mt-5 border-t border-[#34506d]/70 pt-4"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Email us directly</p><a class="contact-detail-email mt-1.5 inline-block text-sm" href="mailto:<?= e($legalContact) ?>"><?= e($legalContact) ?></a></div><?php endif; ?>
        <p class="mt-4 rounded-lg border border-[#34506d]/70 bg-[#0a1b2e]/60 p-3 text-xs leading-5 text-slate-400">For your security, never send passwords, SMTP credentials, card details or one-time codes.</p>
      </aside>
      <section class="contact-form-card" aria-labelledby="contact-form-title">
        <div class="contact-form-header"><div class="contact-form-header-copy"><span class="contact-form-badge">Contact</span><h2 id="contact-form-title" class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Send us a message</h2><p class="mt-2 text-sm leading-6 text-slate-500">Tell us what you need help with. Our team will get back to you by email.</p></div></div>
        <form method="post" action="./" class="relative" autocomplete="on">
          <?= form_action('contact.submit', url('contact')) ?>
          <label class="contact-honeypot" aria-hidden="true">Leave this field empty<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
          <div class="contact-fields">
            <label class="contact-field"><span class="text-xs font-medium text-slate-500">Your name</span><input class="contact-input" type="text" name="name" maxlength="120" autocomplete="name" required></label>
            <label class="contact-field"><span class="text-xs font-medium text-slate-500">Email address</span><input class="contact-input" type="email" name="email" maxlength="191" autocomplete="email" required></label>
            <label class="contact-field contact-field-wide"><span class="text-xs font-medium text-slate-500">What can we help with?</span><select class="contact-input" name="topic" required><option>General question</option><option>Account access</option><option>Technical support</option><option>Privacy request</option><option>Billing or payment</option></select></label>
            <label class="contact-field contact-field-wide"><span class="text-xs font-medium text-slate-500">Message</span><textarea class="contact-input" name="message" rows="6" maxlength="10000" required></textarea></label>
          </div>
          <div class="mt-6 flex flex-col items-start justify-end gap-4 border-t border-slate-200/10 pt-5 sm:flex-row sm:items-center"><button type="submit" class="contact-submit">Send message <?= icon('ArrowRight', 'h-4 w-4') ?></button></div>
        </form>
      </section>
      </div>
    <?php endif; ?>
    <?php if (empty($contactFormEnabled)): ?>
    <div class="legal-layout">
      <aside class="legal-index">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">On this page</p>
        <nav class="mt-3" aria-label="On this page"><ol class="space-y-1">
          <?php foreach ($legalSections as $index => $section): $sectionId = 'legal-section-' . ($index + 1); ?>
            <li><a href="#<?= e($sectionId) ?>" class="block rounded-lg px-2.5 py-2 text-xs leading-5 text-slate-500 transition hover:bg-page hover:text-slate-800"><span class="mr-2 font-mono-code text-[10px] text-slate-400"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><?= e($section['heading']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="#legal-contact" class="block rounded-lg px-2.5 py-2 text-xs leading-5 text-slate-500 transition hover:bg-page hover:text-slate-800">Contact</a></li>
        </ol></nav>
      </aside>
      <div class="legal-entries">
      <?php foreach ($legalSections as $index => $section): $sectionId = 'legal-section-' . ($index + 1); ?>
        <section id="<?= e($sectionId) ?>" class="legal-entry">
          <div class="flex items-start gap-4"><span class="legal-entry-number flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-mono-code text-xs"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div class="min-w-0 flex-1">
          <h2 class="pt-1 text-lg font-semibold text-slate-900 sm:text-xl"><?= e($section['heading']) ?></h2>
          <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
            <p class="mt-3 text-sm leading-7 text-slate-600"><?= e($paragraph) ?></p>
          <?php endforeach; ?>
          <?php if (!empty($section['items'])): ?>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-slate-600">
              <?php foreach ($section['items'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          </div></div>
        </section>
      <?php endforeach; ?>
      <section id="legal-contact" class="legal-contact">
      <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#34506d] text-[#d6e4f1]">@</span><h2 class="text-lg font-semibold text-slate-900">Contact</h2></div>
      <?php if ($legalEntity): ?><p class="mt-3 text-sm leading-7 text-slate-600">Service operator: <?= e($legalEntity) ?></p><?php endif; ?>
      <?php if ($legalAddress): ?><p class="mt-1 text-sm leading-7 text-slate-600">Postal address: <?= e($legalAddress) ?></p><?php endif; ?>
      <?php if ($grievanceOfficer): ?><p class="mt-1 text-sm leading-7 text-slate-600">Grievance contact: <?= e($grievanceOfficer) ?></p><?php endif; ?>
      <?php if ($legalContact): ?>
        <p class="mt-3 text-sm leading-7 text-slate-600">For questions, privacy requests, billing issues or legal notices, email <a class="font-medium text-brand-700 underline" href="mailto:<?= e($legalContact) ?>"><?= e($legalContact) ?></a>.</p>
      <?php else: ?>
        <p class="mt-3 text-sm leading-7 text-slate-600">For publication, configure <code class="rounded bg-slate-100 px-1.5 py-0.5">LEGAL_CONTACT_EMAIL</code> in the server environment so visitors have a direct contact for privacy, billing and legal requests.</p>
      <?php endif; ?>
      <?php if (!$legalEntity || !$legalAddress || !$grievanceOfficer): ?><p class="mt-2 text-xs leading-6 text-slate-400">Before launch, configure <code>LEGAL_ENTITY_NAME</code>, <code>LEGAL_ADDRESS</code> and <code>LEGAL_GRIEVANCE_OFFICER</code> in the server environment with the service operator's correct legal details.</p><?php endif; ?>
      </section>
      </div>
    </div>
    <?php endif; ?>
  </main>
  <footer class="border-t border-slate-200/70">
    <div class="mx-auto grid gap-8 px-4 py-12 text-sm sm:grid-cols-2 <?= $legalPagesEnabled ? 'md:grid-cols-5' : 'md:grid-cols-4' ?> sm:px-6" style="width: min(1400px, calc(100% - 80px)); max-width: 100%">
      <div class="sm:col-span-2">
        <?php partial('app_logo', ['size' => 'md', 'showTagline' => false]); ?>
        <p class="mt-3 max-w-xs text-slate-500">Smart, staggered email campaigns that reach the inbox, from your own email account.</p>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[#8395AE]">Product</p>
        <ul class="mt-3 space-y-2">
          <li><a href="<?= e(url('home')) ?>#features" class="text-slate-500 transition hover:text-slate-900">Features</a></li>
          <li><a href="<?= e(url('home')) ?>#how" class="text-slate-500 transition hover:text-slate-900">How it works</a></li>
          <li><a href="<?= e(url('home')) ?>#why" class="text-slate-500 transition hover:text-slate-900">Why MailDart</a></li>
          <?php if ($footerPlans): ?><li><a href="<?= e(url('pricing')) ?>" class="text-slate-500 transition hover:text-slate-900">Pricing</a></li><?php endif; ?>
          <li><a href="<?= e(url('home')) ?>#faq" class="text-slate-500 transition hover:text-slate-900">FAQ</a></li>
        </ul>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[#8395AE]">Get started</p>
        <ul class="mt-3 space-y-2">
          <?php if ($isLoggedIn): ?>
            <li><a href="<?= e(url('overview')) ?>" class="text-slate-500 transition hover:text-slate-900">Open Dashboard</a></li>
            <li><a href="<?= e(url('guide')) ?>" class="text-slate-500 transition hover:text-slate-900">Guide</a></li>
          <?php else: ?>
            <li><a href="<?= e(url('register')) ?>" class="text-slate-500 transition hover:text-slate-900">Sign up free</a></li>
            <li><a href="<?= e(url('login')) ?>" class="text-slate-500 transition hover:text-slate-900">Log in</a></li>
            <li><a href="<?= e(url('guide')) ?>" class="text-slate-500 transition hover:text-slate-900">Guide</a></li>
          <?php endif; ?>
          <li><a href="<?= e(url('contact')) ?>" class="text-slate-500 transition hover:text-slate-900">Contact</a></li>
        </ul>
      </div>
      <?php if ($legalPagesEnabled): ?>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[#8395AE]">Legal</p>
        <ul class="mt-3 space-y-2">
          <li><a href="<?= e(url('privacy')) ?>" class="text-slate-500 transition hover:text-slate-900">Privacy Policy</a></li>
          <li><a href="<?= e(url('terms')) ?>" class="text-slate-500 transition hover:text-slate-900">Terms of Service</a></li>
          <li><a href="<?= e(url('refunds')) ?>" class="text-slate-500 transition hover:text-slate-900">Refunds &amp; Cancellation</a></li>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <div class="border-t border-slate-200/70">
      <div class="mx-auto flex flex-col items-center justify-between gap-3 px-4 py-5 text-xs text-slate-400 sm:flex-row sm:px-6" style="width: min(1400px, calc(100% - 80px)); max-width: 100%">
        <span>&copy; <?= e(date('Y')) ?> MailDart Pro. All rights reserved.</span>
        <a href="<?= e(url('sitemap.xml')) ?>" class="rounded-md transition hover:text-slate-200">Sitemap</a>
      </div>
    </div>
  </footer>
</div>
