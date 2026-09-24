# 🎯 MailDart (PRO) — Smart 5-Minute Staggered Email Dispatcher & Campaign Studio

> **An all-purpose, anti-spam bulk email marketing platform and automated 5-minute interval staggered dispatcher.**
> Built with React 19, TypeScript, Tailwind CSS, Express, Nodemailer, and XLSX parsing.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue)](https://www.typescriptlang.org/)
[![React](https://img.shields.io/badge/React-19-cyan)](https://react.dev/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38bdf8)](https://tailwindcss.com/)

---

## 🚀 Key Highlights & Capabilities

- ⏱️ **5-Minute Anti-Spam Staggered Dispatcher:**
  - Automatically paces email delivery (1 recipient every 5 minutes by default, configurable).
  - Eliminates bulk burst detection by Gmail, Yahoo, Outlook, and corporate firewalls.
  - Live countdown timer, pause/resume, and real-time step progress tracking.

- 🎨 **Multi-Category Campaign Studio:**
  - **Company Newsletters & Monthly Digests**
  - **Product Launches & Feature Announcements**
  - **Payment & Invoicing Follow-up Reminders**
  - **Client Onboarding & Welcome Sequences**
  - **Festivals & Holiday Greetings** (Diwali, Eid, Christmas, New Year, Holi, Thanksgiving, Black Friday)
  - **Custom Campaigns**

- 📊 **Excel Spreadsheet (.xlsx / .csv) Dynamic Mapping:**
  - Drag-and-drop Excel files to import hundreds of recipients instantly.
  - Auto-maps custom columns (e.g. `{{name}}`, `{{company}}`, `{{city}}`, `{{order_id}}`, `{{balance}}`).

- ⚡ **SMTP Providers (Elastic Email, Gmail, Brevo, SendGrid, AWS SES):**
  - Preconfigured with **Elastic Email** (ultra-affordable at ~$0.50 per 1,000 emails).
  - Works with Gmail 16-character App Passwords and custom business SMTP servers.
  - Built-in Sandbox / Simulation mode for risk-free testing.
  - 1-Click Instant Test Email modal to verify inbox rendering.

- 📜 **Real-Time Live Logs & Export:**
  - Detailed timestamps, recipient email, Message-ID, and delivery status.
  - Retry failed deliveries individually or in batch.
  - Export logs to CSV with a single click.

---

## 🛠️ Tech Stack

- **Frontend:** React 19, TypeScript, Vite, Tailwind CSS, Lucide Icons, Canvas-Confetti
- **Backend / Proxy:** Node.js Express server (`server.ts`)
- **Mailing Engine:** Nodemailer with connection pooling and TLS support
- **Spreadsheets:** SheetJS (`xlsx`) for client-side and server-side parsing

---

## 📦 Getting Started

### 1. Prerequisites
- [Node.js](https://nodejs.org/) v18 or later
- [npm](https://www.npmjs.com/) or [bun](https://bun.sh/)

### 2. Installation
```bash
# Clone the repository
git clone https://github.com/BanshwarTech/MailDart.git
cd MailDart

# Install dependencies
npm install
```

### 3. Environment Setup
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
*(Optional: Add your SMTP credentials or AI Studio API key if desired)*

### 4. Run Development Server
```bash
npm run dev
```
The app will be available at: `http://localhost:3000`

### 5. Production Build
```bash
npm run build
npm start
```

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
