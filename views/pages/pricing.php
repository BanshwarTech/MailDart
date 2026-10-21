<?php
/** Public pricing route; index.php returns a 404 unless at least one active plan is available. */
$plans = plans_active();
$plansById = [];
foreach ($plans as $listedPlan) $plansById[(int) $listedPlan['id']] = $listedPlan;
$isLoggedIn = $currentUser !== null;
$periodCounts = ['month' => 0, 'year' => 0, 'all' => 0];
foreach ($plans as $activePlan) {
    $bucket = (int) $activePlan['price_paise'] === 0 || $activePlan['billing_period'] === 'lifetime' ? 'all' : $activePlan['billing_period'];
    if (isset($periodCounts[$bucket])) $periodCounts[$bucket]++;
}
$defaultPeriod = $periodCounts['month'] > 0 || $periodCounts['all'] > 0 ? 'month' : 'year';
$initialVisibleCount = $periodCounts[$defaultPeriod] + $periodCounts['all'];
$initialGridLayout = $initialVisibleCount >= 4 ? 'rows' : 'cards';
$initialGridColumns = min(4, max(1, $initialVisibleCount));
$gridMaxWidth = $initialVisibleCount <= 1 ? '28rem' : ($initialVisibleCount === 2 ? '48rem' : ($initialVisibleCount === 3 ? '68rem' : '72rem'));
$toolbarMaxWidth = '20rem';
$planHref = $isLoggedIn ? url('billing') : url('register', ['next' => 'billing']);
$card = 'rounded-2xl bg-surface border border-slate-200/80 shadow-card';
$primaryBtn = 'inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-white rounded-xl transition';
$secondaryBtn = 'inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-medium text-slate-800 bg-white/[0.04] hover:bg-white/[0.08] ring-1 ring-inset ring-slate-300 rounded-xl transition';
$gradientText = 'bg-gradient-to-b from-[#F2F6F8] via-[#C4D2E1] to-[#7A8CA6] bg-clip-text text-transparent';
?>
<style>
  .pricing-main{padding-bottom:20px}
  .pricing-hero{grid-template-columns:minmax(0,1fr) minmax(250px,300px)}
  [data-plan-toolbar]{grid-column:auto;width:100%;justify-self:end;margin:0}
  [data-plan-toolbar]>.pricing-period-panel{display:flex;flex-direction:column;align-items:stretch;gap:16px;padding:20px;border:1px solid rgba(85,115,146,.55);border-radius:18px;background:linear-gradient(145deg,rgba(22,50,79,.94),rgba(8,25,42,.88));box-shadow:0 16px 36px rgba(0,0,0,.18)}
  [data-plan-toolbar] [data-period-label]{color:#d6e4f1;font-size:14px;font-weight:700}
  [data-plan-toolbar] [data-period-hint]{margin-top:5px;color:#8fa8c2;font-size:11px}
  [data-plan-toolbar] [role="group"]{display:inline-flex;flex:none;gap:4px;padding:5px;border:1px solid rgba(85,115,146,.72);border-radius:999px;background:linear-gradient(135deg,#112a46,#08192a);box-shadow:0 10px 28px rgba(0,0,0,.2),inset 0 1px rgba(255,255,255,.04)}
  [data-plan-toolbar] [data-plan-period]{min-height:42px;padding:0 20px;border:0;border-radius:999px;cursor:pointer;transition:background .18s ease,color .18s ease,box-shadow .18s ease}
  [data-plan-toolbar] [data-plan-period][aria-pressed="true"]{background:#e1e8f0;color:#0a1b2e;box-shadow:0 2px 8px rgba(0,0,0,.22)}
  [data-plan-toolbar] [data-plan-period][aria-pressed="false"]{background:transparent;color:#9aacc0}
  [data-plan-grid]{width:100%;margin-right:auto;margin-left:auto;gap:16px}
  [data-plan-grid][data-layout="rows"]{grid-template-columns:minmax(0,1fr)!important;max-width:72rem!important}
  [data-plan-grid][data-layout="rows"] [data-plan-card]{display:grid!important;grid-template-areas:"summary features action"!important;grid-template-columns:minmax(220px,.9fr) minmax(0,2fr) minmax(180px,.75fr)!important;align-items:center;gap:14px 24px;padding:24px;text-align:left}
  [data-plan-grid] [data-plan-card][hidden]{display:none!important}
  [data-plan-grid][data-layout="rows"] .pricing-plan-summary{grid-area:summary!important;justify-self:stretch;min-width:0;text-align:left}
  [data-plan-grid][data-layout="rows"] .pricing-plan-summary h2{font-size:20px}
  [data-plan-grid][data-layout="rows"] .pricing-plan-summary [data-plan-description]{margin-top:6px}
  [data-plan-grid][data-layout="rows"] .pricing-plan-summary [data-plan-price]{margin-top:14px}
  [data-plan-grid][data-layout="rows"] [data-plan-features]{grid-area:features!important;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 14px;margin:0;text-align:left}
  [data-plan-grid][data-layout="rows"] [data-plan-features]>li{gap:7px;line-height:1.45}
  [data-plan-grid][data-layout="rows"] [data-plan-action]{grid-area:action!important;justify-self:stretch;margin:0;width:100%;text-align:center}
  .pricing-main{max-width:1120px;margin:0 auto;padding:40px 24px 20px}
  .pricing-hero{position:relative;display:grid;grid-template-columns:minmax(0,1fr) minmax(250px,300px);align-items:center;gap:28px;overflow:hidden;border:1px solid rgba(52,80,109,.8);border-radius:28px;padding:40px;background:radial-gradient(ellipse at 90% 0%,rgba(85,115,146,.30),transparent 42%),linear-gradient(135deg,#112a46,#0d2239);box-shadow:0 18px 50px rgba(0,0,0,.16)}
  .pricing-hero:before{content:"";position:absolute;inset:0;pointer-events:none;opacity:.3;background-image:linear-gradient(rgba(122,140,166,.16) 1px,transparent 1px),linear-gradient(90deg,rgba(122,140,166,.16) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(to bottom right,#000,transparent 75%)}
  .pricing-hero-content{position:relative;z-index:1;max-width:760px}
  .pricing-cover{position:relative;z-index:1;justify-self:end;width:170px;min-height:200px;padding:18px;border:1px solid rgba(143,180,214,.34);border-radius:14px;background:linear-gradient(145deg,rgba(225,232,240,.12),rgba(10,27,46,.75));box-shadow:10px 12px 0 -5px rgba(85,115,146,.24),0 18px 32px rgba(0,0,0,.2);transform:rotate(3deg)}
  .pricing-cover-mark{display:flex;width:42px;height:42px;align-items:center;justify-content:center;border-radius:12px;background:#557392;color:#f2f6f8;font-family:var(--font-mono);font-size:12px;font-weight:700;letter-spacing:.08em}
  .pricing-cover-lines{display:grid;gap:7px;margin-top:24px}.pricing-cover-lines i{display:block;height:4px;border-radius:99px;background:rgba(179,194,210,.38)}.pricing-cover-lines i:nth-child(2){width:78%}.pricing-cover-lines i:nth-child(3){width:55%}
  @media(max-width:760px){.pricing-main{padding:28px 16px 12px}.pricing-hero{grid-template-columns:minmax(0,1fr);padding:26px 22px;border-radius:22px}}
  @media(max-width:900px){[data-plan-grid][data-layout="rows"] [data-plan-card]{grid-template-areas:"summary action" "features features"!important;grid-template-columns:minmax(0,1fr) minmax(160px,.7fr)!important;gap:16px}[data-plan-grid][data-layout="rows"] [data-plan-features]{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(max-width:560px){[data-plan-toolbar]>.pricing-period-panel{gap:12px;padding:16px}[data-plan-toolbar] [role="group"]{width:100%}[data-plan-toolbar] [data-plan-period]{flex:1;padding:0 10px}[data-plan-grid]{grid-template-columns:minmax(0,1fr)!important}[data-plan-grid][data-layout="rows"] [data-plan-card]{grid-template-areas:"summary" "features" "action"!important;grid-template-columns:minmax(0,1fr)!important;gap:18px;padding:20px}[data-plan-grid][data-layout="rows"] [data-plan-features]{grid-template-columns:minmax(0,1fr)}}
</style>
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
    <section class="pricing-main">
      <div class="pricing-hero">
        <div class="pricing-hero-content">
          <p class="inline-flex items-center gap-2 rounded-full border border-slate-300/70 bg-page/50 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400"><?= icon('Sparkles', 'h-3.5 w-3.5 text-[#8fb4d6]') ?>MailDart Pro <span class="text-slate-500">/</span>Pricing</p>
          <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight text-[#F2F6F8] sm:text-5xl">Choose a plan for <span class="<?= $gradientText ?>">your next campaign.</span></h1>
          <p class="mt-5 max-w-2xl text-base leading-7 sm:text-lg sm:leading-8" style="color:#c3d1df">Compare currently available features and limits. Your selected plan, price and billing period are confirmed before checkout.</p>
        </div>
        <div data-plan-toolbar style="max-width:<?= e($toolbarMaxWidth) ?>">
          <div class="pricing-period-panel">
            <div><p data-period-label>Billing cycle</p><p data-period-hint><?= $initialVisibleCount ?> plans available &middot; Select a billing term</p></div>
            <div role="group" aria-label="Choose billing period">
              <button type="button" data-plan-period="month" aria-pressed="<?= $defaultPeriod === 'month' ? 'true' : 'false' ?>" class="text-sm font-semibold <?= $defaultPeriod === 'month' ? 'bg-[#E1E8F0] text-[#0A1B2E] shadow-sm' : 'text-slate-400 hover:text-white' ?>">Monthly</button>
              <button type="button" data-plan-period="year" aria-pressed="<?= $defaultPeriod === 'year' ? 'true' : 'false' ?>" class="text-sm font-semibold <?= $defaultPeriod === 'year' ? 'bg-[#E1E8F0] text-[#0A1B2E] shadow-sm' : 'text-slate-400 hover:text-white' ?>">Yearly <span class="ml-1 text-[10px] font-medium opacity-70">12 months</span></button>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 pt-0 sm:px-6" style="padding-bottom:clamp(44px,6vw,76px)">
    <div data-plan-grid data-layout="<?= e($initialGridLayout) ?>" style="max-width:<?= e($gridMaxWidth) ?>;grid-template-columns:repeat(<?= $initialGridColumns ?>,minmax(0,1fr))" class="mx-auto grid w-full justify-center gap-4 transition-[max-width] duration-300">
      <?php foreach ($plans as $plan): $free = (int) $plan['price_paise'] === 0; $bucket = $free || $plan['billing_period'] === 'lifetime' ? 'all' : $plan['billing_period']; $shown = $bucket === 'all' || $bucket === $defaultPeriod; ?>
        <article data-plan-card data-period="<?= e($bucket) ?>" <?= $shown ? '' : 'hidden' ?> class="relative flex flex-col <?= $card ?> p-6 <?= $plan['is_featured'] ? 'ring-2 ring-[#C4D2E1]' : '' ?>">
          <?php if ($plan['is_featured']): ?><span class="absolute -top-2.5 left-6 rounded-full bg-[#E1E8F0] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#0A1B2E]">Most popular</span><?php endif; ?>
          <div class="pricing-plan-summary">
            <h2 class="text-base font-semibold text-slate-900"><?= e($plan['name']) ?></h2>
            <?php if ($plan['description'] !== ''): ?><p data-plan-description class="mt-1 text-sm text-slate-500"><?= e($plan['description']) ?></p><?php endif; ?>
            <p data-plan-price class="mt-5 flex items-baseline gap-1.5"><span class="text-4xl font-semibold tracking-tight text-slate-900 tabular-nums"><?= e(plan_price_label($plan)) ?></span><?php if (!$free): ?><span class="text-sm text-slate-500"><?= e(plan_period_label($plan)) ?></span><?php endif; ?></p>
            <?php if (!empty($plan['yearly_source_id']) && isset($plansById[(int) $plan['yearly_source_id']])): ?>
              <span class="mt-2 inline-flex w-fit items-center rounded-full border border-emerald-300/30 bg-emerald-400/10 px-2.5 py-1 text-xs font-bold tracking-wide text-emerald-200">10% OFF</span>
            <?php endif; ?>
          </div>
          <ul data-plan-features class="mt-6 flex-1 space-y-2.5">
            <?php foreach (plan_card_lines($plan) as $line): ?>
              <li class="flex items-start gap-2 text-sm <?= $line['included'] ? 'text-slate-600' : 'text-slate-400' ?>">
                <?= $line['included'] ? icon('Check', 'w-4 h-4 mt-0.5 shrink-0 text-slate-500') : icon('X', 'w-4 h-4 mt-0.5 shrink-0 text-slate-300') ?>
                <span class="<?= $line['included'] ? '' : 'line-through decoration-slate-300' ?>"><?= e($line['text']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <a data-plan-action href="<?= e($planHref) ?>" class="mt-8 <?= $plan['is_featured'] ? $primaryBtn : $secondaryBtn ?>"><?= $free ? 'Start free' : 'Get ' . e($plan['name']) ?></a>
        </article>
      <?php endforeach; ?>
    </div>
    <p data-period-empty style="<?= $periodCounts[$defaultPeriod] + $periodCounts['all'] > 0 ? 'display:none' : '' ?>" class="mx-auto max-w-lg rounded-xl border border-[#34506d] bg-[#112a46] px-5 py-6 text-center text-sm leading-6 text-slate-400">No active <?= $defaultPeriod === 'month' ? 'monthly' : 'yearly' ?> plans are available right now. Please check back later.</p>
    <p class="mt-8 text-center text-sm text-slate-500">Payments are processed securely by Razorpay. Plans do not automatically renew; see our <a href="<?= e(url('refunds')) ?>" class="underline hover:text-slate-900">refund and cancellation policy</a>.</p>
    </section>
  </main>
  <?php partial('public_footer', ['plans' => $plans, 'isLoggedIn' => $isLoggedIn, 'legalPagesEnabled' => seo_pricing_is_public()]); ?>
</div>
<script>
  (function () {
    var switches = document.querySelectorAll('[data-plan-period]');
    var cards = document.querySelectorAll('[data-plan-card]');
    var grid = document.querySelector('[data-plan-grid]');
    var toolbar = document.querySelector('[data-plan-toolbar]');
    var periodHint = document.querySelector('[data-period-hint]');
    var emptyState = document.querySelector('[data-period-empty]');
    if (!switches.length || !cards.length) return;
    function selectPeriod(period) {
      var visibleCount = 0;
      cards.forEach(function (card) {
        var visible = card.dataset.period === period || card.dataset.period === 'all';
        card.hidden = !visible;
        if (visible) visibleCount++;
      });
      if (grid) {
        var columns = visibleCount === 4 ? 2 : Math.min(4, Math.max(1, visibleCount));
        var rowLayout = visibleCount >= 4;
        var maxWidth = visibleCount <= 1 ? '28rem' : (visibleCount === 2 ? '48rem' : (visibleCount === 3 ? '68rem' : (visibleCount === 4 ? '56rem' : '72rem')));
        grid.dataset.layout = rowLayout ? 'rows' : 'cards';
        grid.style.maxWidth = rowLayout ? '72rem' : maxWidth;
        grid.style.gridTemplateColumns = rowLayout ? 'minmax(0, 1fr)' : 'repeat(' + columns + ', minmax(0, 1fr))';
        if (toolbar) toolbar.style.maxWidth = '20rem';
      }
      if (periodHint) periodHint.textContent = visibleCount + ' plans available · Select a billing term';
      switches.forEach(function (button) {
        var active = button.dataset.planPeriod === period;
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.classList.toggle('bg-[#E1E8F0]', active);
        button.classList.toggle('text-[#0A1B2E]', active);
        button.classList.toggle('shadow-sm', active);
        button.classList.toggle('text-slate-400', !active);
      });
      if (emptyState) {
        emptyState.textContent = 'No active ' + (period === 'month' ? 'monthly' : 'yearly') + ' plans are available right now. Please check back later.';
        emptyState.style.display = visibleCount ? 'none' : 'block';
      }
    }
    switches.forEach(function (button) {
      button.addEventListener('click', function () { selectPeriod(button.dataset.planPeriod); });
    });
  })();
</script>
