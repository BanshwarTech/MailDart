<?php
/**
 * Admin: Users & Roles. Lists every account and lets an admin promote / demote others.
 * Vars from the dashboard layout: $userRow. index.php only renders this page for admins.
 */
$users = users_list();
$adminCount = count(array_filter($users, fn ($u) => $u['role'] === 'admin'));
$plans = plans_all();
$showPlans = plans_enabled();
$planNames = array_column($plans, 'name', 'id');

$stats = [
    ['label' => 'Total accounts', 'value' => count($users), 'icon' => 'Users'],
    ['label' => 'Admins', 'value' => $adminCount, 'icon' => 'ShieldCheck'],
    ['label' => 'Regular users', 'value' => count($users) - $adminCount, 'icon' => 'User'],
];
?>
<div class="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col">

  <div class="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex items-center gap-3">
    <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
      <?= icon('ShieldCheck', 'w-5 h-5') ?>
    </div>
    <div>
      <h2 class="text-base font-semibold tracking-tight text-slate-900">Users &amp; Roles</h2>
      <p class="text-sm text-slate-500">The first account is always an admin. Admins can open this page and manage roles.</p>
    </div>
  </div>

  <div class="px-5 sm:px-6 py-4 border-b border-slate-200/80 grid grid-cols-1 sm:grid-cols-3 gap-3">
    <?php foreach ($stats as $s): ?>
      <div class="flex items-center gap-3 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 px-4 py-3">
        <div class="w-9 h-9 shrink-0 rounded-lg bg-surface ring-1 ring-slate-200 flex items-center justify-center">
          <?= icon($s['icon'], 'w-4 h-4 text-slate-400') ?>
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-medium text-slate-500 truncate"><?= e($s['label']) ?></p>
          <p class="text-lg font-bold tabular-nums leading-tight text-slate-900"><?= (int) $s['value'] ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-[11px] uppercase tracking-[0.06em] text-slate-500">
        <tr>
          <th class="text-left font-semibold px-5 sm:px-6 py-3">User</th>
          <th class="text-left font-semibold px-4 py-3">Role</th>
          <?php if ($showPlans): ?><th class="text-left font-semibold px-4 py-3">Plan</th><?php endif; ?>
          <th class="text-left font-semibold px-4 py-3 hidden md:table-cell">Joined</th>
          <th class="text-right font-semibold px-5 sm:px-6 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($users as $u):
            $isSelf = (int) $u['id'] === (int) $userRow['id'];
            $isAdminRow = $u['role'] === 'admin';
            $name = $u['display_name'] ?: explode('@', $u['email'])[0];
            $planActive = user_plan_active($u);
            ?>
          <tr class="hover:bg-slate-50">
            <td class="px-5 sm:px-6 py-3">
              <div class="flex items-center gap-3 min-w-0">
                <?php if ($u['photo_url'] !== ''): ?>
                  <img src="<?= e($u['photo_url']) ?>" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                <?php else: ?>
                  <div class="w-8 h-8 shrink-0 rounded-full bg-gradient-to-br from-brand-500 to-accent-600 text-white text-xs font-semibold flex items-center justify-center">
                    <?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?>
                  </div>
                <?php endif; ?>
                <div class="min-w-0">
                  <p class="font-medium text-slate-900 truncate">
                    <?= e($name) ?>
                    <?php if ($isSelf): ?><span class="ml-1 text-[11px] font-normal text-slate-400">(you)</span><?php endif; ?>
                  </p>
                  <p class="text-xs text-slate-500 truncate"><?= e($u['email']) ?></p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <?php if ($isAdminRow): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 ring-1 ring-inset ring-amber-200 text-[11px] font-semibold text-amber-700">
                  <?= icon('ShieldCheck', 'w-3 h-3') ?> Admin
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200 text-[11px] font-medium text-slate-600">
                  <?= icon('User', 'w-3 h-3') ?> User
                </span>
              <?php endif; ?>
            </td>
            <?php if ($showPlans): ?>
            <td class="px-4 py-3">
              <form method="post" action="./" class="flex items-center gap-1.5" data-autosubmit>
                <?= form_action('admin.setUserPlan') ?>
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <select name="plan_id" class="h-8 max-w-[160px] px-2 bg-surface border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-brand-500" title="Assign a plan manually">
                  <?php if (!plan_default_free()): ?><option value="0">No plan</option><?php endif; ?>
                  <?php foreach ($plans as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= selected((int) $u['plan_id'] === (int) $p['id']) ?>><?= e($p['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <?php if ($u['plan_id']): ?>
                <p class="mt-1 text-[11px] <?= $planActive ? 'text-slate-500' : 'text-red-600' ?>">
                  <?= $u['plan_expires_at'] ? ($planActive ? 'Until ' : 'Expired ') . e(format_datetime($u['plan_expires_at'])) : 'No expiry' ?>
                </p>
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <td class="px-4 py-3 text-xs text-slate-500 hidden md:table-cell"><?= e(format_datetime($u['created_at'])) ?></td>
            <td class="px-5 sm:px-6 py-3 text-right">
              <?php if ($isSelf): ?>
                <span class="text-xs text-slate-400" title="You cannot change your own role">—</span>
              <?php elseif ($isAdminRow): ?>
                <?= post_button(
                    'admin.setRole',
                    ['id' => $u['id'], 'role' => 'user'],
                    icon('User', 'w-3.5 h-3.5') . '<span>Make user</span>',
                    'inline-flex items-center gap-1.5 h-8 px-3 text-xs font-medium text-slate-700 bg-surface hover:bg-slate-50 ring-1 ring-inset ring-slate-300 rounded-lg transition',
                    ['confirm' => "Remove admin access from $name?"]
                ) ?>
              <?php else: ?>
                <?= post_button(
                    'admin.setRole',
                    ['id' => $u['id'], 'role' => 'admin'],
                    icon('ShieldCheck', 'w-3.5 h-3.5') . '<span>Make admin</span>',
                    'inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition',
                    ['confirm' => "Give $name full admin access?"]
                ) ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
