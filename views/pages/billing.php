<?php
/**
 * Plans & Billing: the user's current plan, the plans an admin has made live, and payment history.
 * ?order=<razorpay order id> (set by billing.checkout) opens Razorpay Checkout for that pending order.
 */
$currentPlan = user_current_plan($userRow);
if (!$currentPlan && ($free = plan_default_free())) {
    $currentPlan = $free + ['expires_at' => null]; // no active plan (or it expired) = the free plan's limits apply
}
$plans = plans_active();
$plansById = [];
foreach ($plans as $listedPlan) $plansById[(int) $listedPlan['id']] = $listedPlan;
$periodCounts = ['month' => 0, 'year' => 0, 'all' => 0];
foreach ($plans as $activePlan) {
    $bucket = (int) $activePlan['price_paise'] === 0 || $activePlan['billing_period'] === 'lifetime' ? 'all' : $activePlan['billing_period'];
    if (isset($periodCounts[$bucket])) $periodCounts[$bucket]++;
}
$defaultPeriod = $periodCounts['month'] > 0 || $periodCounts['all'] > 0 ? 'month' : 'year';
$initialVisibleCount = $periodCounts[$defaultPeriod] + $periodCounts['all'];
$gridLayout = $initialVisibleCount >= 4 ? 'rows' : 'cards';
$gridColumns = min(4, max(1, $initialVisibleCount));
$gridMaxWidth = $initialVisibleCount <= 1 ? '32rem' : ($initialVisibleCount === 2 ? '56rem' : '72rem');
$history = payments_for_user((int) $userRow['id']);

$pendingOrder = null;
$orderId = (string) ($_GET['order'] ?? '');
if ($orderId !== '' && razorpay_enabled()) {
    $pay = payment_find_by_order($orderId);
    if ($pay && (int) $pay['user_id'] === (int) $userRow['id'] && $pay['status'] === 'created') {
        $pendingOrder = [
            'key' => env_secret('RAZORPAY_KEY_ID'),
            'amount' => (int) $pay['amount_paise'],
            'currency' => $pay['currency'],
            'name' => env('APP_NAME', 'MailDart Pro'),
            'description' => $pay['plan_name'] . ' plan',
            'order_id' => $pay['razorpay_order_id'],
            'prefill' => ['name' => $userRow['display_name'], 'email' => $userRow['email']],
            'theme' => ['color' => '#557392'],
        ];
    }
}
$pageScripts[] = 'js/billing.js';
?>
<style>
  .billing-period-switch{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:24px 0 18px;padding:16px 20px;border:1px solid #1f4060;border-radius:16px;background:linear-gradient(120deg,#112a46,#0d2239)}
  .billing-period-switch [role="group"]{display:inline-flex;flex:none;gap:4px;padding:4px;border:1px solid #34506d;border-radius:12px;background:#08192a}
  .billing-period-switch [data-plan-period]{min-height:40px;padding:0 18px;border:0;border-radius:8px;cursor:pointer;color:#9aacc0;background:transparent}
  .billing-period-switch [data-plan-period][aria-pressed="true"]{color:#0a1b2e;background:#e1e8f0;box-shadow:0 2px 6px rgba(0,0,0,.2)}
  [data-billing-plans]{width:100%;margin-inline:auto;gap:16px}
  [data-billing-plans][data-layout="rows"]{grid-template-columns:minmax(0,1fr)!important;max-width:72rem!important}
  [data-billing-plans][data-layout="rows"] [data-billing-plan]{display:grid;grid-template-columns:minmax(190px,.9fr) minmax(0,2fr) minmax(150px,.75fr);align-items:center;gap:14px 24px;padding:22px 24px}
  [data-billing-plans][data-layout="rows"] .billing-plan-summary{grid-column:1;grid-row:1;min-width:0}
  [data-billing-plans][data-layout="rows"] .billing-plan-summary h3{font-size:20px}
  [data-billing-plans][data-layout="rows"] [data-billing-features]{grid-column:2;grid-row:1;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 14px;margin:0}
  [data-billing-plans][data-layout="rows"] [data-billing-features]>li{gap:7px;line-height:1.45}
  [data-billing-plans][data-layout="rows"] [data-billing-action]{grid-column:3;grid-row:1;margin:0;width:100%}
  [data-billing-plans][data-layout="rows"] [data-billing-action] form{margin-top:0}
  @media(max-width:900px){[data-billing-plans][data-layout="rows"] [data-billing-plan]{grid-template-columns:minmax(0,1fr) minmax(160px,.7fr);gap:16px}[data-billing-plans][data-layout="rows"] .billing-plan-summary{grid-column:1;grid-row:1}[data-billing-plans][data-layout="rows"] [data-billing-features]{grid-column:1/-1;grid-row:2;grid-template-columns:repeat(2,minmax(0,1fr))}[data-billing-plans][data-layout="rows"] [data-billing-action]{grid-column:2;grid-row:1}}
  @media(max-width:640px){.billing-period-switch{flex-direction:column;align-items:stretch;text-align:center;padding:14px}.billing-period-switch [role="group"]{width:100%}.billing-period-switch [data-plan-period]{flex:1;padding:0 10px}[data-billing-plans]{grid-template-columns:minmax(0,1fr)!important}[data-billing-plans][data-layout="rows"] [data-billing-plan]{grid-template-columns:minmax(0,1fr);gap:16px;padding:20px}[data-billing-plans][data-layout="rows"] .billing-plan-summary,[data-billing-plans][data-layout="rows"] [data-billing-features],[data-billing-plans][data-layout="rows"] [data-billing-action]{grid-column:1;grid-row:auto}[data-billing-plans][data-layout="rows"] [data-billing-features]{grid-template-columns:minmax(0,1fr)}}
</style>
<!-- Current plan -->
<div class="relative bg-surface ring-1 ring-slate-200/80 rounded-2xl shadow-card overflow-hidden">
  <div class="absolute inset-y-0 right-0 w-1/2 bg-dots [mask-image:linear-gradient(to_left,black,transparent)] pointer-events-none"></div>
  <div class="relative p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
      <div class="w-12 h-12 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
        <?= icon('Award', 'w-5 h-5') ?>
      </div>
      <div>
        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Current plan</p>
        <?php if ($currentPlan): ?>
          <p class="text-lg font-semibold text-slate-900"><?= e($currentPlan['name']) ?></p>
          <p class="text-sm text-slate-500">
            <?= $currentPlan['expires_at'] ? 'Active until ' . e(format_datetime($currentPlan['expires_at'])) : 'Active · never expires' ?>
          </p>
        <?php else: ?>
          <p class="text-lg font-semibold text-slate-900">No active plan</p>
          <p class="text-sm text-slate-500">Choose a plan below to get started.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Available plans -->
<?php if (!$plans): ?>
  <div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card px-6 py-12 text-center text-sm text-slate-500">
    No plans are available right now. Please check back soon.
  </div>
<?php else: ?>
  <div class="billing-period-switch">
    <div><p class="text-sm font-semibold text-slate-100">Choose billing cycle</p><p data-billing-period-hint class="mt-1 text-xs text-slate-400"><?= $initialVisibleCount ?> plans available</p></div>
    <div role="group" aria-label="Choose billing period">
      <button type="button" data-plan-period="month" aria-pressed="<?= $defaultPeriod === 'month' ? 'true' : 'false' ?>">Monthly</button>
      <button type="button" data-plan-period="year" aria-pressed="<?= $defaultPeriod === 'year' ? 'true' : 'false' ?>">Yearly · 12 months</button>
    </div>
  </div>
  <div data-billing-plans data-layout="<?= e($gridLayout) ?>" style="max-width:<?= e($gridMaxWidth) ?>;grid-template-columns:repeat(<?= $gridColumns ?>,minmax(0,1fr))" class="grid">
    <?php foreach ($plans as $p):
        $isCurrent = $currentPlan && (int) $currentPlan['id'] === (int) $p['id'];
        $free = (int) $p['price_paise'] === 0;
        $bucket = $free || $p['billing_period'] === 'lifetime' ? 'all' : $p['billing_period'];
        $shown = $bucket === 'all' || $bucket === $defaultPeriod;
        $buttonLabel = $isCurrent ? ($free || $p['billing_period'] === 'lifetime' ? 'Current plan' : 'Renew') : ($free ? 'Choose free plan' : 'Buy now');
        $disabled = $isCurrent && ($free || $p['billing_period'] === 'lifetime');
        ?>
      <div data-billing-plan data-period="<?= e($bucket) ?>" style="<?= $shown ? '' : 'display:none' ?>" class="relative flex flex-col bg-surface rounded-2xl shadow-card p-5 sm:p-6 <?= $p['is_featured'] ? 'ring-2 ring-brand-500' : 'ring-1 ring-slate-200/80' ?>">
        <?php if ($p['is_featured']): ?>
          <span class="absolute -top-2.5 left-5 px-2 py-0.5 rounded-full bg-brand-600 text-white text-[10px] font-semibold uppercase tracking-wide">Popular</span>
        <?php endif; ?>
        <div class="billing-plan-summary"><h3 class="text-base font-semibold text-slate-900"><?= e($p['name']) ?></h3>
        <?php if ($p['description'] !== ''): ?>
          <p class="mt-1 text-sm text-slate-500"><?= e($p['description']) ?></p>
        <?php endif; ?>
        <p class="mt-4 flex items-baseline gap-1.5">
          <span class="text-3xl font-bold tracking-tight text-slate-900 tabular-nums"><?= e(plan_price_label($p)) ?></span>
          <?php if (!$free): ?><span class="text-sm text-slate-500"><?= e(plan_period_label($p)) ?></span><?php endif; ?>
        </p>
        <?php if (!empty($p['yearly_source_id']) && isset($plansById[(int) $p['yearly_source_id']])): ?>
          <span class="mt-2 inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold tracking-wide text-emerald-700">10% OFF</span>
        <?php endif; ?>
        </div>
        <ul data-billing-features class="mt-5 space-y-2 flex-1">
          <?php foreach (plan_card_lines($p) as $line): ?>
            <li class="flex items-start gap-2 text-sm <?= $line['included'] ? 'text-slate-700' : 'text-slate-400' ?>">
              <?= $line['included'] ? icon('Check', 'w-4 h-4 mt-0.5 shrink-0 text-brand-600') : icon('X', 'w-4 h-4 mt-0.5 shrink-0 text-slate-300') ?>
              <span class="<?= $line['included'] ? '' : 'line-through decoration-slate-300' ?>"><?= e($line['text']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($disabled): ?>
          <span data-billing-action class="mt-6 inline-flex items-center justify-center gap-1.5 h-10 px-4 text-sm font-semibold text-slate-500 bg-slate-100 rounded-lg">
            <?= icon('CheckCircle2', 'w-4 h-4') ?> Current plan
          </span>
        <?php else: ?>
          <div data-billing-action>
          <?= post_button(
              'billing.checkout',
              ['plan_id' => $p['id']],
              e($buttonLabel),
              'w-full inline-flex items-center justify-center h-10 px-4 text-sm font-semibold rounded-lg transition ' . ($p['is_featured'] ? 'text-white bg-brand-600 hover:bg-brand-500 shadow-button' : 'text-slate-800 bg-surface ring-1 ring-inset ring-slate-300 hover:bg-slate-50'),
              ['form_class' => 'mt-6']
          ) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="text-xs text-slate-500 flex items-center gap-1.5">
    <?= icon('Lock', 'w-3.5 h-3.5') ?> Payments are processed securely by Razorpay (UPI, cards, net banking, wallets).
  </p>
<?php endif; ?>

<script>
  (function () {
    var switches = document.querySelectorAll('[data-plan-period]');
    var cards = document.querySelectorAll('[data-billing-plan]');
    var grid = document.querySelector('[data-billing-plans]');
    var hint = document.querySelector('[data-billing-period-hint]');
    if (!switches.length || !cards.length || !grid) return;
    function selectPeriod(period) {
      var visibleCount = 0;
      cards.forEach(function (card) {
        var visible = card.dataset.period === period || card.dataset.period === 'all';
        card.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
      });
      var rows = visibleCount >= 4;
      var columns = Math.min(4, Math.max(1, visibleCount));
      grid.dataset.layout = rows ? 'rows' : 'cards';
      grid.style.maxWidth = rows ? '72rem' : (visibleCount <= 1 ? '32rem' : (visibleCount === 2 ? '56rem' : '72rem'));
      grid.style.gridTemplateColumns = rows ? 'minmax(0,1fr)' : 'repeat(' + columns + ',minmax(0,1fr))';
      if (hint) hint.textContent = visibleCount + ' plans available';
      switches.forEach(function (button) { button.setAttribute('aria-pressed', button.dataset.planPeriod === period ? 'true' : 'false'); });
    }
    switches.forEach(function (button) { button.addEventListener('click', function () { selectPeriod(button.dataset.planPeriod); }); });
  })();
</script>

<!-- Payment history -->
<?php if ($history): ?>
  <div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card overflow-hidden">
    <div class="px-5 sm:px-6 py-4 border-b border-slate-200/80">
      <h2 class="text-base font-semibold tracking-tight text-slate-900">Payment history</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-[11px] uppercase tracking-[0.06em] text-slate-500">
          <tr>
            <th class="text-left font-semibold px-5 sm:px-6 py-3">Date</th>
            <th class="text-left font-semibold px-4 py-3">Plan</th>
            <th class="text-left font-semibold px-4 py-3">Amount</th>
            <th class="text-left font-semibold px-4 py-3">Status</th>
            <th class="text-left font-semibold px-5 sm:px-6 py-3 hidden md:table-cell">Payment ID</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($history as $pay): ?>
            <tr>
              <td class="px-5 sm:px-6 py-3 text-xs text-slate-500"><?= e(format_datetime($pay['paid_at'] ?? $pay['created_at'])) ?></td>
              <td class="px-4 py-3 text-slate-700"><?= e($pay['plan_name']) ?></td>
              <td class="px-4 py-3 font-semibold tabular-nums"><?= e(money_label((int) $pay['amount_paise'], $pay['currency'])) ?></td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $pay['status'] === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-600' ?>"><?= e(ucfirst($pay['status'])) ?></span>
              </td>
              <td class="px-5 sm:px-6 py-3 font-mono text-xs text-slate-500 hidden md:table-cell"><?= e($pay['razorpay_payment_id'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php if ($pendingOrder): ?>
  <?php /* Opened automatically by assets/js/billing.js; Razorpay's success handler submits #razorpay-verify */ ?>
  <script type="application/json" id="razorpay-order"><?= json_for_script($pendingOrder) ?></script>
  <form method="post" action="./" id="razorpay-verify" hidden>
    <?= form_action('billing.verify', url('billing')) ?>
    <input type="hidden" name="razorpay_order_id">
    <input type="hidden" name="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature">
  </form>
  <div class="p-4 bg-brand-50 border border-brand-200 rounded-xl text-sm text-brand-800 flex flex-wrap items-center justify-between gap-3">
    <span>Complete your payment of <strong><?= e(money_label($pendingOrder['amount'], $pendingOrder['currency'])) ?></strong> in the Razorpay window.</span>
    <button type="button" data-razorpay-open class="inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
      <?= icon('Lock', 'w-3.5 h-3.5') ?> Pay now
    </button>
  </div>
  <script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
<?php endif; ?>
