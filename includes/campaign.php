<?php
/**
 * Campaign domain: storage (MySQL), placeholder rendering, SMTP settings and the staggered dispatcher.
 *
 * Array shapes intentionally match the old TypeScript types (camelCase keys) so views read like the React code:
 *   campaign:  id, name, companyName, festival, subject, htmlTemplate, discountCode, intervalMinutes,
 *              intervalSeconds, status (idle|running|paused|completed), remainingSeconds, recipients[], logs[], customVariables[]
 *   recipient: id, name, email, customData (array|null), status (pending|sending|delivered|failed|skipped),
 *              sentAt, messageId, errorMessage, retryCount
 *   log:       id, timestamp, senderEmail, recipientEmail, recipientName, subject, status, messageId, detail, mode
 *   smtp:      enabled, host, port, secure, username, password, fromName, fromEmail, replyTo
 */

declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

/* ---------------------------------------------------------------------------
 * Schema
 * ------------------------------------------------------------------------- */
function md_migrate_campaign(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS campaigns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL DEFAULT '',
            company_name VARCHAR(255) NOT NULL DEFAULT '',
            festival VARCHAR(40) NOT NULL DEFAULT 'custom',
            subject VARCHAR(998) NOT NULL DEFAULT '',
            html_template MEDIUMTEXT NOT NULL,
            discount_code VARCHAR(191) NOT NULL DEFAULT '',
            interval_minutes DECIMAL(10,4) NOT NULL DEFAULT 5,
            status VARCHAR(12) NOT NULL DEFAULT 'idle',
            remaining_seconds INT NOT NULL DEFAULT 300,
            next_send_at DATETIME NULL,
            custom_variables TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_campaign_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS recipients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            rid VARCHAR(80) NOT NULL,
            position INT NOT NULL DEFAULT 0,
            name VARCHAR(255) NOT NULL DEFAULT '',
            email VARCHAR(320) NOT NULL,
            custom_data TEXT NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'pending',
            sent_at VARCHAR(40) NULL,
            message_id VARCHAR(255) NULL,
            error_message TEXT NULL,
            retry_count INT NOT NULL DEFAULT 0,
            UNIQUE KEY uq_recipient (campaign_id, rid),
            INDEX idx_recipient_order (campaign_id, position),
            CONSTRAINT fk_recipient_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS dispatch_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            lid VARCHAR(80) NOT NULL,
            logged_at VARCHAR(40) NOT NULL,
            sender_email VARCHAR(320) NULL,
            recipient_email VARCHAR(320) NOT NULL,
            recipient_name VARCHAR(255) NOT NULL DEFAULT '',
            subject VARCHAR(998) NOT NULL DEFAULT '',
            status VARCHAR(12) NOT NULL,
            message_id VARCHAR(255) NULL,
            detail TEXT NULL,
            mode VARCHAR(12) NOT NULL,
            INDEX idx_log_campaign (campaign_id, id),
            CONSTRAINT fk_log_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
}

/* ---------------------------------------------------------------------------
 * Static data
 * ------------------------------------------------------------------------- */

/** Festival presets (was src/data/presetTemplates.ts): id, name, festival, description, subject, badgeColor, defaultDiscount, html */
function preset_templates(): array
{
    static $p = null;
    return $p ??= json_decode((string) file_get_contents(MD_ROOT . '/data/presets.json'), true);
}

function initial_sample_recipients(): array
{
    return json_decode((string) file_get_contents(MD_ROOT . '/data/sample-recipients.json'), true);
}

/** Pages in campaign order (was src/data/navigation.ts). */
function nav_items(): array
{
    return [
        ['id' => 'overview', 'label' => 'Overview', 'description' => 'Your campaign progress at a glance', 'icon' => 'LayoutDashboard'],
        ['id' => 'settings', 'step' => 1, 'label' => 'SMTP Settings', 'description' => 'Connect the email account that sends your campaign', 'icon' => 'Server'],
        ['id' => 'recipients', 'step' => 2, 'label' => 'Recipients', 'description' => 'Add contacts or import them from Excel', 'icon' => 'Users'],
        ['id' => 'editor', 'step' => 3, 'label' => 'Template Studio', 'description' => 'Campaign details, email design & live preview', 'icon' => 'FileCode'],
        ['id' => 'dispatch', 'step' => 4, 'label' => 'Dispatcher', 'description' => 'Start staggered sending with a live countdown', 'icon' => 'Timer'],
        ['id' => 'logs', 'step' => 5, 'label' => 'Delivery Logs', 'description' => 'Track every dispatched email', 'icon' => 'ScrollText'],
        ['id' => 'guide', 'label' => 'Guide', 'description' => 'How to set up and run a successful campaign', 'icon' => 'BookOpen'],
    ];
}

function step_items(): array
{
    return array_values(array_filter(nav_items(), fn ($n) => isset($n['step'])));
}

function total_steps(): int
{
    return count(step_items());
}

function find_nav_item(string $id): array
{
    foreach (nav_items() as $n) {
        if ($n['id'] === $id) {
            return $n;
        }
    }
    return nav_items()[0];
}

/* ---------------------------------------------------------------------------
 * Campaign helpers (was src/utils/campaignHelper.ts)
 * ------------------------------------------------------------------------- */

function festival_title(string $festival): string
{
    return match ($festival) {
        'diwali' => 'Diwali (Deepavali)',
        'eid' => 'Eid Mubarak',
        'christmas' => 'Christmas & New Year',
        'newyear' => 'Happy New Year',
        'holi' => 'Holi (Festival of Colors)',
        'thanksgiving' => 'Thanksgiving Celebration',
        'blackfriday' => 'Mega Sale & Offers',
        'newsletter' => 'Monthly Company Newsletter',
        'product_launch' => 'New Product Announcement',
        'followup_reminder' => 'Payment & Follow-up Reminder',
        'welcome_onboarding' => 'Welcome & Client Onboarding',
        'event_invite' => 'Event & Webinar Invitation',
        'custom' => 'General Campaign',
        default => 'General Email Campaign',
    };
}

/** Replace {{name}}, {{email}}, {{festival}}, {{company}}, {{discount}} and any custom Excel variables. */
function replace_placeholders(string $text, array $recipient, array $campaign): string
{
    if ($text === '') {
        return '';
    }
    $cd = is_array($recipient['customData'] ?? null) ? $recipient['customData'] : null;
    $lit = fn (string $v) => str_replace(['\\', '$'], ['\\\\', '\\$'], $v); // literal replacement strings

    $result = preg_replace('/{{\s*name\s*}}/i', $lit(($recipient['name'] ?? '') ?: 'Valued Customer'), $text);
    $result = preg_replace('/{{\s*email\s*}}/i', $lit($recipient['email'] ?? ''), $result);
    $result = preg_replace('/{{\s*festival\s*}}/i', $lit(festival_title($campaign['festival'] ?? '')), $result);

    $company = ($cd['company'] ?? '') ?: (($cd['Company'] ?? '') ?: (($campaign['companyName'] ?? '') ?: 'Our Company'));
    $discount = ($cd['discount'] ?? '') ?: (($cd['Discount'] ?? '') ?: (($campaign['discountCode'] ?? '') ?: 'FESTIVEGIFT'));
    $result = preg_replace('/{{\s*company\s*}}/i', $lit((string) $company), $result);
    $result = preg_replace('/{{\s*discount\s*}}/i', $lit((string) $discount), $result);

    if ($cd) {
        // 1. Direct key replacements
        foreach ($cd as $rawKey => $value) {
            if ($value === null) {
                continue;
            }
            $key = preg_quote(trim((string) $rawKey), '/');
            $result = preg_replace('/{{\s*' . $key . '\s*}}/iu', $lit((string) $value), $result);
        }
        // 2. Normalized matching ({{gift_item}} / {{gift item}} / {{GiftItem}})
        $norm = fn (string $s) => preg_replace('/[\s_-]+/', '', strtolower(trim($s)));
        $result = preg_replace_callback('/{{\s*([\w\s-]+)\s*}}/i', function ($m) use ($cd, $norm) {
            $target = $norm($m[1]);
            foreach ($cd as $k => $v) {
                if ($v !== null && $norm((string) $k) === $target) {
                    return (string) $v;
                }
            }
            return $m[0];
        }, $result);
    }
    return $result;
}

/** Parse pasted text ("Name, email" / "email, Name" / "email" per line) into recipients. */
function parse_recipients_input(string $raw): array
{
    if (trim($raw) === '') {
        return [];
    }
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $raw))));
    $out = [];
    foreach ($lines as $i => $line) {
        $parts = array_map('trim', preg_split('/[,;\t]/', $line));
        $name = '';
        $email = '';
        if (count($parts) >= 2) {
            if (str_contains($parts[0], '@')) {
                [$email, $name] = [$parts[0], $parts[1]];
            } else {
                [$name, $email] = [$parts[0], $parts[1]];
            }
        } elseif (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $line, $m)) {
            $email = $m[0];
            $name = ucfirst(preg_replace('/[._-]/', ' ', explode('@', $email)[0]));
        }
        if ($email !== '' && str_contains($email, '@')) {
            $out[] = new_recipient($name ?: 'Valued Customer', $email, null, $i);
        }
    }
    return $out;
}

function new_recipient(string $name, string $email, ?array $customData = null, int|string $salt = 0): array
{
    return [
        'id' => 'rec-' . (int) (microtime(true) * 1000) . '-' . $salt . '-' . substr(bin2hex(random_bytes(3)), 0, 4),
        'name' => $name,
        'email' => $email,
        'customData' => $customData ?: null,
        'status' => 'pending',
        'sentAt' => null,
        'messageId' => null,
        'errorMessage' => null,
        'retryCount' => 0,
    ];
}

/** 'live' | 'incomplete' | 'sandbox' — real delivery only when SMTP is enabled AND credentials are filled in. */
function smtp_status(array $smtp): string
{
    if (empty($smtp['enabled'])) {
        return 'sandbox';
    }
    return (($smtp['username'] ?? '') !== '' && ($smtp['password'] ?? '') !== '') ? 'live' : 'incomplete';
}

/** Completion state of each workflow step (sidebar ticks, Overview, StepNav). */
function step_done(array $campaign, array $smtp): array
{
    $delivered = false;
    foreach ($campaign['recipients'] as $r) {
        if ($r['status'] === 'delivered') {
            $delivered = true;
            break;
        }
    }
    return [
        'settings' => smtp_status($smtp) === 'live',
        'recipients' => count($campaign['recipients']) > 0,
        'editor' => trim($campaign['subject']) !== '' && trim($campaign['htmlTemplate']) !== '',
        'dispatch' => $campaign['status'] === 'completed' || $delivered,
        'logs' => count($campaign['logs']) > 0,
    ];
}

/* ---------------------------------------------------------------------------
 * Campaign storage
 * ------------------------------------------------------------------------- */

function campaign_row(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM campaigns WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }

    // First visit: same defaults as the React app (Diwali preset + sample recipients)
    $preset = preset_templates()[0];
    $now = now_sql();
    db()->prepare('INSERT INTO campaigns (user_id, name, company_name, festival, subject, html_template, discount_code, interval_minutes, status, remaining_seconds, custom_variables, created_at, updated_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, 5, \'idle\', 300, \'[]\', ?, ?)')
        ->execute([$userId, 'Festival Grand Celebration Campaign', 'Acme Global Innovations', $preset['festival'], $preset['subject'], $preset['html'], $preset['defaultDiscount'], $now, $now]);
    $campaignId = (int) db()->lastInsertId();
    recipients_replace($campaignId, initial_sample_recipients());
    return campaign_row($userId);
}

/** Load the user's campaign with recipients and logs (newest log first). */
function campaign_load(int $userId, bool $withLogs = true): array
{
    $row = campaign_row($userId);
    $minutes = (float) $row['interval_minutes'];
    $remaining = (int) $row['remaining_seconds'];
    if ($row['status'] === 'running' && $row['next_send_at']) {
        $remaining = max(0, strtotime($row['next_send_at']) - time());
    }
    return [
        'id' => (int) $row['id'],
        'userId' => (int) $row['user_id'],
        'name' => $row['name'],
        'companyName' => $row['company_name'],
        'festival' => $row['festival'],
        'subject' => $row['subject'],
        'htmlTemplate' => $row['html_template'],
        'discountCode' => $row['discount_code'],
        'intervalMinutes' => $minutes == (int) $minutes ? (int) $minutes : $minutes,
        'intervalSeconds' => (int) round($minutes * 60),
        'status' => $row['status'],
        'remainingSeconds' => $remaining,
        'nextSendAt' => $row['next_send_at'],
        'currentIndex' => 0,
        'customVariables' => json_decode($row['custom_variables'] ?: '[]', true) ?: [],
        'recipients' => recipients_load((int) $row['id']),
        'logs' => $withLogs ? logs_load((int) $row['id']) : [],
    ];
}

/**
 * Update scalar campaign fields. Keys use the camelCase names:
 * name, companyName, festival, subject, htmlTemplate, discountCode, intervalMinutes, status, remainingSeconds, nextSendAt, customVariables
 */
function campaign_update(int $campaignId, array $patch): void
{
    $map = [
        'name' => 'name', 'companyName' => 'company_name', 'festival' => 'festival', 'subject' => 'subject',
        'htmlTemplate' => 'html_template', 'discountCode' => 'discount_code', 'intervalMinutes' => 'interval_minutes',
        'status' => 'status', 'remainingSeconds' => 'remaining_seconds', 'nextSendAt' => 'next_send_at',
        'customVariables' => 'custom_variables',
    ];
    $sets = [];
    $values = [];
    foreach ($patch as $key => $value) {
        if (!isset($map[$key])) {
            throw new InvalidArgumentException("Unknown campaign field: $key");
        }
        if ($key === 'customVariables') {
            $value = json_encode(array_values(array_unique($value)), JSON_UNESCAPED_UNICODE);
        }
        $sets[] = $map[$key] . ' = ?';
        $values[] = $value;
    }
    if (!$sets) {
        return;
    }
    $sets[] = 'updated_at = ?';
    $values[] = now_sql();
    $values[] = $campaignId;
    db()->prepare('UPDATE campaigns SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($values);
}

/* ---------------------------------------------------------------------------
 * Recipients storage
 * ------------------------------------------------------------------------- */

function recipient_from_row(array $r): array
{
    return [
        'id' => $r['rid'],
        'name' => $r['name'],
        'email' => $r['email'],
        'customData' => $r['custom_data'] ? json_decode($r['custom_data'], true) : null,
        'status' => $r['status'],
        'sentAt' => $r['sent_at'],
        'messageId' => $r['message_id'],
        'errorMessage' => $r['error_message'],
        'retryCount' => (int) $r['retry_count'],
    ];
}

function recipients_load(int $campaignId): array
{
    $stmt = db()->prepare('SELECT * FROM recipients WHERE campaign_id = ? ORDER BY position, id');
    $stmt->execute([$campaignId]);
    return array_map('recipient_from_row', $stmt->fetchAll());
}

/** Replace the whole recipient list (order preserved). Use for bulk edits, imports, deletes. */
function recipients_replace(int $campaignId, array $recipients): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM recipients WHERE campaign_id = ?')->execute([$campaignId]);
        recipients_insert($campaignId, $recipients, 0);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Append recipients to the end of the list. */
function recipients_append(int $campaignId, array $recipients): void
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM recipients WHERE campaign_id = ?');
    $stmt->execute([$campaignId]);
    recipients_insert($campaignId, $recipients, (int) $stmt->fetchColumn());
}

function recipients_insert(int $campaignId, array $recipients, int $startPosition): void
{
    $stmt = db()->prepare('INSERT INTO recipients (campaign_id, rid, position, name, email, custom_data, status, sent_at, message_id, error_message, retry_count)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), custom_data = VALUES(custom_data)');
    $pos = $startPosition;
    foreach ($recipients as $r) {
        $cd = $r['customData'] ?? null;
        $stmt->execute([
            $campaignId,
            (string) ($r['id'] ?? new_recipient('', '')['id']),
            $pos++,
            (string) ($r['name'] ?? ''),
            (string) $r['email'],
            $cd ? json_encode($cd, JSON_UNESCAPED_UNICODE) : null,
            $r['status'] ?? 'pending',
            $r['sentAt'] ?? null,
            $r['messageId'] ?? null,
            $r['errorMessage'] ?? null,
            (int) ($r['retryCount'] ?? 0),
        ]);
    }
}

/** Update fields of one recipient by its id: name, email, customData, status, sentAt, messageId, errorMessage, retryCount */
function recipient_update(int $campaignId, string $rid, array $patch): void
{
    $map = ['name' => 'name', 'email' => 'email', 'customData' => 'custom_data', 'status' => 'status', 'sentAt' => 'sent_at',
        'messageId' => 'message_id', 'errorMessage' => 'error_message', 'retryCount' => 'retry_count'];
    $sets = [];
    $values = [];
    foreach ($patch as $k => $v) {
        if (!isset($map[$k])) {
            throw new InvalidArgumentException("Unknown recipient field: $k");
        }
        if ($k === 'customData') {
            $v = $v ? json_encode($v, JSON_UNESCAPED_UNICODE) : null;
        }
        $sets[] = $map[$k] . ' = ?';
        $values[] = $v;
    }
    if (!$sets) {
        return;
    }
    $values[] = $campaignId;
    $values[] = $rid;
    db()->prepare('UPDATE recipients SET ' . implode(', ', $sets) . ' WHERE campaign_id = ? AND rid = ?')->execute($values);
}

/** Delete recipients by id list. */
function recipients_delete(int $campaignId, array $rids): void
{
    if (!$rids) {
        return;
    }
    $in = implode(',', array_fill(0, count($rids), '?'));
    db()->prepare("DELETE FROM recipients WHERE campaign_id = ? AND rid IN ($in)")->execute(array_merge([$campaignId], array_values($rids)));
}

function recipient_find(array $campaign, string $rid): ?array
{
    foreach ($campaign['recipients'] as $r) {
        if ($r['id'] === $rid) {
            return $r;
        }
    }
    return null;
}

/* ---------------------------------------------------------------------------
 * Logs storage
 * ------------------------------------------------------------------------- */

function logs_load(int $campaignId): array
{
    $stmt = db()->prepare('SELECT * FROM dispatch_logs WHERE campaign_id = ? ORDER BY id DESC');
    $stmt->execute([$campaignId]);
    return array_map(fn ($l) => [
        'id' => $l['lid'],
        'timestamp' => $l['logged_at'],
        'senderEmail' => $l['sender_email'],
        'recipientEmail' => $l['recipient_email'],
        'recipientName' => $l['recipient_name'],
        'subject' => $l['subject'],
        'status' => $l['status'],
        'messageId' => $l['message_id'],
        'detail' => $l['detail'],
        'mode' => $l['mode'],
    ], $stmt->fetchAll());
}

function log_add(int $campaignId, array $log): void
{
    db()->prepare('INSERT INTO dispatch_logs (campaign_id, lid, logged_at, sender_email, recipient_email, recipient_name, subject, status, message_id, detail, mode)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$campaignId, $log['id'], $log['timestamp'], $log['senderEmail'] ?? null, $log['recipientEmail'], $log['recipientName'] ?? '',
            $log['subject'] ?? '', $log['status'], $log['messageId'] ?? null, $log['detail'] ?? null, $log['mode']]);
}

function logs_clear(int $campaignId): void
{
    db()->prepare('DELETE FROM dispatch_logs WHERE campaign_id = ?')->execute([$campaignId]);
}

/* ---------------------------------------------------------------------------
 * SMTP settings (per user, password encrypted at rest)
 * ------------------------------------------------------------------------- */

function smtp_defaults(): array
{
    return [
        'enabled' => true,
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => false,
        'username' => '',
        'password' => '',
        'fromName' => 'MailDart Campaigns',
        'fromEmail' => '',
        'replyTo' => '',
    ];
}

function smtp_load(int $userId): array
{
    $stmt = db()->prepare('SELECT config_json, password_enc FROM user_smtp_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return smtp_defaults();
    }
    $cfg = array_merge(smtp_defaults(), json_decode($row['config_json'], true) ?: []);
    $cfg['password'] = decrypt_secret($row['password_enc']);
    $cfg['host'] = $cfg['host'] ?: 'smtp.gmail.com';
    $cfg['port'] = (int) ($cfg['port'] ?: 587);
    // 587 uses STARTTLS; SSL must stay off (only 465 uses direct SSL)
    $cfg['secure'] = $cfg['port'] === 465;
    $cfg['enabled'] = (bool) $cfg['enabled'];
    return $cfg;
}

function smtp_save(int $userId, array $config): void
{
    $cfg = array_intersect_key(array_merge(smtp_defaults(), $config), smtp_defaults());
    $cfg['port'] = (int) $cfg['port'];
    $cfg['enabled'] = (bool) $cfg['enabled'];
    $cfg['secure'] = (bool) $cfg['secure'];
    $password = (string) $cfg['password'];
    unset($cfg['password']);
    db()->prepare('INSERT INTO user_smtp_settings (user_id, config_json, password_enc, updated_at) VALUES (?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE config_json = VALUES(config_json), password_enc = VALUES(password_enc), updated_at = VALUES(updated_at)')
        ->execute([$userId, json_encode($cfg, JSON_UNESCAPED_UNICODE), encrypt_secret($password), now_sql()]);
}

/* ---------------------------------------------------------------------------
 * Sending (was POST /api/send-email in server.ts)
 * ------------------------------------------------------------------------- */

/**
 * Send (or simulate) one email. Same response shape as the old endpoint:
 *   success, mode (smtp|simulated), sender, messageId, response?, timestamp, message | error
 * $msg: to, subject, html, fromName?, fromEmail?, replyTo?
 */
function send_email_now(array $msg, ?array $smtp, bool $simulate = false): array
{
    $user = $smtp['username'] ?? '';
    $pass = $smtp['password'] ?? '';
    $hasRealSmtp = !$simulate && $smtp && ($smtp['enabled'] ?? true) !== false && !empty($smtp['host']) && $user !== '' && $pass !== '';

    if ($hasRealSmtp) {
        $fromEmail = ($msg['fromEmail'] ?? '') ?: (($smtp['fromEmail'] ?? '') ?: $user);
        $fromName = ($msg['fromName'] ?? '') ?: (($smtp['fromName'] ?? '') ?: 'Acme Celebrations');
        try {
            $info = smtp_send(
                ['host' => $smtp['host'], 'port' => $smtp['port'] ?? 587, 'secure' => !empty($smtp['secure']), 'user' => $user, 'pass' => $pass],
                ['to' => $msg['to'], 'subject' => $msg['subject'], 'html' => $msg['html'], 'fromEmail' => $fromEmail, 'fromName' => $fromName,
                    'replyTo' => ($msg['replyTo'] ?? '') ?: $fromEmail]
            );
        } catch (SmtpFailure $e) {
            return ['success' => false, 'mode' => 'smtp', 'error' => $e->getMessage()];
        }
        return [
            'success' => true,
            'mode' => 'smtp',
            'sender' => $fromEmail,
            'messageId' => $info['messageId'],
            'response' => $info['response'],
            'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
            'message' => "Email dispatched successfully from {$fromEmail} via {$smtp['host']}!",
        ];
    }

    // Simulated / sandbox delivery mode (safe testing mode with full log)
    return [
        'success' => true,
        'mode' => 'simulated',
        'sender' => ($msg['fromEmail'] ?? '') ?: (($smtp['fromEmail'] ?? '') ?: 'sandbox@festivamail.internal'),
        'messageId' => '<camp-' . (int) (microtime(true) * 1000) . '-' . substr(bin2hex(random_bytes(4)), 0, 7) . '@festivamail.internal>',
        'message' => 'Email delivered in simulation/sandbox mode (Valid HTML & recipients processed).',
        'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
    ];
}

/* ---------------------------------------------------------------------------
 * Dispatcher (was the timer + dispatchRecipient in App.tsx)
 * ------------------------------------------------------------------------- */

/** Personalise, send and log one recipient; updates recipient + campaign state. Returns the send result. */
function dispatch_recipient(array $campaign, array $recipient, array $smtp): array
{
    $cid = $campaign['id'];
    recipient_update($cid, $recipient['id'], ['status' => 'sending']);

    $subject = replace_placeholders($campaign['subject'], $recipient, $campaign);
    $html = replace_placeholders($campaign['htmlTemplate'], $recipient, $campaign);
    $data = send_email_now([
        'to' => $recipient['email'],
        'subject' => $subject,
        'html' => $html,
        'fromName' => ($smtp['fromName'] ?? '') ?: $campaign['companyName'],
        'fromEmail' => $smtp['fromEmail'] ?? '',
        'replyTo' => $smtp['replyTo'] ?? '',
    ], !empty($smtp['enabled']) ? $smtp : null, empty($smtp['enabled']));

    $ok = !empty($data['success']);
    $now = gmdate('Y-m-d\TH:i:s.v\Z');
    $messageId = ($data['messageId'] ?? '') ?: 'msg-' . (int) (microtime(true) * 1000);
    $sender = ($data['sender'] ?? '') ?: (($smtp['fromEmail'] ?? '') ?: (($smtp['username'] ?? '') ?: 'Current User'));

    log_add($cid, [
        'id' => 'log-' . (int) (microtime(true) * 1000) . '-' . substr(bin2hex(random_bytes(2)), 0, 4),
        'timestamp' => $now,
        'senderEmail' => $sender,
        'recipientEmail' => $recipient['email'],
        'recipientName' => $recipient['name'],
        'subject' => $subject,
        'status' => $ok ? 'delivered' : 'failed',
        'messageId' => $messageId,
        'mode' => ($data['mode'] ?? '') === 'smtp' ? 'smtp' : 'simulated',
        'detail' => ($data['message'] ?? '') ?: ($ok ? "Sent from $sender" : ($data['error'] ?? 'Failed')),
    ]);

    recipient_update($cid, $recipient['id'], [
        'status' => $ok ? 'delivered' : 'failed',
        'sentAt' => $now,
        'messageId' => $messageId,
        'errorMessage' => $ok ? null : ($data['error'] ?? null),
    ]);

    // All done? -> completed. Otherwise restart the countdown for the next recipient.
    $stmt = db()->prepare("SELECT COUNT(*) FROM recipients WHERE campaign_id = ? AND status = 'pending'");
    $stmt->execute([$cid]);
    $finished = (int) $stmt->fetchColumn() === 0;
    $row = campaign_row($campaign['userId']);
    $interval = (int) round((float) $row['interval_minutes'] * 60);

    if ($finished) {
        campaign_update($cid, ['status' => 'completed', 'remainingSeconds' => 0, 'nextSendAt' => null]);
    } elseif ($row['status'] === 'running') {
        campaign_update($cid, ['remainingSeconds' => $interval, 'nextSendAt' => date('Y-m-d H:i:s', time() + $interval)]);
    } else {
        campaign_update($cid, ['remainingSeconds' => $interval]);
    }

    $data['finished'] = $finished;
    return $data;
}

function first_pending(array $campaign): ?array
{
    foreach ($campaign['recipients'] as $r) {
        if ($r['status'] === 'pending') {
            return $r;
        }
    }
    return null;
}

/**
 * Called by the countdown (api/dispatch-tick.php) and by cron (cron/dispatch.php).
 * If the campaign is running and its countdown has reached zero, atomically claims the slot and sends the next recipient.
 * Returns ['sent' => bool, 'status' => ..., 'remainingSeconds' => ..., 'result' => send result|null]
 */
function dispatch_tick(int $userId): array
{
    $row = campaign_row($userId);
    $result = null;
    if ($row['status'] === 'running' && $row['next_send_at'] && strtotime($row['next_send_at']) <= time()) {
        $interval = (int) round((float) $row['interval_minutes'] * 60);
        // Claim this slot so parallel ticks (two tabs, cron) never double-send
        $claim = db()->prepare("UPDATE campaigns SET next_send_at = ? WHERE id = ? AND status = 'running' AND next_send_at = ?");
        $claim->execute([date('Y-m-d H:i:s', time() + $interval), $row['id'], $row['next_send_at']]);
        if ($claim->rowCount() === 1) {
            $campaign = campaign_load($userId, false);
            $next = first_pending($campaign);
            if ($next) {
                $result = dispatch_recipient($campaign, $next, smtp_load($userId));
            } else {
                campaign_update((int) $row['id'], ['status' => 'completed', 'remainingSeconds' => 0, 'nextSendAt' => null]);
            }
        }
    }
    $campaign = campaign_load($userId, false);
    return ['sent' => $result !== null, 'status' => $campaign['status'], 'remainingSeconds' => $campaign['remainingSeconds'], 'result' => $result];
}

/** Start / resume. Returns an error message or null. */
function campaign_start(array $campaign): ?string
{
    if (!first_pending($campaign)) {
        return 'All recipients have already been sent! Please reset campaign or add more recipients.';
    }
    $remaining = $campaign['remainingSeconds'] > 0 ? $campaign['remainingSeconds'] : $campaign['intervalSeconds'];
    campaign_update($campaign['id'], ['status' => 'running', 'remainingSeconds' => $remaining, 'nextSendAt' => date('Y-m-d H:i:s', time() + $remaining)]);
    return null;
}

function campaign_pause(array $campaign): void
{
    campaign_update($campaign['id'], ['status' => 'paused', 'remainingSeconds' => $campaign['remainingSeconds'], 'nextSendAt' => null]);
}

/** Reset all recipients back to pending so they can be re-sent. */
function campaign_reset(array $campaign): void
{
    db()->prepare("UPDATE recipients SET status = 'pending', sent_at = NULL, message_id = NULL, error_message = NULL WHERE campaign_id = ?")
        ->execute([$campaign['id']]);
    campaign_update($campaign['id'], ['status' => 'idle', 'remainingSeconds' => $campaign['intervalSeconds'], 'nextSendAt' => null]);
}

function campaign_set_interval(array $campaign, float $minutes): void
{
    $seconds = (int) round($minutes * 60);
    $patch = ['intervalMinutes' => $minutes, 'remainingSeconds' => $seconds];
    if ($campaign['status'] === 'running') {
        $patch['nextSendAt'] = date('Y-m-d H:i:s', time() + $seconds);
    }
    campaign_update($campaign['id'], $patch);
}

/* ---------------------------------------------------------------------------
 * Excel import merge (was handleImportExcelData in App.tsx)
 * ------------------------------------------------------------------------- */

/**
 * Add imported recipients (append or replace), register discovered variables and, when requested,
 * append a "Your Personalised Details" block for custom variables missing from the template.
 * Also queues the Excel import banner shown at the top of the dashboard.
 */
function campaign_import_excel(array $campaign, array $recipients, bool $replace, array $variables, bool $autoInsertBlock = true): void
{
    $template = $campaign['htmlTemplate'];
    $standard = ['name', 'email', 'company', 'discount', 'festival'];
    $custom = array_values(array_filter($variables, fn ($v) => !in_array($v, $standard, true)));

    if ($autoInsertBlock && $custom) {
        $missing = array_values(array_filter($custom, fn ($v) => !str_contains(strtolower($template), '{{' . strtolower($v) . '}}')));
        if ($missing) {
            $rows = implode("\n", array_map(
                fn ($v) => '    <p style="margin: 4px 0; font-size: 13px; color: #cbd5e1;"><strong style="color: #ffffff; text-transform: capitalize;">' . str_replace('_', ' ', $v) . ':</strong> {{' . $v . '}}</p>',
                $missing
            ));
            $block = "\n<!-- Auto-Generated Excel Variables Section -->\n<div style=\"margin: 20px 0; padding: 14px 18px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(26, 61, 99, 0.35); border-left: 4px solid #1a3d63; border-radius: 8px;\">\n  <p style=\"margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #1a3d63;\">✨ Your Personalised Details:</p>\n{$rows}\n</div>\n";
            $pos = strpos($template, '</body>');
            $template = $pos !== false ? substr_replace($template, "{$block}\n</body>", $pos, 7) : $template . $block;
        }
    }

    if ($replace) {
        recipients_replace($campaign['id'], $recipients);
    } else {
        recipients_append($campaign['id'], $recipients);
    }
    campaign_update($campaign['id'], [
        'customVariables' => array_values(array_unique(array_merge($campaign['customVariables'], $variables))),
        'htmlTemplate' => $template,
    ]);

    md_session_start();
    $_SESSION['excel_toast'] = [
        'message' => count($recipients) . ' recipients and ' . count($variables) . ' variables imported from your Excel sheet!',
        'variables' => array_values($variables),
    ];
}
