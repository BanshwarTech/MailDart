<?php
/** Shared marketing footer. Vars: $plans, $isLoggedIn, $legalPagesEnabled. */
$plans = $plans ?? [];
$isLoggedIn = $isLoggedIn ?? false;
$legalPagesEnabled = $legalPagesEnabled ?? seo_pricing_is_public();
?>
<footer class="border-t border-slate-200/70">
  <div class="mx-auto px-4 sm:px-6 py-12 grid sm:grid-cols-2 <?= $legalPagesEnabled ? 'md:grid-cols-5' : 'md:grid-cols-4' ?> gap-8 text-sm" style="width: min(1400px, calc(100% - 80px)); max-width: 100%">
    <div class="sm:col-span-2">
      <?php partial('app_logo', ['size' => 'md', 'showTagline' => false]); ?>
      <p class="mt-3 text-slate-500 max-w-xs">Smart, staggered email campaigns that reach the inbox, from your own email account.</p>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-[#8395AE]">Product</p>
      <ul class="mt-3 space-y-2">
        <li><a href="<?= e(url('home')) ?>#features" class="text-slate-500 hover:text-slate-900 transition">Features</a></li>
        <li><a href="<?= e(url('home')) ?>#how" class="text-slate-500 hover:text-slate-900 transition">How it works</a></li>
        <li><a href="<?= e(url('home')) ?>#why" class="text-slate-500 hover:text-slate-900 transition">Why MailDart</a></li>
        <?php if ($plans): ?><li><a href="<?= e(url('pricing')) ?>" class="text-slate-500 hover:text-slate-900 transition">Pricing</a></li><?php endif; ?>
        <li><a href="<?= e(url('home')) ?>#faq" class="text-slate-500 hover:text-slate-900 transition">FAQ</a></li>
      </ul>
    </div>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-[#8395AE]">Get started</p>
      <ul class="mt-3 space-y-2">
        <?php if ($isLoggedIn): ?>
          <li><a href="<?= e(url('overview')) ?>" class="text-slate-500 hover:text-slate-900 transition">Open Dashboard</a></li>
          <li><a href="<?= e(url('guide')) ?>" class="text-slate-500 hover:text-slate-900 transition">Guide</a></li>
        <?php else: ?>
          <li><a href="<?= e(url('register')) ?>" class="text-slate-500 hover:text-slate-900 transition">Sign up free</a></li>
          <li><a href="<?= e(url('login')) ?>" class="text-slate-500 hover:text-slate-900 transition">Log in</a></li>
          <li><a href="<?= e(url('guide')) ?>" class="text-slate-500 hover:text-slate-900 transition">Guide</a></li>
        <?php endif; ?>
        <li><a href="<?= e(url('contact')) ?>" class="text-slate-500 hover:text-slate-900 transition">Contact</a></li>
      </ul>
    </div>
    <?php if ($legalPagesEnabled): ?>
    <div>
      <p class="text-xs font-semibold uppercase tracking-wider text-[#8395AE]">Legal</p>
      <ul class="mt-3 space-y-2">
        <li><a href="<?= e(url('privacy')) ?>" class="text-slate-500 hover:text-slate-900 transition">Privacy Policy</a></li>
        <li><a href="<?= e(url('terms')) ?>" class="text-slate-500 hover:text-slate-900 transition">Terms of Service</a></li>
        <li><a href="<?= e(url('refunds')) ?>" class="text-slate-500 hover:text-slate-900 transition">Refunds &amp; Cancellation</a></li>
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
