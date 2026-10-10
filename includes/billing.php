<?php
/**
 * Plans & billing: admin-managed plans, plan activation on a user, and Razorpay payments (plain curl, no SDK).
 *
 * Razorpay is enabled only when RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET are set in .env.
 * Flow: billing.checkout creates a Razorpay order -> the Billing page opens Razorpay Checkout ->
 *       billing.verify checks the signature and activates the plan.
 * api/razorpay-webhook.php does the same from Razorpay's side (RAZORPAY_WEBHOOK_SECRET), in case the browser
 * closes before billing.verify runs. Activation is idempotent, so whichever arrives first wins.
 */

declare(strict_types=1);

class BillingFailure extends RuntimeException
{
}

const RAZORPAY_API = 'https://api.razorpay.com/v1';
const PLAN_PERIODS = ['month' => 'per month', 'year' => 'per year', 'lifetime' => 'one-time'];

/* ---------------------------------------------------------------------------
 * Master switch: while the admin keeps the plans system OFF, everyone gets every feature with no limits,
 * and Plans & Billing / Pricing are hidden. Admins turn it on from Manage Plans.
 * ------------------------------------------------------------------------- */

function plans_enabled(): bool
{
    return app_setting('plans_enabled', '0') === '1';
}

/* ---------------------------------------------------------------------------
 * Plans
 * ------------------------------------------------------------------------- */

function plans_all(): array
{
    return db()->query('SELECT * FROM plans ORDER BY sort_order ASC, price_paise ASC, id ASC')->fetchAll();
}

/** Plans the admin has switched on: shown on the landing page and the Billing page. */
function plans_active(): array
{
    return db()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, price_paise ASC, id ASC')->fetchAll();
}

function plan_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM plans WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function plan_features(array $plan): array
{
    return array_values(array_filter(array_map('trim', explode("\n", (string) $plan['features'])), 'strlen'));
}

/** "₹499" / "Free" */
function plan_price_label(array $plan): string
{
    if ((int) $plan['price_paise'] === 0) {
        return 'Free';
    }
    $symbol = $plan['currency'] === 'INR' ? '₹' : $plan['currency'] . ' ';
    $amount = (int) $plan['price_paise'] / 100;
    return $symbol . number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
}

function plan_period_label(array $plan): string
{
    return PLAN_PERIODS[$plan['billing_period']] ?? '';
}

/** Create (id = null) or update a plan from the admin form. Returns the plan id. */
function plan_save(?int $id, array $in): int
{
    $name = mb_substr(trim((string) ($in['name'] ?? '')), 0, 120);
    if ($name === '') {
        throw new BillingFailure('Please enter a plan name.');
    }
    $price = str_replace(',', '', trim((string) ($in['price'] ?? '0')));
    if (!is_numeric($price) || (float) $price < 0 || (float) $price > 10000000) {
        throw new BillingFailure('Please enter a valid price (0 for a free plan).');
    }
    $period = (string) ($in['billing_period'] ?? 'month');
    if (!isset(PLAN_PERIODS[$period])) {
        throw new BillingFailure('Please choose a billing period.');
    }

    $values = [
        $name,
        mb_substr(trim((string) ($in['description'] ?? '')), 0, 500),
        (int) round((float) $price * 100),
        $period,
        trim((string) ($in['features'] ?? '')),
        !empty($in['is_active']) ? 1 : 0,
        !empty($in['is_featured']) ? 1 : 0,
        (int) ($in['sort_order'] ?? 0),
        trim((string) ($in['max_recipients'] ?? '')) === '' ? null : max(0, (int) $in['max_recipients']),
        !empty($in['allow_excel']) ? 1 : 0,
        !empty($in['allow_ai']) ? 1 : 0,
        !empty($in['allow_export']) ? 1 : 0,
        !empty($in['allow_custom_interval']) ? 1 : 0,
        !empty($in['allow_test_email']) ? 1 : 0,
        trim((string) ($in['test_email_limit'] ?? '')) === '' ? null : max(0, (int) $in['test_email_limit']),
        now_sql(),
    ];

    if ($id === null) {
        db()->prepare('INSERT INTO plans (name, description, price_paise, billing_period, features, is_active, is_featured, sort_order,
                       max_recipients, allow_excel, allow_ai, allow_export, allow_custom_interval, allow_test_email, test_email_limit, updated_at, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([...$values, now_sql()]);
        return (int) db()->lastInsertId();
    }

    if (!plan_find($id)) {
        throw new BillingFailure('That plan no longer exists.');
    }
    db()->prepare('UPDATE plans SET name = ?, description = ?, price_paise = ?, billing_period = ?, features = ?, is_active = ?,
                   is_featured = ?, sort_order = ?, max_recipients = ?, allow_excel = ?, allow_ai = ?, allow_export = ?, allow_custom_interval = ?, allow_test_email = ?, test_email_limit = ?, updated_at = ?
                   WHERE id = ?')->execute([...$values, $id]);
    return $id;
}

/** Switch a plan on/off for the frontend. Users who already have it keep it. */
function plan_set_active(int $id, bool $active): array
{
    $plan = plan_find($id);
    if (!$plan) {
        throw new BillingFailure('That plan no longer exists.');
    }
    db()->prepare('UPDATE plans SET is_active = ?, updated_at = ? WHERE id = ?')->execute([$active ? 1 : 0, now_sql(), $id]);
    $plan['is_active'] = $active ? 1 : 0;
    return $plan;
}

/** Starter plans for a new install (only when there are no plans yet). Admins can edit or hide them in Manage Plans. */
function plans_seed_defaults(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM plans')->fetchColumn() > 0) {
        return;
    }
    $defaults = [
        ['Starter', 'Try MailDart and send your first small campaigns.', 0, 'lifetime', 0, 1,
            "Up to 100 recipients per campaign\nSandbox mode to practise safely\nReady-made festival & business templates\n1 SMTP account (Gmail or any SMTP)\nDelivery logs"],
        ['Pro', 'For businesses that email customers every month.', 49900, 'month', 1, 2,
            "Up to 5,000 recipients per campaign\nExcel / CSV import with custom {{variables}}\nAI template generator\nCustom send gap & live countdown\nTest emails before sending\nExport delivery logs as CSV\nEmail support"],
        ['Business', 'For agencies and teams with large lists.', 149900, 'month', 0, 3,
            "Unlimited recipients\nEverything in Pro\nPriority support\nHelp setting up SMTP & sender domains"],
    ];
    $stmt = $pdo->prepare('INSERT INTO plans (name, description, price_paise, billing_period, is_featured, sort_order, features, is_active, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');
    foreach ($defaults as $row) {
        $stmt->execute($row);
    }
}

/* ---------------------------------------------------------------------------
 * Plan limits (what a user may do)
 * ------------------------------------------------------------------------- */

/** Feature key => [label, plans column]. Everything here is enforced in the app and listed on plan cards. */
const PLAN_FEATURES = [
    'excel' => ['Excel / CSV import with {{variables}}', 'allow_excel'],
    'ai' => ['AI template generator', 'allow_ai'],
    'interval' => ['Custom send gap (30 sec – any minutes)', 'allow_custom_interval'],
    'test' => ['Test emails before sending', 'allow_test_email'],
    'export' => ['CSV export of recipients & logs', 'allow_export'],
];
const PLAN_FEATURE_LABELS = ['excel' => 'Excel / CSV import', 'ai' => 'AI template generator', 'interval' => 'Custom send gap',
    'test' => 'Test email', 'export' => 'CSV export'];

/** The fixed gap (minutes) for plans without a custom send gap. */
const PLAN_DEFAULT_INTERVAL = 5;

/** Used when a user has no active plan and no free plan exists: same as the default Starter plan. */
const PLAN_FALLBACK_LIMITS = ['max_recipients' => 100, 'excel' => true, 'ai' => false, 'export' => false, 'interval' => false, 'test' => true, 'test_limit' => 10];

/**
 * What this user may do: ['plan' => name, 'max_recipients' => ?int (null = unlimited), 'excel' / 'ai' / 'export' => bool,
 * 'unlimited' => bool]. Admins have no limits. Without an active plan the user gets the free plan's limits
 * (lowest-sorted active ₹0 plan), or PLAN_FALLBACK_LIMITS when there is none.
 */
function user_limits(array $userRow): array
{
    static $cache = [];
    $key = (int) $userRow['id'] . '|' . ($userRow['plan_id'] ?? '') . '|' . ($userRow['plan_expires_at'] ?? '') . '|' . user_role($userRow);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (!plans_enabled()) {
        return $cache[$key] = ['plan' => 'Full access', 'max_recipients' => null, 'test_limit' => null, 'unlimited' => true] + array_fill_keys(array_keys(PLAN_FEATURES), true);
    }
    if (is_admin($userRow)) {
        return $cache[$key] = ['plan' => 'Admin', 'max_recipients' => null, 'test_limit' => null, 'unlimited' => true] + array_fill_keys(array_keys(PLAN_FEATURES), true);
    }

    $plan = user_current_plan($userRow) ?? plan_default_free();
    if (!$plan) {
        return $cache[$key] = ['plan' => 'Free'] + PLAN_FALLBACK_LIMITS + ['unlimited' => false];
    }
    $limits = [
        'plan' => $plan['name'],
        'max_recipients' => $plan['max_recipients'] === null ? null : (int) $plan['max_recipients'],
        'test_limit' => $plan['test_email_limit'] === null ? null : (int) $plan['test_email_limit'],
        'unlimited' => false,
    ];
    foreach (PLAN_FEATURES as $feature => [, $column]) {
        $limits[$feature] = (bool) $plan[$column];
    }
    return $cache[$key] = $limits;
}

function user_can(array $userRow, string $feature): bool
{
    return !empty(user_limits($userRow)[$feature]);
}

/** Throw a BillingFailure with an upgrade message when the user's plan does not include $feature. */
function plan_require(array $userRow, string $feature): void
{
    if (!user_can($userRow, $feature)) {
        $limits = user_limits($userRow);
        throw new BillingFailure(PLAN_FEATURE_LABELS[$feature] . " is not included in your {$limits['plan']} plan. Upgrade to unlock it.");
    }
}

/**
 * Check that adding $adding recipients stays within the plan's limit. With $replace the current list is
 * thrown away first, so only $adding counts. Lists that are already over the limit are kept, just not grown.
 */
function plan_guard_recipients(array $userRow, int $campaignId, int $adding, bool $replace = false): void
{
    $max = user_limits($userRow)['max_recipients'];
    if ($max === null || $adding <= 0) {
        return;
    }
    $current = 0;
    if (!$replace) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM recipients WHERE campaign_id = ?');
        $stmt->execute([$campaignId]);
        $current = (int) $stmt->fetchColumn();
    }
    if ($current + $adding > $max) {
        $plan = user_limits($userRow)['plan'];
        $left = max(0, $max - $current);
        throw new BillingFailure("Your {$plan} plan allows up to " . number_format($max) . ' recipients'
            . ($left > 0 ? " (you can add {$left} more, not {$adding})." : ' and your list is full.') . ' Upgrade to add more.');
    }
}

/**
 * What a plan card lists, generated from the plan's real limits so the card always matches what the app
 * enforces: [['text' => …, 'included' => bool], …] — recipients first, then each feature, then the admin's
 * free-text extras (support, etc.).
 */
function plan_card_lines(array $plan): array
{
    $lines = [['text' => $plan['max_recipients'] === null ? 'Unlimited recipients'
        : 'Up to ' . number_format((int) $plan['max_recipients']) . ' recipients per campaign', 'included' => true]];
    foreach (PLAN_FEATURES as $feature => [$label, $column]) {
        if ($feature === 'test' && $plan[$column] && $plan['test_email_limit'] !== null) {
            $label = number_format((int) $plan['test_email_limit']) . ' test emails per month';
        }
        $lines[] = ['text' => $label, 'included' => (bool) $plan[$column]];
    }
    foreach (plan_features($plan) as $extra) {
        $lines[] = ['text' => $extra, 'included' => true];
    }
    return $lines;
}

/* ---------------------------------------------------------------------------
 * Monthly usage (test emails)
 * ------------------------------------------------------------------------- */

function usage_get(int $userId, string $metric): int
{
    $stmt = db()->prepare('SELECT used FROM usage_counters WHERE user_id = ? AND metric = ? AND period = ?');
    $stmt->execute([$userId, $metric, date('Y-m')]);
    return (int) $stmt->fetchColumn();
}

function usage_increment(int $userId, string $metric): void
{
    db()->prepare('INSERT INTO usage_counters (user_id, metric, period, used) VALUES (?, ?, ?, 1)
                   ON DUPLICATE KEY UPDATE used = used + 1')->execute([$userId, $metric, date('Y-m')]);
}

/** Real test emails left this month: null = unlimited, 0 = none (feature off or quota used up). */
function test_emails_left(array $userRow): ?int
{
    $limits = user_limits($userRow);
    if (!$limits['test']) {
        return 0;
    }
    if ($limits['test_limit'] === null) {
        return null;
    }
    return max(0, $limits['test_limit'] - usage_get((int) $userRow['id'], 'test_email'));
}

/** How many more recipients the user may add (null = unlimited). With $replace the current list does not count. */
function recipients_slots_left(array $userRow, int $campaignId, bool $replace = false): ?int
{
    $max = user_limits($userRow)['max_recipients'];
    if ($max === null) {
        return null;
    }
    if ($replace) {
        return $max;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM recipients WHERE campaign_id = ?');
    $stmt->execute([$campaignId]);
    return max(0, $max - (int) $stmt->fetchColumn());
}

/** Small "🔒 Pro" badge for a button whose feature the user's plan does not include ('' when allowed). */
function plan_lock_badge(array $userRow, string $feature, string $class = 'bg-slate-100 text-slate-600'): string
{
    if (user_can($userRow, $feature)) {
        return '';
    }
    return '<span class="inline-flex items-center gap-0.5 ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold ' . e($class) . '">'
        . icon('Lock', 'w-2.5 h-2.5') . ' Pro</span>';
}

function plan_delete(int $id): void
{
    db()->prepare('DELETE FROM plans WHERE id = ?')->execute([$id]); // users / payments keep working (FK SET NULL)
}

/* ---------------------------------------------------------------------------
 * A user's plan
 * ------------------------------------------------------------------------- */

function user_plan_active(array $userRow): bool
{
    return !empty($userRow['plan_id'])
        && (empty($userRow['plan_expires_at']) || strtotime((string) $userRow['plan_expires_at']) > time());
}

/** The user's current plan row (with 'expires_at'), or null when they have none or it expired. */
function user_current_plan(array $userRow): ?array
{
    if (!user_plan_active($userRow)) {
        return null;
    }
    $plan = plan_find((int) $userRow['plan_id']);
    return $plan ? $plan + ['expires_at' => $userRow['plan_expires_at']] : null;
}

/** The free plan every account starts on: the lowest-sorted live ₹0 plan (Starter by default), or null. */
function plan_default_free(): ?array
{
    static $plan = false;
    if ($plan === false) {
        $plan = db()->query('SELECT * FROM plans WHERE is_active = 1 AND price_paise = 0 ORDER BY sort_order ASC, id ASC LIMIT 1')->fetch() ?: null;
    }
    return $plan;
}

/** Put a new account (or one without any plan) on the default free plan. Never replaces an existing plan. */
function user_assign_default_plan(int $userId): void
{
    $plan = plan_default_free();
    if ($plan) {
        db()->prepare('UPDATE users SET plan_id = ?, plan_expires_at = NULL WHERE id = ? AND plan_id IS NULL')->execute([$plan['id'], $userId]);
    }
}

/**
 * Give a user a plan. Renewing the same, still-active plan extends from its current expiry
 * instead of from today, so paying early never loses days.
 */
function user_activate_plan(int $userId, array $plan): void
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return;
    }

    $expires = null;
    if ($plan['billing_period'] !== 'lifetime') {
        $from = time();
        if ((int) $user['plan_id'] === (int) $plan['id'] && user_plan_active($user) && $user['plan_expires_at']) {
            $from = max($from, (int) strtotime((string) $user['plan_expires_at']));
        }
        $expires = date('Y-m-d H:i:s', (int) strtotime($plan['billing_period'] === 'year' ? '+1 year' : '+1 month', $from));
    }

    db()->prepare('UPDATE users SET plan_id = ?, plan_expires_at = ?, updated_at = ? WHERE id = ?')
        ->execute([$plan['id'], $expires, now_sql(), $userId]);
}

/** Admin: assign a plan without payment. 0 (no plan) puts the user back on the default free plan. */
function user_admin_set_plan(int $userId, int $planId): void
{
    if ($planId === 0) {
        db()->prepare('UPDATE users SET plan_id = NULL, plan_expires_at = NULL, updated_at = ? WHERE id = ?')->execute([now_sql(), $userId]);
        user_assign_default_plan($userId);
        return;
    }
    $plan = plan_find($planId);
    if (!$plan) {
        throw new BillingFailure('That plan no longer exists.');
    }
    user_activate_plan($userId, $plan);
}

/* ---------------------------------------------------------------------------
 * Razorpay
 * ------------------------------------------------------------------------- */

function razorpay_enabled(): bool
{
    return (bool) (env_secret('RAZORPAY_KEY_ID') && env_secret('RAZORPAY_KEY_SECRET'));
}

function razorpay_request(string $method, string $path, array $body = []): array
{
    $ch = curl_init(RAZORPAY_API . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERPWD => env_secret('RAZORPAY_KEY_ID') . ':' . env_secret('RAZORPAY_KEY_SECRET'),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => $method === 'GET' ? null : json_encode($body),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        error_log('MailDart Razorpay: ' . $err);
        throw new BillingFailure('Could not reach Razorpay. Please check your internet connection and try again.');
    }
    $data = json_decode((string) $raw, true);
    if ($status >= 400 || !is_array($data)) {
        error_log('MailDart Razorpay ' . $status . ': ' . $raw);
        $msg = is_array($data) ? ($data['error']['description'] ?? '') : '';
        throw new BillingFailure('Razorpay error' . ($msg !== '' ? ': ' . $msg : '. Please try again.'));
    }
    return $data;
}

/** Create a Razorpay order for an active paid plan and remember it as a pending payment. */
function razorpay_create_order(array $user, array $plan): array
{
    if (!razorpay_enabled()) {
        throw new BillingFailure('Online payments are not set up yet. Ask your administrator to add the RAZORPAY_* settings in .env.');
    }
    if (empty($plan['is_active'])) {
        throw new BillingFailure('This plan is not available right now.');
    }
    if ((int) $plan['price_paise'] < 100) {
        throw new BillingFailure('Paid plans must cost at least ₹1.');
    }

    $order = razorpay_request('POST', '/orders', [
        'amount' => (int) $plan['price_paise'],
        'currency' => $plan['currency'],
        'receipt' => 'md_' . $user['id'] . '_' . time(),
        'notes' => ['user_id' => (string) $user['id'], 'plan_id' => (string) $plan['id'], 'email' => $user['email']],
    ]);
    if (empty($order['id'])) {
        throw new BillingFailure('Razorpay did not return an order. Please try again.');
    }

    db()->prepare('INSERT INTO payments (user_id, plan_id, plan_name, amount_paise, currency, razorpay_order_id, status, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, \'created\', ?)')
        ->execute([$user['id'], $plan['id'], $plan['name'], $plan['price_paise'], $plan['currency'], $order['id'], now_sql()]);

    return $order;
}

function payment_find_by_order(string $orderId): ?array
{
    $stmt = db()->prepare('SELECT * FROM payments WHERE razorpay_order_id = ?');
    $stmt->execute([$orderId]);
    return $stmt->fetch() ?: null;
}

/**
 * Mark a payment paid and activate its plan. Safe to call twice (browser + webhook):
 * only the call that flips the status activates the plan. Returns true if this call did it.
 */
function payment_mark_paid(array $payment, string $razorpayPaymentId): bool
{
    $stmt = db()->prepare('UPDATE payments SET status = \'paid\', razorpay_payment_id = ?, paid_at = ? WHERE id = ? AND status <> \'paid\'');
    $stmt->execute([$razorpayPaymentId, now_sql(), $payment['id']]);
    if ($stmt->rowCount() !== 1) {
        return false;
    }
    $plan = $payment['plan_id'] ? plan_find((int) $payment['plan_id']) : null;
    if ($plan) {
        user_activate_plan((int) $payment['user_id'], $plan);
    } else {
        error_log('MailDart Razorpay: paid order ' . $payment['razorpay_order_id'] . ' has no plan any more; activate manually.');
    }
    return true;
}

/** Checkout success handler: verify Razorpay's signature (order_id|payment_id, HMAC-SHA256 with the key secret). */
function razorpay_verify_checkout(array $user, string $orderId, string $paymentId, string $signature): array
{
    $payment = payment_find_by_order($orderId);
    if (!$payment || (int) $payment['user_id'] !== (int) $user['id']) {
        throw new BillingFailure('We could not find that order. If money was deducted, contact support with your payment ID.');
    }
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, (string) env_secret('RAZORPAY_KEY_SECRET'));
    if ($paymentId === '' || !hash_equals($expected, $signature)) {
        db()->prepare('UPDATE payments SET status = \'failed\' WHERE id = ? AND status = \'created\'')->execute([$payment['id']]);
        throw new BillingFailure('Payment verification failed. If money was deducted, contact support with your payment ID.');
    }
    payment_mark_paid($payment, $paymentId);
    return $payment;
}

/** Webhook: verify X-Razorpay-Signature over the raw body, then activate on payment.captured / order.paid. */
function razorpay_handle_webhook(string $rawBody, string $signature): void
{
    $secret = env_secret('RAZORPAY_WEBHOOK_SECRET');
    if (!$secret) {
        throw new BillingFailure('Webhook secret not configured.');
    }
    if (!hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature)) {
        throw new BillingFailure('Invalid signature.');
    }

    $event = json_decode($rawBody, true);
    $type = $event['event'] ?? '';
    if (!in_array($type, ['payment.captured', 'order.paid'], true)) {
        return; // ignore other events
    }
    $entity = $event['payload']['payment']['entity'] ?? [];
    $orderId = (string) ($entity['order_id'] ?? ($event['payload']['order']['entity']['id'] ?? ''));
    $payment = $orderId !== '' ? payment_find_by_order($orderId) : null;
    if ($payment && (int) ($entity['amount'] ?? 0) === (int) $payment['amount_paise']) {
        payment_mark_paid($payment, (string) ($entity['id'] ?? ''));
    }
}

function payments_for_user(int $userId, int $limit = 20): array
{
    $stmt = db()->prepare('SELECT * FROM payments WHERE user_id = ? AND status <> \'created\' ORDER BY id DESC LIMIT ' . max(1, $limit));
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function payments_recent(int $limit = 25): array
{
    return db()->query('SELECT p.*, u.email, u.display_name FROM payments p JOIN users u ON u.id = p.user_id
                        WHERE p.status <> \'created\' ORDER BY p.id DESC LIMIT ' . max(1, $limit))->fetchAll();
}

function money_label(int $paise, string $currency = 'INR'): string
{
    return plan_price_label(['price_paise' => $paise, 'currency' => $currency]);
}
