import React, { useEffect, useState } from 'react';
import {
  BookOpen,
  Rocket,
  FileCode,
  FileSpreadsheet,
  Clock,
  Server,
  SlidersHorizontal,
  HelpCircle,
  ArrowRight,
  CheckCircle2,
  AlertTriangle,
  Info,
  Play,
  Pause,
  Zap,
  RotateCcw,
  Download,
  Mail,
  ChevronDown,
  ShieldCheck,
  FlaskConical,
} from 'lucide-react';
import { ViewSection } from '../types';

interface GuidePageProps {
  intervalMinutes: number;
  onNavigate: (section: ViewSection) => void;
  onOpenSmtpSettings: () => void;
  onOpenTestEmail: () => void;
}

const SECTIONS = [
  { id: 'getting-started', label: 'Getting started', icon: Rocket },
  { id: 'templates', label: 'Templates & variables', icon: FileCode },
  { id: 'excel', label: 'Importing from Excel', icon: FileSpreadsheet },
  { id: 'interval', label: 'Why one email every 5 min', icon: Clock },
  { id: 'delivery', label: 'Delivery settings', icon: Server },
  { id: 'controls', label: 'Campaign controls', icon: SlidersHorizontal },
  { id: 'faq', label: 'FAQ & troubleshooting', icon: HelpCircle },
];

const PROVIDERS = [
  { name: 'Gmail', host: 'smtp.gmail.com', port: '587 (STARTTLS)', username: 'Your Gmail address', password: '16-letter App Password', note: 'Recommended for getting started' },
  { name: 'Elastic Email', host: 'smtp.elasticemail.com', port: '2525 or 587', username: 'Registered login email', password: 'API Key', note: 'Most affordable (~$0.50 / 1,000 emails)' },
  { name: 'Brevo', host: 'smtp-relay.brevo.com', port: '587', username: 'Brevo login email', password: 'SMTP key', note: 'Generous free tier' },
  { name: 'SendGrid', host: 'smtp.sendgrid.net', port: '587', username: 'apikey', password: 'SendGrid API Key', note: 'Good for high volume' },
  { name: 'Amazon SES', host: 'email-smtp.us-east-1.amazonaws.com', port: '587', username: 'SES SMTP username', password: 'SES SMTP password', note: 'Region-specific host' },
];

const VARIABLES = [
  { tag: 'name', meaning: 'Recipient name' },
  { tag: 'email', meaning: 'Recipient email address' },
  { tag: 'company', meaning: 'Your company name (or the Company column from Excel)' },
  { tag: 'discount', meaning: 'Discount / coupon code' },
  { tag: 'festival', meaning: 'Campaign category title, e.g. "Diwali (Deepavali)"' },
];

const FAQS = [
  {
    q: 'My test email went to the spam folder. What should I do?',
    a: 'Use a proper sender name and a real From address, personalise the subject with {{name}}, and avoid too many images or words like "FREE" in capitals. Keep the 5-minute interval for real campaigns. A verified domain (SPF / DKIM) with providers like Elastic Email or Brevo helps a lot.',
  },
  {
    q: 'Gmail says "Username and Password not accepted".',
    a: 'Gmail does not allow your normal password over SMTP. Turn on 2-Step Verification, create an App Password and paste the 16-letter code (without spaces is fine) in the Password field. Use host smtp.gmail.com with port 587 and keep SSL off (587 uses STARTTLS automatically).',
  },
  {
    q: 'What is the difference between Sandbox and Real SMTP?',
    a: 'Sandbox only simulates sending: the timer, logs and statuses work, but no email leaves the app. Real SMTP actually delivers emails through your mail account. Always run a quick Sandbox or test send before a real campaign.',
  },
  {
    q: 'Can I close the browser while the campaign is running?',
    a: 'No. The dispatcher runs in this browser tab, so keep the tab open until the campaign is complete. If you close it, the campaign pauses and you can resume from where it stopped.',
  },
  {
    q: 'Some rows from my Excel file were not imported.',
    a: 'Rows without a valid email address and duplicate emails are skipped. Check that your sheet has a column named Email, Mail or Receiver, and that each row has a proper email address.',
  },
  {
    q: 'Where is my data stored?',
    a: 'Your campaign, recipients and settings are saved in this browser automatically. If you log in, your SMTP settings are also synced securely to your account so you can use them on another device.',
  },
];

const SectionHeader: React.FC<{ icon: React.ElementType; eyebrow: string; title: string; description: string }> = ({
  icon: Icon,
  eyebrow,
  title,
  description,
}) => (
  <div className="flex items-start gap-4 mb-6">
    <div className="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
      <Icon className="w-5 h-5" />
    </div>
    <div>
      <p className="text-[11px] font-semibold uppercase tracking-[0.08em] text-brand-700">{eyebrow}</p>
      <h2 className="text-xl font-semibold tracking-tight text-slate-900">{title}</h2>
      <p className="text-sm text-slate-500 mt-1 leading-relaxed">{description}</p>
    </div>
  </div>
);

const Callout: React.FC<{ tone: 'info' | 'tip' | 'warning'; title: string; children: React.ReactNode }> = ({ tone, title, children }) => {
  const styles = {
    info: { box: 'bg-sky-50 ring-sky-200 text-sky-900', icon: <Info className="w-4 h-4 text-sky-600" /> },
    tip: { box: 'bg-brand-50 ring-brand-200 text-brand-900', icon: <CheckCircle2 className="w-4 h-4 text-brand-700" /> },
    warning: { box: 'bg-amber-50 ring-amber-200 text-amber-900', icon: <AlertTriangle className="w-4 h-4 text-amber-600" /> },
  }[tone];
  return (
    <div className={`mt-5 rounded-xl ring-1 ring-inset px-4 py-3.5 flex gap-3 ${styles.box}`}>
      <div className="mt-0.5 shrink-0">{styles.icon}</div>
      <div className="text-sm leading-relaxed">
        <p className="font-semibold">{title}</p>
        <div className="opacity-90 mt-0.5">{children}</div>
      </div>
    </div>
  );
};

const Tag: React.FC<{ children: string }> = ({ children }) => (
  <code className="px-1.5 py-0.5 bg-slate-100 ring-1 ring-inset ring-slate-200 text-brand-700 font-mono text-xs rounded-md whitespace-nowrap">
    {children}
  </code>
);

const card = 'bg-surface border border-slate-200/80 rounded-2xl shadow-card p-6 md:p-8 scroll-mt-24';

export const GuidePage: React.FC<GuidePageProps> = ({ intervalMinutes, onNavigate, onOpenSmtpSettings, onOpenTestEmail }) => {
  const [activeId, setActiveId] = useState(SECTIONS[0].id);
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  // Highlight the section currently in view
  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
        if (visible[0]) setActiveId(visible[0].target.id);
      },
      { rootMargin: '-96px 0px -60% 0px' }
    );
    SECTIONS.forEach((s) => {
      const el = document.getElementById(s.id);
      if (el) observer.observe(el);
    });
    return () => observer.disconnect();
  }, []);

  const scrollTo = (id: string) => {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    setActiveId(id);
  };

  const quickStart = [
    { title: 'Connect your email', text: 'Add Gmail, Elastic Email or any SMTP account, or stay in Sandbox mode to practise.', action: { label: 'SMTP Settings', onClick: onOpenSmtpSettings } },
    { title: 'Add recipients', text: 'Upload an Excel / CSV sheet or add people one by one.', action: { label: 'Recipients', onClick: () => onNavigate('recipients') } },
    { title: 'Design the email', text: 'Pick a ready template, generate one with AI, or paste your own HTML.', action: { label: 'Template Studio', onClick: () => onNavigate('editor') } },
    { title: 'Send a test', text: 'Send one email to yourself and check how it looks in a real inbox.', action: { label: 'Send Test Email', onClick: onOpenTestEmail } },
    { title: 'Start the campaign', text: `Press Start. One email goes out every ${intervalMinutes} min until everyone is covered.`, action: { label: 'Dispatcher', onClick: () => onNavigate('dispatch') } },
  ];

  const controls = [
    { icon: Play, name: 'Start / Resume', text: 'Begins sending from the next pending recipient.', tone: 'text-brand-700 bg-brand-50' },
    { icon: Pause, name: 'Pause', text: 'Stops the timer. Resume any time from the same point.', tone: 'text-amber-600 bg-amber-50' },
    { icon: Zap, name: 'Send Immediately', text: 'Skips the wait and sends to the next recipient right now.', tone: 'text-brand-700 bg-brand-50' },
    { icon: RotateCcw, name: 'Reset Statuses', text: 'Marks everyone as pending again so the campaign can be re-sent.', tone: 'text-slate-600 bg-slate-100' },
    { icon: RotateCcw, name: 'Retry Failed', text: 'Re-sends to a single recipient whose delivery failed.', tone: 'text-red-600 bg-red-50' },
    { icon: Download, name: 'Export CSV', text: 'Downloads the delivery logs as a report from Delivery Logs.', tone: 'text-sky-600 bg-sky-50' },
  ];

  return (
    <div className="space-y-6">
      {/* Hero */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0A1B2E] via-[#112A46] to-[#1B3B5A] ring-1 ring-inset ring-[#34506D]/60 text-white shadow-card">
        <div className="absolute inset-0 opacity-40 [background-image:radial-gradient(rgb(255_255_255/0.12)_1px,transparent_1px)] [background-size:18px_18px] [mask-image:linear-gradient(to_left,black,transparent_70%)]" />
        <div className="absolute -top-24 -right-16 w-80 h-80 rounded-full bg-brand-500/30 blur-3xl" />
        <div className="relative p-6 md:p-10">
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-inset ring-white/15 text-[11px] font-semibold uppercase tracking-[0.08em] text-[#E1E8F0]">
            <BookOpen className="w-3.5 h-3.5" />
            Documentation
          </span>
          <h2 className="mt-4 text-2xl md:text-4xl font-semibold tracking-tight">MailDart Campaign Guide</h2>
          <p className="mt-3 max-w-2xl text-sm md:text-base text-[#C4D2E1] leading-relaxed">
            Everything you need to plan, personalise and send email campaigns that land in the inbox, from your first test email
            to a full campaign with hundreds of recipients.
          </p>
          <div className="mt-6 flex flex-wrap gap-2">
            <button
              onClick={() => scrollTo('getting-started')}
              className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-[#FFFFFF] rounded-lg shadow-button transition"
            >
              <Rocket className="w-4 h-4 text-[#1B3B5A]" />
              Quick start
            </button>
            <button
              onClick={() => scrollTo('faq')}
              className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-medium text-white bg-white/10 hover:bg-white/15 ring-1 ring-inset ring-white/20 rounded-lg transition"
            >
              <HelpCircle className="w-4 h-4" />
              FAQ & troubleshooting
            </button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-6 items-start">
        {/* Table of contents */}
        <aside className="hidden lg:block sticky top-24">
          <p className="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">On this page</p>
          <nav className="space-y-0.5 border-l border-slate-200">
            {SECTIONS.map((s) => (
              <button
                key={s.id}
                onClick={() => scrollTo(s.id)}
                className={`-ml-px w-full text-left pl-4 pr-2 py-1.5 text-sm border-l-2 transition ${
                  activeId === s.id
                    ? 'border-brand-600 text-brand-700 font-semibold'
                    : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
                }`}
              >
                {s.label}
              </button>
            ))}
          </nav>
        </aside>

        {/* Mobile table of contents */}
        <div className="lg:hidden -mx-1 flex gap-2 overflow-x-auto pb-1">
          {SECTIONS.map((s) => (
            <button
              key={s.id}
              onClick={() => scrollTo(s.id)}
              className="shrink-0 px-3 py-1.5 text-xs font-medium text-slate-600 bg-surface ring-1 ring-inset ring-slate-200 rounded-full"
            >
              {s.label}
            </button>
          ))}
        </div>

        {/* Content */}
        <div className="space-y-6 min-w-0">
          {/* 1. Getting started */}
          <section id="getting-started" className={card}>
            <SectionHeader icon={Rocket} eyebrow="Step by step" title="Getting started" description="Your first campaign in five simple steps. Each step has a shortcut to the right screen." />
            <ol className="relative space-y-4">
              {quickStart.map((step, i) => (
                <li key={step.title} className="relative flex gap-4">
                  {i < quickStart.length - 1 && <span className="absolute left-4 top-9 bottom-[-16px] w-px bg-slate-200" />}
                  <span className="relative z-10 w-8 h-8 shrink-0 rounded-full bg-brand-600 text-white text-sm font-semibold flex items-center justify-center shadow-button">
                    {i + 1}
                  </span>
                  <div className="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-center justify-between gap-2 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 px-4 py-3">
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{step.title}</p>
                      <p className="text-sm text-slate-500">{step.text}</p>
                    </div>
                    <button
                      onClick={step.action.onClick}
                      className="shrink-0 self-start sm:self-auto inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800 hover:bg-brand-50 px-2.5 py-1.5 rounded-lg transition"
                    >
                      {step.action.label}
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </li>
              ))}
            </ol>
          </section>

          {/* 2. Templates */}
          <section id="templates" className={card}>
            <SectionHeader
              icon={FileCode}
              eyebrow="Template Studio"
              title="Templates & variables"
              description="Paste any HTML, choose a ready-made festive or business template, or generate a new one with AI in seconds."
            />
            <div className="grid sm:grid-cols-3 gap-3 mb-6">
              {[
                { t: 'Ready-made templates', d: 'Diwali, Eid, Christmas, Holi, newsletters, product launches and more.' },
                { t: 'AI Studio', d: 'Describe your campaign and get a complete HTML email generated for you.' },
                { t: 'Custom HTML', d: 'Paste your own code and preview it on desktop and mobile instantly.' },
              ].map((f) => (
                <div key={f.t} className="rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 p-4">
                  <p className="text-sm font-semibold text-slate-900">{f.t}</p>
                  <p className="text-sm text-slate-500 mt-1">{f.d}</p>
                </div>
              ))}
            </div>
            <p className="text-sm font-semibold text-slate-900 mb-2">Personalisation tags</p>
            <p className="text-sm text-slate-500 mb-3">Use these in the subject line or the HTML. Each tag is replaced with the recipient&apos;s own data.</p>
            <div className="border border-slate-200 rounded-xl overflow-hidden">
              <table className="w-full text-sm">
                <thead className="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wide">
                  <tr>
                    <th className="text-left px-4 py-2.5 w-40">Tag</th>
                    <th className="text-left px-4 py-2.5">Replaced with</th>
                  </tr>
                </thead>
                <tbody>
                  {VARIABLES.map((v) => (
                    <tr key={v.tag} className="border-t border-slate-100">
                      <td className="px-4 py-2.5"><Tag>{`{{${v.tag}}}`}</Tag></td>
                      <td className="px-4 py-2.5 text-slate-700">{v.meaning}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Callout tone="tip" title="Example">
              Subject: <Tag>{'Happy Diwali, {{name}}! A gift from {{company}}'}</Tag> becomes “Happy Diwali, Priya! A gift from Acme Global”.
            </Callout>
          </section>

          {/* 3. Excel */}
          <section id="excel" className={card}>
            <SectionHeader
              icon={FileSpreadsheet}
              eyebrow="Recipients"
              title="Importing from Excel"
              description="No need to type anything by hand. Upload a .xlsx, .xls or .csv file and MailDart reads the columns for you."
            />
            <div className="grid sm:grid-cols-2 gap-3">
              <div className="rounded-xl ring-1 ring-inset ring-brand-200 bg-brand-50/60 p-4">
                <p className="text-sm font-semibold text-brand-900">Email column (required)</p>
                <p className="text-sm text-brand-800/80 mt-1">Name it <Tag>Email</Tag>, <Tag>Mail</Tag> or <Tag>Receiver</Tag>.</p>
              </div>
              <div className="rounded-xl ring-1 ring-inset ring-slate-200 bg-slate-50 p-4">
                <p className="text-sm font-semibold text-slate-900">Name column (recommended)</p>
                <p className="text-sm text-slate-500 mt-1">Name it <Tag>Name</Tag> or <Tag>Full Name</Tag>, used by <Tag>{'{{name}}'}</Tag>.</p>
              </div>
            </div>
            <p className="text-sm font-semibold text-slate-900 mt-6 mb-2">Every extra column becomes a variable</p>
            <div className="border border-slate-200 rounded-xl overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wide">
                  <tr>
                    <th className="text-left px-4 py-2.5">Excel column</th>
                    <th className="text-left px-4 py-2.5">Becomes tag</th>
                    <th className="text-left px-4 py-2.5">Example value</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    ['City', '{{city}}', 'Mumbai'],
                    ['Gift Item', '{{gift_item}}', 'Dry Fruits Box'],
                    ['Order ID', '{{order_id}}', 'ORD-10452'],
                  ].map(([col, tag, ex]) => (
                    <tr key={col} className="border-t border-slate-100">
                      <td className="px-4 py-2.5 font-medium text-slate-900">{col}</td>
                      <td className="px-4 py-2.5"><Tag>{tag}</Tag></td>
                      <td className="px-4 py-2.5 text-slate-500">{ex}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Callout tone="info" title="Tip">
              Use the <strong>Excel Format</strong> button on the Recipients page to download a ready sample sheet. Rows without a valid email
              and duplicate emails are skipped automatically.
            </Callout>
            <button
              onClick={() => onNavigate('recipients')}
              className="mt-5 inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-slate-700 bg-surface hover:bg-slate-50 border border-slate-300 rounded-lg shadow-xs transition"
            >
              Go to Recipients <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </section>

          {/* 4. Interval */}
          <section id="interval" className={card}>
            <SectionHeader
              icon={Clock}
              eyebrow="Deliverability"
              title="Why one email every 5 minutes?"
              description="Sending slowly is the simplest way to stay out of the spam folder."
            />
            <div className="grid md:grid-cols-2 gap-3">
              <div className="rounded-xl ring-1 ring-inset ring-red-200 bg-red-50/60 p-5">
                <p className="text-sm font-semibold text-red-900">Bulk blast (all at once)</p>
                <p className="text-sm text-red-800/80 mt-1 leading-relaxed">
                  Hundreds of emails in the same second look like a bot to <strong>Gmail, Yahoo and Outlook</strong>. Their spam filters
                  block the sender, and your emails land in spam instead of the inbox.
                </p>
              </div>
              <div className="rounded-xl ring-1 ring-inset ring-brand-200 bg-brand-50/60 p-5">
                <p className="text-sm font-semibold text-brand-900">MailDart staggered sending</p>
                <p className="text-sm text-brand-800/80 mt-1 leading-relaxed">
                  One personalised email goes to one recipient at a time, with a gap of 5 minutes (300 seconds). The load stays low and
                  your emails look like normal, one-to-one mail.
                </p>
              </div>
            </div>
            <Callout tone="warning" title="Testing only">
              You can set the interval to 1 minute or 30 seconds while testing, but keep 5 minutes for real campaigns.
            </Callout>
          </section>

          {/* 5. Delivery */}
          <section id="delivery" className={card}>
            <SectionHeader
              icon={Server}
              eyebrow="SMTP"
              title="Delivery settings"
              description="Choose how emails are sent. Start in Sandbox, then switch to your real mail account when you are ready."
            />
            <div className="grid md:grid-cols-2 gap-3 mb-6">
              <div className="rounded-xl ring-1 ring-inset ring-amber-200 bg-amber-50/60 p-5">
                <div className="flex items-center gap-2 text-amber-900">
                  <FlaskConical className="w-4 h-4" />
                  <p className="text-sm font-semibold">Sandbox mode</p>
                </div>
                <p className="text-sm text-amber-900/75 mt-1.5 leading-relaxed">
                  Practise the complete flow (timer, statuses and logs) safely, without any password. No real email is sent.
                </p>
              </div>
              <div className="rounded-xl ring-1 ring-inset ring-brand-200 bg-brand-50/60 p-5">
                <div className="flex items-center gap-2 text-brand-900">
                  <ShieldCheck className="w-4 h-4" />
                  <p className="text-sm font-semibold">Real SMTP mode</p>
                </div>
                <p className="text-sm text-brand-900/75 mt-1.5 leading-relaxed">
                  Real emails are delivered to inboxes through your Gmail or email provider, from your own address.
                </p>
              </div>
            </div>

            <p className="text-sm font-semibold text-slate-900 mb-2">Supported providers</p>
            <div className="border border-slate-200 rounded-xl overflow-x-auto">
              <table className="w-full text-sm min-w-[640px]">
                <thead className="bg-slate-50 text-slate-500 text-xs font-semibold uppercase tracking-wide">
                  <tr>
                    <th className="text-left px-4 py-2.5">Provider</th>
                    <th className="text-left px-4 py-2.5">Host</th>
                    <th className="text-left px-4 py-2.5">Port</th>
                    <th className="text-left px-4 py-2.5">Username / Password</th>
                  </tr>
                </thead>
                <tbody>
                  {PROVIDERS.map((p) => (
                    <tr key={p.name} className="border-t border-slate-100 align-top">
                      <td className="px-4 py-3">
                        <p className="font-semibold text-slate-900">{p.name}</p>
                        <p className="text-xs text-slate-500">{p.note}</p>
                      </td>
                      <td className="px-4 py-3"><Tag>{p.host}</Tag></td>
                      <td className="px-4 py-3 text-slate-700 whitespace-nowrap">{p.port}</td>
                      <td className="px-4 py-3 text-slate-700">
                        {p.username}
                        <span className="block text-xs text-slate-500">{p.password}</span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="mt-6 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 p-5">
              <p className="text-sm font-semibold text-slate-900">How to create a Gmail App Password</p>
              <ol className="mt-3 space-y-2 text-sm text-slate-600 list-decimal list-inside">
                <li>
                  Open your{' '}
                  <a href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer" className="text-brand-700 font-medium underline underline-offset-2">
                    Google Account security settings
                  </a>
                  .
                </li>
                <li>Make sure <strong className="text-slate-900">2-Step Verification</strong> is turned on.</li>
                <li>Search for <strong className="text-slate-900">App passwords</strong> and open it.</li>
                <li>Enter the app name <Tag>MailDart</Tag> and click <strong className="text-slate-900">Create</strong>.</li>
                <li>Copy the 16-letter code and paste it in the Password field of SMTP Settings.</li>
              </ol>
            </div>

            <button
              onClick={onOpenSmtpSettings}
              className="mt-5 inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
            >
              <Server className="w-3.5 h-3.5" />
              Open SMTP Settings
            </button>
          </section>

          {/* 6. Controls */}
          <section id="controls" className={card}>
            <SectionHeader
              icon={SlidersHorizontal}
              eyebrow="Dispatcher"
              title="Campaign controls"
              description="You stay in full control while the campaign is running."
            />
            <div className="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
              {controls.map((c) => {
                const Icon = c.icon;
                return (
                  <div key={c.name} className="rounded-xl ring-1 ring-inset ring-slate-200/80 p-4 hover:shadow-card-hover transition">
                    <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${c.tone}`}>
                      <Icon className="w-4 h-4" />
                    </div>
                    <p className="mt-3 text-sm font-semibold text-slate-900">{c.name}</p>
                    <p className="text-sm text-slate-500 mt-0.5">{c.text}</p>
                  </div>
                );
              })}
            </div>
            <Callout tone="warning" title="Keep this tab open">
              The dispatcher runs in your browser. Keep the MailDart tab open until the campaign is complete.
            </Callout>
          </section>

          {/* 7. FAQ */}
          <section id="faq" className={card}>
            <SectionHeader icon={HelpCircle} eyebrow="Help" title="FAQ & troubleshooting" description="Quick answers to the most common questions." />
            <div className="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
              {FAQS.map((f, i) => {
                const open = openFaq === i;
                return (
                  <div key={f.q}>
                    <button
                      onClick={() => setOpenFaq(open ? null : i)}
                      className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 transition"
                      aria-expanded={open}
                    >
                      <span className="text-sm font-semibold text-slate-900">{f.q}</span>
                      <ChevronDown className={`w-4 h-4 shrink-0 text-slate-400 transition-transform ${open ? 'rotate-180' : ''}`} />
                    </button>
                    {open && <p className="px-5 pb-4 -mt-1 text-sm text-slate-600 leading-relaxed animate-fade-in">{f.a}</p>}
                  </div>
                );
              })}
            </div>
          </section>

          {/* Footer CTA */}
          <div className="rounded-2xl bg-surface border border-slate-200/80 shadow-card p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
              <p className="text-base font-semibold text-slate-900">Got it? Let&apos;s start your campaign.</p>
              <p className="text-sm text-slate-500">Head to the Dispatcher when your SMTP, recipients and template are ready.</p>
            </div>
            <button
              onClick={() => onNavigate('dispatch')}
              className="inline-flex items-center gap-1.5 h-10 px-4 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
            >
              Go to Dispatcher <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
