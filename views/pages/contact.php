<?php
$legalTitle = 'Contact us';
$legalEyebrow = 'Help & Support';
$legalIntro = 'Need help with MailDart Pro? Send a message using the form below. For account, privacy or billing requests, include enough information for us to find the right record.';
$contactFormEnabled = true;
$legalSections = [
    ['heading' => 'How we can help', 'paragraphs' => ['Contact us about account access, campaign setup, technical issues, privacy requests, or a payment and plan question. We will route your request to the right person.']],
    ['heading' => 'Help us respond faster', 'paragraphs' => ['Please include the email address on your MailDart account and a short description of the issue. For a payment question, include the Razorpay order or payment ID and transaction date.'], 'items' => [
        'Never send your password, SMTP password, full card number or one-time verification code.',
        'If reporting a technical issue, mention the page and the steps that led to it.',
    ]],
];
partial('legal_document', compact('legalTitle', 'legalEyebrow', 'legalIntro', 'legalSections', 'contactFormEnabled', 'currentUser'));
