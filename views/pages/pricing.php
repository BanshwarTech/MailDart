<?php
/** Public pricing route; index.php returns a 404 unless at least one active plan is available. */
$plans = plans_active();
$isLoggedIn = $currentUser !== null;
$planHref = $isLoggedIn ? url('billing') : url('register', ['next' => 'billing']);
$card = 'rounded-2xl bg-surface border border-slate-200/80 shadow-card';
$primaryBtn = 'inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-white rounded-xl transition';
$secondaryBtn = 'inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-medium text-slate-800 bg-white/[0.04] hover:bg-white/[0.08] ring-1 ring-inset ring-slate-300 rounded-xl transition';
$gradientText = 'bg-gradient-to-b from-[#F2F6F8] via-[#C4D2E1] to-[#7A8CA6] bg-clip-text text-transparent';
?>
<div class="min-h-screen bg-page text-slate-900 overflow-x-clip">
  <header class="sticky top-0 z-40 border-b border-slate-200/60 bg-page/70 backdrop-blur-xl">
    <div class="mx-auto flex h-16 items-center justify-between gap-4 px-4 sm:px-6" style="width: min(1400px, calc(100% - 80px)); max-width: 100%">
      <a href="<?= e(url('home')) ?>" aria-label="MailDart home"><?php partial('app_logo', ['size' => 'md', 'showTagline' => false]); ?></a>
      <nav class="hidden items-center gap-1 text-sm md:flex" aria-label="Main">
        <a href="<?= e(url('home')) ?>#features" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">Features</a>
        <a href="<?= e(url('home')) ?>#how" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">How it works</a>
        <a href="<?= e(url('home')) ?>#why" class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900">Why MailDart</a>
        <a href="<?= e(url('pricing')) ?>" aria-current="page" class="rounded-lg px-3 py-2 text-slate-900 transition">Pricing</a>
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
  <main id="main">
    <section class="relative overflow-hidden border-b border-slate-200/50">
      <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute inset-0 [background-image:linear-gradient(rgb(52_80_109/0.25)_1px,transparent_1px),linear-gradient(90deg,rgb(52_80_109/0.25)_1px,transparent_1px)] [background-size:56px_56px] [mask-image:radial-gradient(ellipse_70%_70%_at_50%_0%,black,transparent)]"></div>
        <div class="absolute -top-48 left-1/2 h-[620px] w-[1100px] -translate-x-1/2 rounded-full bg-[radial-gradient(closest-side,rgb(85_115_146/0.45),transparent)]"></div>
      </div>
      <div class="relative mx-auto max-w-6xl px-4 pb-14 pt-16 text-center sm:px-6 md:pb-20 md:pt-24">
        <span class="inline-flex items-center gap-2 rounded-full bg-surface/80 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-300">
          <?= icon('Sparkles', 'h-3.5 w-3.5 text-[#C4D2E1]') ?> MailDart plans
        </span>
        <h1 class="mx-auto mt-6 max-w-4xl text-4xl font-semibold leading-[1.05] tracking-[-0.03em] text-[#F2F6F8] sm:text-5xl md:text-7xl">Choose a plan for <span class="<?= $gradientText ?>">your next campaign.</span></h1>
        <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-500 md:text-lg">Compare currently available features and limits. Your selected plan, price and billing period are confirmed before checkout.</p>
      </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 md:py-20">
    <div class="mx-auto grid gap-4 sm:grid-cols-2 lg:grid-cols-<?= min(4, max(2, count($plans))) ?> <?= count($plans) < 3 ? 'max-w-4xl' : '' ?>">
      <?php foreach ($plans as $plan): $free = (int) $plan['price_paise'] === 0; ?>
        <article class="relative flex flex-col <?= $card ?> p-6 <?= $plan['is_featured'] ? 'ring-2 ring-[#C4D2E1]' : '' ?>">
          <?php if ($plan['is_featured']): ?><span class="absolute -top-2.5 left-6 rounded-full bg-[#E1E8F0] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#0A1B2E]">Most popular</span><?php endif; ?>
          <h2 class="text-base font-semibold text-slate-900"><?= e($plan['name']) ?></h2>
          <?php if ($plan['description'] !== ''): ?><p class="mt-1 text-sm text-slate-500"><?= e($plan['description']) ?></p><?php endif; ?>
          <p class="mt-5 flex items-baseline gap-1.5"><span class="text-4xl font-semibold tracking-tight text-slate-900 tabular-nums"><?= e(plan_price_label($plan)) ?></span><?php if (!$free): ?><span class="text-sm text-slate-500"><?= e(plan_period_label($plan)) ?></span><?php endif; ?></p>
          <ul class="mt-6 flex-1 space-y-2.5">
            <?php foreach (plan_card_lines($plan) as $line): ?>
              <li class="flex items-start gap-2 text-sm <?= $line['included'] ? 'text-slate-600' : 'text-slate-400' ?>">
                <?= $line['included'] ? icon('Check', 'w-4 h-4 mt-0.5 shrink-0 text-slate-500') : icon('X', 'w-4 h-4 mt-0.5 shrink-0 text-slate-300') ?>
                <span class="<?= $line['included'] ? '' : 'line-through decoration-slate-300' ?>"><?= e($line['text']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= e($planHref) ?>" class="mt-8 <?= $plan['is_featured'] ? $primaryBtn : $secondaryBtn ?>"><?= $free ? 'Start free' : 'Get ' . e($plan['name']) ?></a>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="mt-8 text-center text-sm text-slate-500">Payments are processed securely by Razorpay. Plans do not automatically renew; see our <a href="<?= e(url('refunds')) ?>" class="underline hover:text-slate-900">refund and cancellation policy</a>.</p>
    </section>
  </main>
  <footer class="border-t border-slate-200/70">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-7 text-xs text-slate-400 sm:flex-row sm:px-6">
      <span>© <?= e(date('Y')) ?> MailDart Pro. All rights reserved.</span>
      <nav class="flex flex-wrap gap-4" aria-label="Legal">
        <a class="hover:text-slate-700" href="<?= e(url('contact')) ?>">Contact</a>
        <a class="hover:text-slate-700" href="<?= e(url('privacy')) ?>">Privacy</a>
        <a class="hover:text-slate-700" href="<?= e(url('terms')) ?>">Terms</a>
        <a class="hover:text-slate-700" href="<?= e(url('refunds')) ?>">Refunds</a>
      </nav>
      <a href="<?= e(url('home')) ?>" class="hover:text-slate-700">Back to home</a>
      <a href="<?= e(url('sitemap.xml')) ?>" class="hover:text-slate-700">Sitemap</a>
    </div>
  </footer>
</div>
