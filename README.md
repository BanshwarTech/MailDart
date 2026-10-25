# MailDart

## What is MailDart?

MailDart is a web application for businesses and teams that need to prepare and
send email campaigns to their own contacts. Its purpose is to bring the main
campaign workflow into one place: manage recipients, create an email template,
connect an SMTP account, send messages in staggered intervals, and review
delivery results.

MailDart is a campaign-management tool, not an email service provider. You
connect an SMTP account that you own or are authorized to use; that provider
handles email delivery. Use it to contact recipients who have agreed to hear
from you, and follow your provider's sending policies and applicable email
regulations.

## How it works

1. Create an account and configure the SMTP account used to send campaign
   messages.
2. Add your contacts or import them from a CSV/XLSX file.
3. Create a campaign and customize its email content.
4. Start the campaign to send messages at the configured interval.
5. Review delivery logs and campaign progress in the app.

An optional scheduled dispatcher can continue processing due campaign messages
when the app is not open in a browser.

## Features

- Email/password accounts, password reset, and optional Google sign-in
- Campaign editor, recipient management, CSV/XLSX import, and delivery logs
- SMTP settings per account, with saved credentials encrypted at rest
- Starter plans and optional paid plans through Razorpay
- Optional AI template generation, reCAPTCHA, and contact-form email

## Requirements

- PHP 8.1 or later
- MySQL or MariaDB
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `zip`, and
  `simplexml`
- Apache with `mod_rewrite` for the included clean URLs and `.htaccess` rules

The project includes PHPMailer in `lib/PHPMailer`; Composer is not required.

## Local setup with XAMPP

1. Put the project in your XAMPP `htdocs` directory, for example
   `C:\xampp\htdocs\core\MailDart`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Copy `.env.example` to `.env`. The default database settings work with a
   standard local XAMPP installation:

   ```dotenv
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=maildart
   DB_USER=root
   DB_PASS=
   ```

   Change these values if your MySQL/MariaDB credentials differ. The configured
   database user must be allowed to create the database on first run.
4. Open `http://localhost/core/MailDart/` in your browser. On first use, MailDart
   creates the database if necessary and applies the schema migrations
   automatically. No manual SQL import is required.
5. Register an account. The first account is promoted to administrator; later
   accounts receive the default free plan.

The same first-run initialization can be run from the project directory with
PHP CLI:

```powershell
php -r "require 'includes/bootstrap.php'; db(); echo 'Database ready.' . PHP_EOL;"
```

If the database cannot be reached, check that MySQL is running and that the
`DB_*` values in `.env` are correct.

## Configuration

Only the database settings are needed for a basic local installation. The
following integrations are optional; configure their related values in `.env`
when using them:

- **Email sending and password resets:** SMTP credentials in the app for
  campaigns; `MAIL_*` settings for server-generated password-reset and contact
  notification emails.
- **Google sign-in:** `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`; configure
  the OAuth redirect URI as `<APP_URL>google-auth`.
- **AI template generation:** `GEMINI_API_KEY` or `NVIDIA_API_KEY`.
- **reCAPTCHA v3:** `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY`.
- **Razorpay payments:** `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, and
  `RAZORPAY_WEBHOOK_SECRET`; point the webhook to `<APP_URL>api/razorpay-webhook`.
- **Public deployment:** set `APP_URL` to the canonical site URL and set
  `APP_TIMEZONE` as appropriate.

The app creates a `storage` directory for sessions, the generated encryption
key, and a schema-version marker. The web server must be able to write to this
directory. Back up `storage/app.key` and keep it private: without the same key,
previously saved SMTP passwords cannot be decrypted. `.env` and `storage` are
excluded from Git.

## Scheduled campaign dispatch

Campaign dispatch also works from the app while it is open. To keep due
campaigns running without an open browser, schedule the CLI script to run every
minute.

On Windows, add a task in Task Scheduler that runs:

```text
C:\xampp\php\php.exe C:\xampp\htdocs\core\MailDart\cron\dispatch.php
```

On Linux, add a cron entry, adjusting the PHP and project paths:

```cron
* * * * * php /path/to/MailDart/cron/dispatch.php
```

## Project structure

- `actions/` — form-action handlers
- `api/` — JSON endpoints and the Razorpay webhook
- `assets/` — CSS, JavaScript, and images
- `cron/` — scheduled campaign dispatcher
- `data/` — bundled templates and sample recipients
- `includes/` — database bootstrap, schema migrations, and application logic
- `lib/PHPMailer/` — bundled SMTP mailer
- `views/` — page templates and layouts

## Production notes

- Use a dedicated database user with only the permissions the deployment needs;
  do not use the default XAMPP `root` account in production.
- Keep `.env` and `storage` private and writable only by the application where
  necessary.
- Use HTTPS and set `APP_URL` to the HTTPS site URL.
- Configure and verify outbound SMTP before sending real campaigns.
- Set up regular database and encryption-key backups.
