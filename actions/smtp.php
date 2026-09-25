<?php
/**
 * SMTP settings actions (was src/components/SmtpSettingsPage.tsx + the SmtpSettingsPage handlers in
 * src/App.tsx, and the "Send Single Test Email" modal, src/components/TestEmailModal.tsx).
 *
 * The whole Settings form (Sender info + SMTP credentials) is ONE <form>; every button in it (Save,
 * Test Connection, Send Live Test, the mode switch cards, the Gmail/Elastic help toggles, the quick-setup
 * presets) is a submit button that overrides the form's default "action" hidden field with its own
 * name="action" value="..." (the browser sends whichever one was actually clicked). That way every
 * button always posts the *whole* current form state, so nothing the user typed is ever lost, and
 * everything keeps working with JavaScript switched off.
 *
 * Fields shared by every handler below (same names as the <input>s in views/pages/settings.php):
 *   enabled, host, port, secure, username, password, fromName, fromEmail, replyTo, help
 */

/** Build the SmtpConfig array smtp_save() expects from the posted form fields. */
function smtp_config_from_post(): array
{
    return [
        'enabled' => input_bool('enabled'),
        'host' => input('host') ?: 'smtp.gmail.com',
        'port' => (int) (input('port') ?: 587),
        'secure' => input_bool('secure'),
        'username' => input('username'),
        'password' => input_raw('password'),
        'fromName' => input('fromName') ?: 'MailDart Campaigns',
        'fromEmail' => input('fromEmail') ?: input('username'),
        'replyTo' => input('replyTo'),
    ];
}

// Everything below runs inside an isolated closure: index.php require()s this file directly into its own
// top-level scope (not inside a function), so any bare $variable assigned here would silently overwrite
// index.php's own same-named variable of the same name. index.php sets $name (the action to run) right
// before doing that require and reads it again immediately after to know which handler to call — so this
// file must never leave a stray top-level $name (or similar) lying around after it runs.
return (function (): array {
    $handlers = [
        // "Save Settings"
        'save' => function (array $user): void {
            smtp_save((int) $user['id'], smtp_config_from_post());
            flash('success', 'Settings saved.');
            redirect_back('settings');
        },

        // Mode switcher cards ("Real SMTP" / "Sandbox / Simulation Mode") — local, unsaved until "Save Settings"
        'setModeLive' => function (array $user): void {
            $_POST['enabled'] = '1';
            keep_old_input(['_csrf']);
            redirect_back('settings');
        },
        'setModeSandbox' => function (array $user): void {
            $_POST['enabled'] = '0';
            keep_old_input(['_csrf']);
            redirect_back('settings');
        },

        // Gmail / Elastic Email step-by-step guide toggles (mutually exclusive, like the TSX state)
        'setHelpGmail' => function (array $user): void {
            $_POST['help'] = 'gmail';
            keep_old_input(['_csrf']);
            redirect_back('settings');
        },
        'setHelpElastic' => function (array $user): void {
            $_POST['help'] = 'elastic';
            keep_old_input(['_csrf']);
            redirect_back('settings');
        },
        'setHelpNone' => function (array $user): void {
            $_POST['help'] = '';
            keep_old_input(['_csrf']);
            redirect_back('settings');
        },

        // "Test Connection" (was POST /api/verify-smtp)
        'verify' => function (array $user): void {
            keep_old_input(['_csrf']);
            $host = input('host');
            $username = input('username');
            $password = input_raw('password');
            if ($host === '' || $username === '' || $password === '') {
                flash_data('smtp_result', ['success' => false, 'message' => 'Please fill in the Host, Username (Email) and Password / App Key.']);
                redirect_back('settings');
            }
            try {
                smtp_verify(['host' => $host, 'port' => (int) (input('port') ?: 587), 'secure' => input_bool('secure'), 'user' => $username, 'pass' => $password]);
                flash_data('smtp_result', ['success' => true, 'message' => "SMTP connection verified successfully! Emails will be sent from {$username}."]);
            } catch (SmtpFailure $e) {
                flash_data('smtp_result', ['success' => false, 'message' => $e->getMessage()]);
            }
            redirect_back('settings');
        },

        // "Send Live Test to My Email" (was handleSendLiveTest, POST /api/send-email with a canned message)
        'sendLiveTest' => function (array $user): void {
            keep_old_input(['_csrf']);
            $username = input('username');
            $fromEmailPosted = input('fromEmail');
            $targetEmail = $username ?: $fromEmailPosted;
            if ($targetEmail === '' || !str_contains($targetEmail, '@')) {
                flash_data('smtp_result', ['success' => false, 'message' => 'Please enter a valid Username / Sender Email first.']);
                redirect_back('settings');
            }
            $password = input_raw('password');
            if ($password === '') {
                flash_data('smtp_result', ['success' => false, 'message' => 'Please enter the SMTP Password / App Password.']);
                redirect_back('settings');
            }

            $host = input('host') ?: 'smtp.gmail.com';
            $fromName = input('fromName') ?: 'MailDart Tester';
            $fromEmail = $fromEmailPosted ?: $targetEmail;
            $smtpOpts = [
                'enabled' => true,
                'host' => $host,
                'port' => (int) (input('port') ?: 587),
                'secure' => input_bool('secure'),
                'username' => $username,
                'password' => $password,
                'fromName' => $fromName,
                'fromEmail' => $fromEmail,
            ];
            $html = '<div style="font-family: sans-serif; padding: 20px; border: 1px solid #1a3d63; border-radius: 8px;">'
                . '<h2 style="color: #1a3d63;">SMTP Verification Successful!</h2>'
                . '<p>This test email confirms that emails are being sent successfully from <strong>' . e($targetEmail) . '</strong> through the SMTP server <strong>' . e($host) . '</strong>.</p>'
                . '<p style="color: #666; font-size: 12px;">Sent via MailDart Staggered Dispatcher on ' . e(date('n/j/Y, g:i:s A')) . '</p></div>';

            $result = send_email_now([
                'to' => $targetEmail,
                'subject' => "\u{2705} SMTP Test: Verified Dispatch from {$targetEmail}",
                'html' => $html,
                'fromName' => $fromName,
                'fromEmail' => $fromEmail,
            ], $smtpOpts, false);

            if (!empty($result['success'])) {
                flash_data('smtp_result', ['success' => true, 'message' => "Great! The test email has been sent to {$targetEmail}. (Message ID: " . ($result['messageId'] ?: 'sent') . ')']);
            } else {
                flash_data('smtp_result', ['success' => false, 'message' => $result['error'] ?? 'Could not send the test email. Please check your credentials.']);
            }
            redirect_back('settings');
        },

        // Test Email modal ("Send Single Test Email", was TestEmailModal.tsx -> POST /api/send-email)
        'sendTest' => function (array $user): void {
            keep_old_input(['_csrf']);
            $testEmail = input('testEmail');
            $testName = input('testName') ?: 'Campaign Reviewer';
            if ($testEmail === '' || !str_contains($testEmail, '@')) {
                flash_data('test_email_result', ['success' => false, 'message' => 'Please enter a valid test recipient email.']);
                redirect_back('dispatch');
            }

            $campaign = campaign_load((int) $user['id'], false);
            $smtp = smtp_load((int) $user['id']);
            $recipient = ['name' => $testName, 'email' => $testEmail];
            $subject = '[TEST] ' . replace_placeholders($campaign['subject'], $recipient, $campaign);
            $html = replace_placeholders($campaign['htmlTemplate'], $recipient, $campaign);

            $result = send_email_now([
                'to' => $testEmail,
                'subject' => $subject,
                'html' => $html,
                'fromName' => $smtp['fromName'] ?: $campaign['companyName'],
                'fromEmail' => $smtp['fromEmail'],
                'replyTo' => $smtp['replyTo'],
            ], !empty($smtp['enabled']) ? $smtp : null, empty($smtp['enabled']));

            if (!empty($result['success'])) {
                $message = !empty($smtp['enabled'])
                    ? "Real test email delivered to {$testEmail}! (ID: {$result['messageId']})"
                    : "Delivered in simulation/sandbox mode to {$testEmail}! (Valid HTML parsed & verified)";
                flash_data('test_email_result', ['success' => true, 'message' => $message]);
            } else {
                flash_data('test_email_result', ['success' => false, 'message' => $result['error'] ?? 'Failed to dispatch test email.']);
            }
            redirect_back('dispatch');
        },
    ];

    // Quick Setup presets ("Gmail (Default)" / "Elastic Email" / "Brevo" / "SendGrid" / "AWS SES").
    // These persist immediately (smtp_save), matching the guide's "post to an action that updates the
    // stored config" option, since they submit the *whole* settings form (host/port only change; whatever
    // username/password/etc. the user already typed is preserved and saved along with them).
    $presets = [
        'applyPresetGmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'help' => 'gmail'],
        'applyPresetElastic' => ['host' => 'smtp.elasticemail.com', 'port' => 2525, 'help' => 'elastic'],
        'applyPresetBrevo' => ['host' => 'smtp-relay.brevo.com', 'port' => 587, 'help' => ''],
        'applyPresetSendgrid' => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'help' => ''],
        'applyPresetSes' => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'help' => ''],
    ];
    foreach ($presets as $presetKey => $preset) {
        $handlers[$presetKey] = function (array $user) use ($preset): void {
            $config = smtp_config_from_post();
            $config['enabled'] = true;
            $config['host'] = $preset['host'];
            $config['port'] = $preset['port'];
            $config['secure'] = $preset['port'] === 465;
            smtp_save((int) $user['id'], $config);

            $_POST['enabled'] = '1';
            $_POST['host'] = $preset['host'];
            $_POST['port'] = (string) $preset['port'];
            $_POST['secure'] = $preset['port'] === 465 ? '1' : '';
            $_POST['help'] = $preset['help'];
            keep_old_input(['_csrf']);
            redirect_back('settings');
        };
    }

    return $handlers;
})();
