<?php
/**
 * Admin: Manage Plans. Create / edit plans and switch them on or off for the website (landing pricing + Billing page).
 * ?edit=<id> loads a plan into the form. index.php only renders this page for admins.
 */
$plans = plans_all();
$editing = isset($_GET['edit']) ? plan_find((int) $_GET['edit']) : null;
$payments = payments_recent();

$counts = db()->query('SELECT plan_id, COUNT(*) AS n FROM users WHERE plan_id IS NOT NULL AND (plan_expires_at IS NULL OR plan_expires_at > NOW()) GROUP BY plan_id')
    ->fetchAll(PDO::FETCH_KEY_PAIR);

$f = [
    'name' => old('name', $editing['name'] ?? ''),
    'description' => old('description', $editing['description'] ?? ''),
    'price' => old('price', $editing ? rtrim(rtrim(number_format((int) $editing['price_paise'] / 100, 2, '.', ''), '0'), '.') : ''),
    'billing_period' => old('billing_period', $editing['billing_period'] ?? 'month'),
    'features' => old('features', $editing['features'] ?? ''),
    'sort_order' => old('sort_order', (string) ($editing['sort_order'] ?? '0')),
    'is_active' => (bool) old('is_active', $editing ? (bool) $editing['is_active'] : true),
    'is_featured' => (bool) old('is_featured', $editing ? (bool) $editing['is_featured'] : false),
    'max_recipients' => old('max_recipients', $editing ? (string) ($editing['max_recipients'] ?? '') : '1000'),
    'allow_excel' => (bool) old('allow_excel', $editing ? (bool) $editing['allow_excel'] : true),
    'allow_ai' => (bool) old('allow_ai', $editing ? (bool) $editing['allow_ai'] : true),
    'allow_export' => (bool) old('allow_export', $editing ? (bool) $editing['allow_export'] : true),
    'allow_custom_interval' => (bool) old('allow_custom_interval', $editing ? (bool) $editing['allow_custom_interval'] : true),
    'allow_test_email' => (bool) old('allow_test_email', $editing ? (bool) $editing['allow_test_email'] : true),
    'test_email_limit' => old('test_email_limit', $editing ? (string) ($editing['test_email_limit'] ?? '') : ''),
];

$inputClass = 'w-full h-10 px-3 bg-surface border border-slate-300 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10';
$labelClass = 'block text-xs font-medium text-slate-600 mb-1.5';
?>
<?php $plansOn = plans_enabled(); ?>
<!-- Master switch -->
<div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4" style="<?= $plansOn ? 'box-shadow:inset 0 0 0 2px #16a34a' : '' ?>">
  <div class="flex items-start gap-3">
    <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-white" style="background:<?= $plansOn ? '#16a34a' : '#3b4f66' ?>">
      <?= icon($plansOn ? 'CheckCircle2' : 'Lock', 'w-5 h-5') ?>
    </div>
    <div>
      <h2 class="text-base font-semibold tracking-tight text-slate-900">Plans system is <?= $plansOn ? 'ON' : 'OFF' ?></h2>
      <p class="text-sm text-slate-500">
        <?= $plansOn
            ? 'Plan limits are enforced. Users see Plans &amp; Billing, the website shows Pricing, and paid plans can be bought.'
            : 'Everyone gets every feature with no limits. Plans &amp; Billing and Pricing are hidden. Set up your plans below, then switch it on when you are ready.' ?>
      </p>
    </div>
  </div>
  <?= post_button(
      'admin.setPlansEnabled',
      ['enabled' => $plansOn ? '0' : '1'],
      '<span class="relative inline-flex shrink-0 rounded-full transition" style="width:52px;height:28px;background:' . ($plansOn ? '#16a34a' : '#3b4f66') . ';box-shadow:inset 0 0 0 1px rgb(255 255 255/.12)">'
          . '<span class="absolute rounded-full transition-all" style="top:4px;left:' . ($plansOn ? '28px' : '4px') . ';width:20px;height:20px;background:#fff;box-shadow:0 1px 3px rgb(0 0 0/.4)"></span></span>'
          . '<span class="text-sm font-semibold" style="color:' . ($plansOn ? '#4ade80' : '#94a3b8') . '">' . ($plansOn ? 'ON' : 'OFF') . '</span>',
      'inline-flex items-center gap-2.5 h-10 px-2 rounded-lg hover:bg-slate-50 transition shrink-0',
      ['role' => 'switch', 'aria-checked' => $plansOn ? 'true' : 'false', 'title' => $plansOn ? 'Turn the plans system off' : 'Turn the plans system on',
          'confirm' => $plansOn ? 'Turn plans OFF? Everyone will get every feature with no limits.' : 'Turn plans ON? Plan limits will apply to all non-admin users.']
  ) ?>
</div>

<?php if ($plansOn && !razorpay_enabled()): ?>
  <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800 flex items-start gap-3">
    <?= icon('AlertTriangle', 'w-5 h-5 shrink-0 mt-0.5') ?>
    <div>
      <p class="font-semibold">Razorpay is not connected yet</p>
      <p class="mt-0.5 text-amber-700">Add <code class="font-mono">RAZORPAY_KEY_ID</code>, <code class="font-mono">RAZORPAY_KEY_SECRET</code> and <code class="font-mono">RAZORPAY_WEBHOOK_SECRET</code> to <code class="font-mono">.env</code>. Until then only free plans can be chosen, and you can still assign plans manually from Users &amp; Roles.</p>
    </div>
  </div>
<?php endif; ?>

<div class="space-y-6">

  <!-- Plans list -->
  <div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card overflow-hidden">
    <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80">
      <h2 class="text-base font-semibold tracking-tight text-slate-900">All plans</h2>
      <p class="text-sm text-slate-500">Switch a plan on to show it on the website and let users buy it. Switching off never removes it from people who already have it.</p>
    </div>

    <?php if (!$plans): ?>
      <div class="px-6 py-12 text-center text-sm text-slate-500">No plans yet. Create your first one with the form.</div>
    <?php else: ?>
      <ul class="divide-y divide-slate-100">
        <?php foreach ($plans as $p): $live = (bool) $p['is_active']; ?>
          <li class="px-5 sm:px-6 py-4 flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[180px]">
              <div class="flex items-center gap-2">
                <p class="font-semibold text-slate-900"><?= e($p['name']) ?></p>
                <?php if ($p['is_featured']): ?>
                  <span class="px-1.5 py-0.5 rounded bg-brand-50 ring-1 ring-inset ring-brand-200 text-[10px] font-semibold text-brand-700">Popular</span>
                <?php endif; ?>
              </div>
              <p class="text-sm text-slate-600">
                <span class="font-semibold tabular-nums"><?= e(plan_price_label($p)) ?></span>
                <span class="text-slate-400"><?= (int) $p['price_paise'] > 0 ? e(plan_period_label($p)) : '' ?></span>
                <span class="text-slate-400"> · <?= (int) ($counts[$p['id']] ?? 0) ?> active user<?= (int) ($counts[$p['id']] ?? 0) === 1 ? '' : 's' ?></span>
              </p>
              <p class="mt-1 flex flex-wrap gap-1 text-[11px]">
                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600"><?= $p['max_recipients'] === null ? 'Unlimited' : number_format((int) $p['max_recipients']) ?> recipients</span>
                <?php if ($p['allow_test_email'] && $p['test_email_limit'] !== null): ?>
                  <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600"><?= (int) $p['test_email_limit'] ?> tests/mo</span>
                <?php endif; ?>
                <?php foreach (['allow_excel' => 'Excel', 'allow_ai' => 'AI', 'allow_custom_interval' => 'Gap', 'allow_test_email' => 'Test', 'allow_export' => 'Export'] as $key => $label): ?>
                  <span class="px-1.5 py-0.5 rounded <?= $p[$key] ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-400 line-through' ?>"><?= $label ?></span>
                <?php endforeach; ?>
              </p>
            </div>

            <?= post_button(
                'admin.togglePlan',
                ['id' => $p['id'], 'active' => $live ? '0' : '1'],
                // Switch drawn with inline styles (no Tailwind build step): green track + knob right = Live, grey + knob left = Hidden
                '<span class="relative inline-flex shrink-0 rounded-full transition" style="width:40px;height:22px;background:' . ($live ? '#16a34a' : '#3b4f66') . ';box-shadow:inset 0 0 0 1px rgb(255 255 255/.12)">'
                    . '<span class="absolute rounded-full transition-all" style="top:3px;left:' . ($live ? '21px' : '3px') . ';width:16px;height:16px;background:#fff;box-shadow:0 1px 3px rgb(0 0 0/.4)"></span></span>'
                    . '<span class="text-xs font-semibold" style="color:' . ($live ? '#4ade80' : '#94a3b8') . '">' . ($live ? 'Live' : 'Hidden') . '</span>',
                'inline-flex items-center gap-2 h-8 px-2 rounded-lg hover:bg-slate-50 transition',
                ['title' => $live ? 'Hide from the website' : 'Make live on the website', 'role' => 'switch', 'aria-checked' => $live ? 'true' : 'false']
            ) ?>

            <div class="flex items-center gap-1">
              <a href="<?= e(url('plans', ['edit' => $p['id']])) ?>#plan-form" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition" title="Edit">
                <?= icon('Pencil', 'w-4 h-4') ?>
              </a>
              <?= post_button(
                  'admin.deletePlan',
                  ['id' => $p['id']],
                  icon('Trash2', 'w-4 h-4'),
                  'p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition',
                  ['title' => 'Delete', 'confirm' => "Delete the “{$p['name']}” plan?"]
              ) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Create / edit form -->
  <form method="post" action="./" id="plan-form" class="bg-surface border border-slate-200/80 rounded-2xl shadow-card overflow-hidden scroll-mt-20">
    <?= form_action('admin.savePlan', url('plans')) ?>
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

    <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
          <?= icon($editing ? 'Pencil' : 'Plus', 'w-5 h-5') ?>
        </div>
        <div>
          <h2 class="text-base font-semibold tracking-tight text-slate-900"><?= $editing ? 'Edit plan' : 'New plan' ?></h2>
          <p class="text-sm text-slate-500">Prices are in INR. Use 0 for a free plan.</p>
        </div>
      </div>
      <?php if ($editing): ?>
        <a href="<?= e(url('plans')) ?>" class="text-xs font-medium text-slate-500 hover:text-slate-900">Cancel</a>
      <?php endif; ?>
    </div>

    <div class="p-5 sm:p-6 space-y-4">
      <div>
        <label class="<?= $labelClass ?>" for="plan-name">Plan name</label>
        <input id="plan-name" name="name" required maxlength="120" value="<?= e($f['name']) ?>" placeholder="e.g. Pro" class="<?= $inputClass ?>">
      </div>
      <div>
        <label class="<?= $labelClass ?>" for="plan-description">Short description</label>
        <input id="plan-description" name="description" maxlength="500" value="<?= e($f['description']) ?>" placeholder="For growing teams sending every week" class="<?= $inputClass ?>">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="<?= $labelClass ?>" for="plan-price">Price (₹)</label>
          <input id="plan-price" name="price" inputmode="decimal" required value="<?= e($f['price']) ?>" placeholder="499" class="<?= $inputClass ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>" for="plan-period">Billing</label>
          <select id="plan-period" name="billing_period" class="<?= $inputClass ?>">
            <?php foreach (['month' => 'Monthly', 'year' => 'Yearly', 'lifetime' => 'One-time (lifetime)'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= selected($f['billing_period'] === $val) ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="<?= $labelClass ?>" for="plan-features">Extra lines for the plan card <span class="font-normal text-slate-400">(one per line, e.g. support — limits below are listed automatically)</span></label>
        <textarea id="plan-features" name="features" rows="5" placeholder="Everything in Starter&#10;Email support" class="<?= $inputClass ?> h-auto py-2"><?= e($f['features']) ?></textarea>
      </div>
      <fieldset class="rounded-xl ring-1 ring-inset ring-slate-200 bg-slate-50/60 p-4 space-y-3">
        <legend class="px-1 text-xs font-semibold text-slate-700">Limits (enforced in the app)</legend>
        <div>
          <label class="<?= $labelClass ?>" for="plan-max">Max recipients per campaign <span class="font-normal text-slate-400">(empty = unlimited)</span></label>
          <input id="plan-max" name="max_recipients" type="number" min="0" value="<?= e($f['max_recipients']) ?>" placeholder="Unlimited" class="<?= $inputClass ?>">
        </div>
        <div class="grid sm:grid-cols-2 gap-2">
          <?php foreach (PLAN_FEATURES as [$label, $key]): ?>
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
              <input type="checkbox" name="<?= $key ?>" value="1" <?= checked($f[$key]) ?> class="rounded border-slate-300 text-brand-600">
              <?= e($label) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div>
          <label class="<?= $labelClass ?>" for="plan-test-limit">Real test emails per month <span class="font-normal text-slate-400">(empty = unlimited; needs “Test emails before sending”)</span></label>
          <input id="plan-test-limit" name="test_email_limit" type="number" min="0" value="<?= e($f['test_email_limit']) ?>" placeholder="Unlimited" class="<?= $inputClass ?>">
        </div>
      </fieldset>
      <div class="grid grid-cols-2 gap-3 items-end">
        <div>
          <label class="<?= $labelClass ?>" for="plan-sort">Display order</label>
          <input id="plan-sort" name="sort_order" type="number" value="<?= e($f['sort_order']) ?>" class="<?= $inputClass ?>">
        </div>
        <div class="space-y-2 pb-1">
          <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" <?= checked($f['is_active']) ?> class="rounded border-slate-300 text-brand-600">
            Live on website
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="is_featured" value="1" <?= checked($f['is_featured']) ?> class="rounded border-slate-300 text-brand-600">
            Highlight as “Popular”
          </label>
        </div>
      </div>
    </div>

    <div class="px-5 sm:px-6 py-4 border-t border-slate-200/80 bg-slate-50/60 flex justify-end">
      <button type="submit" class="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
        <?= icon('Save', 'w-4 h-4') ?> <?= $editing ? 'Save changes' : 'Create plan' ?>
      </button>
    </div>
  </form>
</div>

<!-- Recent payments -->
<div class="bg-surface border border-slate-200/80 rounded-2xl shadow-card overflow-hidden">
  <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80">
    <h2 class="text-base font-semibold tracking-tight text-slate-900">Recent payments</h2>
    <p class="text-sm text-slate-500">Razorpay payments across all users.</p>
  </div>
  <?php if (!$payments): ?>
    <div class="px-6 py-10 text-center text-sm text-slate-500">No payments yet.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-[11px] uppercase tracking-[0.06em] text-slate-500">
          <tr>
            <th class="text-left font-semibold px-5 sm:px-6 py-3">User</th>
            <th class="text-left font-semibold px-4 py-3">Plan</th>
            <th class="text-left font-semibold px-4 py-3">Amount</th>
            <th class="text-left font-semibold px-4 py-3">Status</th>
            <th class="text-left font-semibold px-4 py-3 hidden md:table-cell">Payment ID</th>
            <th class="text-left font-semibold px-5 sm:px-6 py-3">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td class="px-5 sm:px-6 py-3 text-slate-700 truncate max-w-[220px]"><?= e($pay['email']) ?></td>
              <td class="px-4 py-3 text-slate-700"><?= e($pay['plan_name']) ?></td>
              <td class="px-4 py-3 font-semibold tabular-nums"><?= e(money_label((int) $pay['amount_paise'], $pay['currency'])) ?></td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $pay['status'] === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-600' ?>"><?= e(ucfirst($pay['status'])) ?></span>
              </td>
              <td class="px-4 py-3 font-mono text-xs text-slate-500 hidden md:table-cell"><?= e($pay['razorpay_payment_id'] ?? '—') ?></td>
              <td class="px-5 sm:px-6 py-3 text-xs text-slate-500"><?= e(format_datetime($pay['paid_at'] ?? $pay['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
