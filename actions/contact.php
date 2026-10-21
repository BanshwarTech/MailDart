<?php
/** Public contact form actions. */

return [
    'submit' => function (?array $user): void {
        $name = mb_substr(trim(input('name')), 0, 120);
        $email = trim(input('email'));
        $topic = input('topic');
        $message = trim(input('message'));
        $topics = ['General question', 'Account access', 'Technical support', 'Privacy request', 'Billing or payment'];

        // Silently accept the bot's submission without creating a record or sending email.
        if (input('company_website') !== '') {
            flash('success', 'Thanks. Your message has been received.');
            redirect('contact');
        }
        if ($name === '' || strlen($name) > 480 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191
            || !in_array($topic, $topics, true) || $message === '' || mb_strlen($message) > 10000) {
            flash('error', 'Please check your name, email, topic and message, then try again.');
            redirect('contact');
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $ipHash = hash_hmac('sha256', $ip, app_key());
        $rate = db()->prepare('SELECT COUNT(*) FROM contact_submissions WHERE ip_hash = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $rate->execute([$ipHash]);
        if ((int) $rate->fetchColumn() >= 5) {
            flash('error', 'Too many messages were sent from this connection. Please try again later.');
            redirect('contact');
        }

        $data = ['name' => $name, 'email' => $email, 'topic' => $topic, 'message' => $message];
        $id = contact_submission_create($data, $ipHash);
        try {
            contact_submission_send($data);
            contact_submission_mail_status($id, 'sent');
            flash('success', 'Thanks. Your message has been sent to the MailDart team.');
        } catch (Throwable $e) {
            contact_submission_mail_status($id, 'failed', $e->getMessage());
            error_log('MailDart contact email failed for submission ' . $id . ': ' . $e->getMessage());
            flash('warning', 'Your message was saved for the MailDart team, but the email notification could not be sent right now.');
        }
        redirect('contact');
    },
];
