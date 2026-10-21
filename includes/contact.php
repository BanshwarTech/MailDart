<?php
/** Public contact submissions and admin-managed SMTP delivery. */

function contact_mail_settings(): array
{
    $stored = json_decode((string) app_setting('contact_smtp', '{}'), true) ?: [];
    $passwordEnc = (string) ($stored['password_enc'] ?? '');
    $password = decrypt_secret($passwordEnc);
    return [
        'host' => (string) ($stored['host'] ?? ''),
        'port' => (int) ($stored['port'] ?? 587),
        'username' => (string) ($stored['username'] ?? ''),
        'password' => $password,
        'password_set' => $password !== '',
        'from_name' => (string) ($stored['from_name'] ?? 'MailDart Pro'),
        'from_email' => (string) ($stored['from_email'] ?? ''),
        'recipient' => (string) ($stored['recipient'] ?? ''),
    ];
}

/** Validate SMTP details from the admin form; a blank password keeps the saved credential. */
function contact_mail_settings_from_post(): array
{
    $old = contact_mail_settings();
    $host = trim(input('host'));
    $port = (int) input('port', '587');
    $username = trim(input('username'));
    $password = input_raw('password');
    $fromName = trim(input('from_name')) ?: 'MailDart Pro';
    $fromEmail = trim(input('from_email')) ?: $username;
    $recipient = trim(input('recipient'));

    if ($host === '' || strlen($host) > 253) {
        throw new RuntimeException('Enter a valid SMTP host.');
    }
    if (!in_array($port, [465, 587], true)) {
        throw new RuntimeException('Use SMTP port 465 (SSL) or 587 (STARTTLS).');
    }
    if ($username === '' || ($password === '' && !$old['password_set'])) {
        throw new RuntimeException('Enter the SMTP username and password.');
    }
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter valid recipient and sender email addresses.');
    }
    if (strlen($fromName) > 120 || strlen($username) > 191 || strlen($recipient) > 191) {
        throw new RuntimeException('One or more email settings are too long.');
    }

    return [
        'host' => $host,
        'port' => $port,
        'username' => $username,
        'password' => $password !== '' ? $password : $old['password'],
        'password_set' => true,
        'from_name' => $fromName,
        'from_email' => $fromEmail,
        'recipient' => $recipient,
    ];
}

function contact_mail_save_settings(array $settings): void
{
    app_setting_set('contact_smtp', json_encode([
        'host' => $settings['host'],
        'port' => (int) $settings['port'],
        'username' => $settings['username'],
        'password_enc' => encrypt_secret($settings['password']),
        'from_name' => $settings['from_name'],
        'from_email' => $settings['from_email'],
        'recipient' => $settings['recipient'],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}

function contact_mail_is_configured(): bool
{
    $settings = contact_mail_settings();
    return $settings['host'] !== '' && $settings['username'] !== '' && $settings['password_set']
        && filter_var($settings['recipient'], FILTER_VALIDATE_EMAIL)
        && filter_var($settings['from_email'] ?: $settings['username'], FILTER_VALIDATE_EMAIL);
}

/** Try both Zoho-supported ports on connection timeouts and remember the working port. */
function contact_mail_with_port_fallback(array $settings, callable $send)
{
    $primaryPort = (int) $settings['port'];
    $ports = array_values(array_unique([$primaryPort, $primaryPort === 465 ? 587 : 465]));
    $lastTimeout = null;

    foreach ($ports as $port) {
        $smtp = [
            'host' => $settings['host'],
            'port' => $port,
            'secure' => $port === 465,
            'user' => $settings['username'],
            'pass' => $settings['password'],
        ];
        try {
            $result = $send($smtp);
            if ($port !== $primaryPort) {
                $settings['port'] = $port;
                contact_mail_save_settings($settings);
            }
            return $result;
        } catch (SmtpFailure $e) {
            if (!str_contains(strtolower($e->getMessage()), 'connection timed out')) {
                throw $e;
            }
            $lastTimeout = $e;
        }
    }

    if ($lastTimeout) {
        throw new SmtpFailure('Connection timed out on Zoho SMTP ports 587 and 465. Ask your hosting provider to allow outbound SMTP connections, or use an email relay/API supported by your host.', 0, $lastTimeout);
    }
    throw new SmtpFailure('Could not connect to the SMTP server. Check the SMTP host and your hosting provider firewall.');
}

function contact_submission_create(array $data, string $ipHash): int
{
    $stmt = db()->prepare('INSERT INTO contact_submissions (name, email, topic, message, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$data['name'], $data['email'], $data['topic'], $data['message'], $ipHash, now_sql()]);
    return (int) db()->lastInsertId();
}

function contact_submission_mail_status(int $id, string $status, string $error = ''): void
{
    if (!in_array($status, ['sent', 'failed'], true)) {
        throw new InvalidArgumentException('Invalid contact mail status.');
    }
    db()->prepare('UPDATE contact_submissions SET mail_status = ?, mail_error = ? WHERE id = ?')->execute([$status, mb_substr($error, 0, 500), $id]);
}

function contact_submissions_list(int $limit = 100): array
{
    $limit = max(1, min(200, $limit));
    return db()->query("SELECT id, name, email, topic, message, mail_status, mail_error, created_at, read_at FROM contact_submissions ORDER BY created_at DESC, id DESC LIMIT {$limit}")->fetchAll();
}

function contact_submissions_unread_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM contact_submissions WHERE read_at IS NULL')->fetchColumn();
}

function contact_submission_set_read(int $id, bool $read): void
{
    db()->prepare('UPDATE contact_submissions SET read_at = ? WHERE id = ?')->execute([$read ? now_sql() : null, $id]);
}

function contact_submission_delete(int $id): void
{
    db()->prepare('DELETE FROM contact_submissions WHERE id = ?')->execute([$id]);
}

/** Send the message using the site's admin-owned SMTP settings. */
function contact_submission_send(array $data): void
{
    $settings = contact_mail_settings();
    if (!contact_mail_is_configured()) {
        throw new RuntimeException('Contact SMTP is not configured.');
    }
    $body = '<h2>New MailDart contact message</h2>'
        . '<p><strong>Name:</strong> ' . e($data['name']) . '</p>'
        . '<p><strong>Email:</strong> ' . e($data['email']) . '</p>'
        . '<p><strong>Topic:</strong> ' . e($data['topic']) . '</p>'
        . '<p><strong>Message:</strong></p><p>' . nl2br(e($data['message'])) . '</p>';
    contact_mail_with_port_fallback($settings, fn (array $smtp) => smtp_send($smtp, [
        'to' => $settings['recipient'],
        'fromEmail' => $settings['from_email'] ?: $settings['username'],
        'fromName' => $settings['from_name'],
        'replyTo' => $data['email'],
        'subject' => '[MailDart Contact] ' . $data['topic'],
        'html' => $body,
    ]));
}
