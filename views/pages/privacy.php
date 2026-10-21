<?php
$legalTitle = 'Privacy Policy';
$legalIntro = 'This policy explains how MailDart Pro handles information when you visit the site or use its email-campaign service. It covers account and campaign data, payment processing, AI features and your privacy choices.';
$legalSections = [
    ['heading' => 'Information we handle', 'paragraphs' => ['Depending on the features you use, MailDart may process:'], 'items' => [
        'Account details such as your name, email address and authentication/session information.',
        'Campaign content, recipient names and email addresses, custom fields, templates, send status and delivery logs that you enter or upload.',
        'SMTP server details, usernames and sender settings. SMTP passwords are encrypted before they are stored; do not share your account credentials.',
        'Plan, order and payment status. Razorpay handles payment credentials; MailDart does not store full card numbers or UPI credentials.',
        'Basic technical and security information needed to operate the site, prevent abuse and diagnose errors.',
    ]],
    ['heading' => 'How we use it', 'paragraphs' => ['We use information to create and secure your account, run campaigns you initiate, show campaign and payment history, enforce plan limits, provide support, prevent fraud or abuse, and maintain the service. We do not sell your personal information.']],
    ['heading' => 'Email delivery and AI providers', 'paragraphs' => ['When you send a campaign, recipient data and message content are transmitted through the SMTP provider you configured. That provider processes them under its own terms and privacy practices. When you explicitly use AI template generation, the prompt and information you provide for that generation are sent to the configured AI provider (Google Gemini or NVIDIA). Avoid including sensitive or unnecessary personal data in AI prompts. Razorpay processes payments and related payment details. These providers may process information under their own policies and in locations outside your country.']],
    ['heading' => 'Cookies and storage', 'paragraphs' => ['MailDart uses a necessary session cookie to keep you signed in and protect account actions. The service also stores the campaign and account information needed to provide the features you request. We do not use advertising cookies on the public site.']],
    ['heading' => 'Retention, security and deletion', 'paragraphs' => ['We keep account and campaign data while your account is active and for a reasonable period afterwards where needed for security, dispute handling, backups or legal obligations. You can request correction or deletion of your account information using the contact below. Some records may need to be retained where the law requires it. SMTP passwords are encrypted at rest; no internet service can promise absolute security.']],
    ['heading' => 'Your responsibilities and choices', 'paragraphs' => ['Only upload recipient information you are authorized to use. You are responsible for providing recipients with any notices and choices required by applicable privacy and electronic-marketing laws. You may ask us to access, correct or delete personal information associated with your account, subject to legal and security requirements.']],
    ['heading' => 'Changes to this policy', 'paragraphs' => ['We may update this policy as the service or applicable requirements change. The effective date above shows when this version was published. Material changes will be posted on this page.']],
];
partial('legal_document', compact('legalTitle', 'legalIntro', 'legalSections', 'currentUser'));
