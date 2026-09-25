<?php
/**
 * POST api/preview.php
 * Live preview for the Template Studio editor (assets/js/editor.js), debounced while typing in the subject
 * or HTML fields. Body: {subject, html, recipientId}. Returns the same fields personalised for that recipient
 * using replace_placeholders() — the same function the server-rendered initial preview uses. Company / discount
 * / festival come from the campaign's last SAVED state (unsaved edits to those small fields are not sent here).
 */

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

api(function () {
    require_method('POST');
    $body = request_json();
    $user = require_login();

    $campaign = campaign_load((int) $user['id'], false);

    $recipientId = (string) ($body['recipientId'] ?? '');
    $recipient = recipient_find($campaign, $recipientId) ?? ($campaign['recipients'][0] ?? [
        'id' => 'preview-default',
        'name' => 'Aarav Sharma',
        'email' => 'aarav.sharma@example.com',
        'customData' => ['city' => 'Mumbai', 'gift' => 'Royal Sweets Hamper'],
        'status' => 'pending',
    ]);

    $subject = (string) ($body['subject'] ?? '');
    $html = (string) ($body['html'] ?? '');

    json_response([
        'success' => true,
        'subject' => replace_placeholders($subject, $recipient, $campaign),
        'html' => replace_placeholders($html, $recipient, $campaign),
    ]);
});
