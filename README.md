# 🎯 MailDart (PRO) — Smart 5-Minute Staggered Email Dispatcher & Campaign Studio

> **An all-purpose, anti-spam bulk email marketing platform and automated 5-minute interval staggered dispatcher.**
> Built in **core PHP + MySQL**: no Node.js, no npm, no build step, no JavaScript frameworks or libraries.

---

## 🚀 Key Highlights & Capabilities

- ⏱️ **5-Minute Anti-Spam Staggered Dispatcher:**
  - Automatically paces email delivery (1 recipient every 5 minutes by default, configurable).
  - Eliminates bulk burst detection by Gmail, Yahoo, Outlook, and corporate firewalls.
  - Live countdown timer, pause/resume, and real-time step progress tracking.
  - Optional cron job keeps campaigns sending even when no browser tab is open.

- 🎨 **Multi-Category Campaign Studio:**
  - **Company Newsletters & Monthly Digests**
  - **Product Launches & Feature Announcements**
  - **Payment & Invoicing Follow-up Reminders**
  - **Client Onboarding & Welcome Sequences**
  - **Festivals & Holiday Greetings** (Diwali, Eid, Christmas, New Year, Holi, Thanksgiving, Black Friday)
  - **Custom Campaigns**, plus the AI template generator (Google Gemini / NVIDIA NIM)

- 📊 **Excel Spreadsheet (.xlsx / .csv) Dynamic Mapping:**
  - Upload Excel files to import hundreds of recipients instantly (parsed in pure PHP).
  - Auto-maps custom columns (e.g. `{{name}}`, `{{company}}`, `{{city}}`, `{{order_id}}`, `{{balance}}`).
  - Legacy binary `.xls` files must be re-saved as `.xlsx` or `.csv`.

- ⚡ **SMTP Providers (Elastic Email, Gmail, Brevo, SendGrid, AWS SES):**
  - Works with Gmail 16-character App Passwords and custom business SMTP servers.
  - Built-in Sandbox / Simulation mode for risk-free testing.
  - 1-Click Instant Test Email modal to verify inbox rendering.

- 👤 **Multi-user accounts:** register / sign in / forgot password; every user's campaign, recipients,
  logs and SMTP settings are stored in MySQL (SMTP passwords encrypted at rest).

- 📜 **Real-Time Live Logs & Export:** timestamps, recipient, Message-ID and status; retry failures; CSV export.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Server | Core PHP 8.1+ (no framework), sessions, PDO |
| Database | MySQL / MariaDB (tables are created automatically) |
| Email | PHPMailer 6 (bundled in `lib/PHPMailer`, no Composer) |
| Styling | Static pre-built CSS (`assets/css/app.css`, Tailwind design tokens) |
| Icons | Lucide icons as inline SVG (`includes/icons.php`) |
| JavaScript | Small plain-JS enhancements only (`assets/js/*.js`), no libraries |

---

## 📦 Getting Started (XAMPP)

1. **Requirements:** XAMPP with PHP 8.1+ and MySQL. PHP extensions `pdo_mysql`, `openssl`, `curl`, `zip`,
   `mbstring` (all enabled in XAMPP by default).
2. **Copy the project** into `htdocs`, e.g. `C:\xampp\htdocs\core\MailDart`.
3. **Configure:** copy `.env.example` to `.env` and adjust if needed. The XAMPP defaults (`root`, empty
   password, database `maildart`) work out of the box. The database and tables are created on the first request.
4. **Start Apache + MySQL** in the XAMPP Control Panel and open
   <http://localhost/core/MailDart/>.
5. **Register an account**, then follow the 5 steps: SMTP Settings → Recipients → Template Studio → Dispatcher → Delivery Logs.

### Optional settings (`.env`)

- `GEMINI_API_KEY` / `NVIDIA_API_KEY`: enable the AI template generator.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_EMAIL`: the system mail account used for
  "Forgot password" emails.
- `APP_URL`: public URL used in password-reset links (auto-detected when empty).
- `APP_KEY`: secret for encrypting saved SMTP passwords (auto-generated in `storage/app.key` when empty).

### Keep sending without an open browser (optional)

The dashboard sends the next email when its countdown reaches zero. To keep campaigns running with the
browser closed, run the dispatcher every minute:

```
# Windows Task Scheduler (every 1 minute)
C:\xampp\php\php.exe C:\xampp\htdocs\core\MailDart\cron\dispatch.php

# Linux cron
* * * * * php /var/www/MailDart/cron/dispatch.php
```

---

## 🗂️ Project Structure

```
index.php              Front controller: routing, auth gate, CSRF-checked POST actions
actions/*.php          Form handlers (auth, smtp, recipients, editor, dispatch, logs, app)
api/*.php              Small JSON endpoints (dispatch tick, live template preview)
views/layout/          Page shells (dashboard, public pages)
views/pages/           One file per page (landing, auth, overview, settings, recipients, editor, dispatch, logs, guide)
views/partials/        Sidebar, header, dropdowns, modals, ...
includes/              bootstrap (env, DB, sessions), helpers, campaign logic, mailer, auth, excel, ai, icons
data/                  Preset email templates + sample recipients (JSON)
lib/PHPMailer/         Bundled PHPMailer
assets/css/app.css     Static stylesheet
assets/js/             Plain-JS progressive enhancements
cron/dispatch.php      Optional CLI dispatcher
storage/               Runtime data: sessions, app key (not web-accessible, git-ignored)
```

Internal folders (`includes`, `actions`, `views`, `lib`, `data`, `cron`, `storage`) and `.env` are blocked from the web by `.htaccess`.

### Clean URLs & SEO

Apache `mod_rewrite` (enabled in XAMPP) serves clean URLs without `.php`: `/login`, `/register`, `/recipients`,
`/api/preview`, … Old links such as `index.php?page=recipients` or `api/preview.php` are 301-redirected to the clean
URL. `/sitemap.xml` (public pages) and `/robots.txt` (blocks the dashboard and internals) are generated by PHP with
the site's real base URL. Dashboard pages also send `<meta name="robots" content="noindex">`.

> Search engines only read `robots.txt` from the **domain root**. When the app lives in a sub-folder
> (e.g. `localhost/core/MailDart`), copy the rules from `/core/MailDart/robots.txt` into the root `robots.txt`,
> or deploy the app at the root of its own domain.

> **Styling note:** `assets/css/app.css` is a static, pre-generated file. It contains the classes used by the
> current views. If you add markup with new utility classes, add matching rules to a stylesheet of your own.

---

## 📋 Recommended SMTP Configuration (Elastic Email)

For high inbox delivery and the lowest possible costs:
- **Host:** `smtp.elasticemail.com`
- **Port:** `2525` or `587`
- **Username:** Your Elastic Email registered email
- **Password:** Your generated Elastic Email API Key
- **Delivery Interval:** 5 minutes (300 seconds)

---

## 📄 License
This project is licensed under the MIT License.
