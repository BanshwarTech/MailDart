import React, { useState } from 'react';
import {
  ArrowRight,
  Timer,
  FileSpreadsheet,
  Sparkles,
  MonitorSmartphone,
  Server,
  ScrollText,
  FlaskConical,
  Cloud,
  ShieldCheck,
  X,
  Check,
  ChevronDown,
  PartyPopper,
  Newspaper,
  Rocket,
  BellRing,
  HandHeart,
  Monitor,
  Smartphone,
  Wand2,
  UserRound,
  Mail,
  Plus,
  BookOpen,
} from 'lucide-react';
import { ViewSection } from '../types';
import { AppLogo } from './AppLogo';
import { STEP_ITEMS } from '../data/navigation';
import { PRESET_TEMPLATES } from '../data/presetTemplates';

interface LandingPageProps {
  isLoggedIn: boolean;
  onOpenApp: (section?: ViewSection) => void;
  onLogin: () => void;
  onSignup: () => void;
}

const PROVIDERS = ['Gmail', 'Elastic Email', 'Brevo', 'SendGrid', 'Amazon SES', 'Any SMTP'];

const USE_CASES = [
  { icon: PartyPopper, title: 'Festival greetings', text: 'Diwali, Eid, Holi, Christmas and New Year wishes with a personal offer code.' },
  { icon: Newspaper, title: 'Newsletters', text: 'Monthly updates and digests for customers and partners.' },
  { icon: Rocket, title: 'Product launches', text: 'Announce new features or products to your whole list.' },
  { icon: BellRing, title: 'Reminders', text: 'Payment, renewal and follow-up reminders with each customer’s details.' },
  { icon: HandHeart, title: 'Onboarding', text: 'Warm welcome emails for new customers and clients.' },
];

const FAQS = [
  { q: 'Why not send all emails at once?', a: 'Hundreds of emails in the same second look like spam to email providers. Sending one at a time with a gap keeps your sender reputation healthy and your emails in the inbox.' },
  { q: 'Do I need a paid email service?', a: 'No. A normal Gmail account with a free App Password works. For larger lists, low-cost providers like Elastic Email or Brevo are supported too.' },
  { q: 'Can I try it without sending real emails?', a: 'Yes. Sandbox mode simulates the whole campaign (timer, statuses and logs) so you can practise safely.' },
  { q: 'How many emails can I send in a day?', a: 'At the default gap of 5 minutes that is about 12 emails an hour. You can change the gap, and your email provider sets the daily limit (for example, a normal Gmail account allows around 500 a day).' },
  { q: 'Can I personalise each email?', a: 'Yes. Use tags like {{name}}, {{company}} and {{discount}}, plus any column from your Excel sheet such as {{city}} or {{order_id}}. Each recipient gets their own values.' },
  { q: 'Is my data safe?', a: 'Your campaign and recipients stay in your browser. Your SMTP settings are stored securely in your own account.' },
];

const STEP_TEXT: Record<string, string> = {
  settings: 'Connect Gmail or any SMTP account, or start in Sandbox mode.',
  recipients: 'Upload your Excel / CSV list or add people one by one.',
  editor: 'Pick a template, personalise it with tags and send yourself a test.',
  dispatch: 'Press Start. One email goes out at a time, on a timer.',
  logs: 'Watch deliveries live, retry failures and export a report.',
};

const card = 'rounded-2xl bg-surface border border-slate-200/80 shadow-card';
const eyebrow = 'inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500';
const gradientText = 'bg-gradient-to-b from-[#F2F6F8] via-[#C4D2E1] to-[#7A8CA6] bg-clip-text text-transparent';

/** Faux product screenshot for the hero */
const ProductPreview: React.FC = () => (
  <div className="relative">
    <div className="absolute -inset-x-10 -top-10 -bottom-16 bg-[radial-gradient(ellipse_at_center,rgb(85_115_146/0.35),transparent_65%)] blur-2xl" aria-hidden />
    <div className="relative rounded-2xl bg-[#0E2440] ring-1 ring-[#34506D] shadow-[0_40px_120px_-30px_rgb(0_0_0/0.8)] overflow-hidden">
      {/* Browser chrome */}
      <div className="h-10 px-4 flex items-center gap-3 border-b border-[#1F4060] bg-[#0A1B2E]">
        <div className="flex gap-1.5">
          <span className="w-2.5 h-2.5 rounded-full bg-[#34506D]" />
          <span className="w-2.5 h-2.5 rounded-full bg-[#34506D]" />
          <span className="w-2.5 h-2.5 rounded-full bg-[#34506D]" />
        </div>
        <div className="flex-1 max-w-xs mx-auto h-6 rounded-md bg-[#112A46] ring-1 ring-[#1F4060] text-[10px] text-[#7A8CA6] flex items-center justify-center font-mono">
          maildart · dispatcher
        </div>
      </div>

      <div className="grid grid-cols-[150px_minmax(0,1fr)] md:grid-cols-[190px_minmax(0,1fr)] min-h-[340px]">
        {/* Mini sidebar */}
        <div className="hidden sm:block border-r border-[#1F4060] bg-[#112A46]/70 p-3 space-y-1">
          <p className="px-2 pb-1 text-[9px] font-semibold uppercase tracking-wider text-[#7A8CA6]">Campaign steps</p>
          {STEP_ITEMS.map((s, i) => (
            <div key={s.id} className={`flex items-center gap-2 px-2 py-1.5 rounded-md text-[11px] ${s.id === 'dispatch' ? 'bg-[#1B3B5A] text-[#F2F6F8]' : 'text-[#A1B2C4]'}`}>
              <span className={`w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold ${i < 3 ? 'bg-[#557392] text-white' : 'ring-1 ring-[#34506D] text-[#A1B2C4]'}`}>
                {i < 3 ? <Check className="w-2.5 h-2.5" strokeWidth={3} /> : s.step}
              </span>
              {s.label}
            </div>
          ))}
        </div>

        {/* Main area */}
        <div className="col-span-2 sm:col-span-1 p-4 md:p-5 grid md:grid-cols-5 gap-3">
          <div className="md:col-span-2 rounded-xl bg-[#112A46] ring-1 ring-[#1F4060] p-4 flex flex-col items-center justify-center text-center">
            <p className="text-[9px] font-semibold uppercase tracking-wider text-[#7A8CA6]">Next email in</p>
            <p className="mt-1 text-4xl font-bold font-mono tabular-nums text-[#F2F6F8]">04:32</p>
            <span className="mt-2 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-amber-50 ring-1 ring-inset ring-amber-200 text-[10px] font-semibold text-amber-800">
              <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" />
              Sending
            </span>
          </div>

          <div className="md:col-span-3 rounded-xl bg-[#112A46] ring-1 ring-[#1F4060] p-4">
            <div className="flex items-center justify-between">
              <p className="text-[10px] font-semibold uppercase tracking-wider text-[#7A8CA6]">Deliveries today</p>
              <p className="text-[10px] text-[#A1B2C4]"><span className="text-[#F2F6F8] font-semibold">42</span> / 60 sent</p>
            </div>
            <div className="mt-3 h-20 flex items-end gap-1.5">
              {[30, 45, 38, 60, 52, 70, 64, 82, 76, 90, 72, 95].map((h, i) => (
                <span key={i} className="flex-1 rounded-t bg-gradient-to-t from-[#34506D] to-[#7A8CA6]" style={{ height: `${h}%` }} />
              ))}
            </div>
          </div>

          <div className="md:col-span-5 rounded-xl bg-[#112A46] ring-1 ring-[#1F4060] divide-y divide-[#1F4060]">
            {[
              ['Aarav Sharma', 'aarav@example.com', 'Delivered', '10:05'],
              ['Priya Patel', 'priya@acme-corp.in', 'Delivered', '10:10'],
              ['Rohit Verma', 'rohit@techsolutions.com', 'Next in queue', '10:15'],
            ].map(([name, email, status, time]) => (
              <div key={name} className="flex items-center gap-3 px-3 py-2.5">
                <span className="w-7 h-7 shrink-0 rounded-full bg-[#1B3B5A] text-[#C4D2E1] text-[10px] font-semibold flex items-center justify-center">
                  {name.split(' ').map((p) => p[0]).join('')}
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block text-xs font-medium text-[#F2F6F8] truncate">{name}</span>
                  <span className="block text-[10px] font-mono text-[#7A8CA6] truncate">{email}</span>
                </span>
                <span className="hidden sm:block text-[10px] font-mono text-[#7A8CA6]">{time}</span>
                <span
                  className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ring-1 ring-inset ${
                    status === 'Delivered' ? 'bg-[#1B3B5A] text-[#C4D2E1] ring-[#34506D]' : 'bg-transparent text-[#A1B2C4] ring-[#34506D]'
                  }`}
                >
                  {status === 'Delivered' ? '✓ ' : ''}
                  {status}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  </div>
);

export const LandingPage: React.FC<LandingPageProps> = ({ isLoggedIn, onOpenApp, onLogin, onSignup }) => {
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  const scrollTo = (id: string) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  const primaryAction = isLoggedIn ? () => onOpenApp() : onSignup;

  const primaryBtn =
    'inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-[#FFFFFF] rounded-xl shadow-[0_0_0_1px_rgb(255_255_255/0.2),0_10px_30px_-10px_rgb(193_210_225/0.5)] transition';
  const secondaryBtn =
    'inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-medium text-slate-800 bg-white/[0.04] hover:bg-white/[0.08] ring-1 ring-inset ring-slate-300 rounded-xl transition';

  return (
    <div className="min-h-screen bg-page text-slate-900 overflow-x-clip">
      {/* ---------- Navigation ---------- */}
      <header className="sticky top-0 z-40 bg-page/70 backdrop-blur-xl border-b border-slate-200/60">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
          <AppLogo size="md" showTagline={false} />
          <nav className="hidden md:flex items-center gap-1 text-sm">
            {[
              ['features', 'Features'],
              ['how', 'How it works'],
              ['why', 'Why MailDart'],
              ['faq', 'FAQ'],
            ].map(([id, label]) => (
              <button key={id} onClick={() => scrollTo(id)} className="px-3 py-2 rounded-lg text-slate-500 hover:text-slate-900 transition">
                {label}
              </button>
            ))}
          </nav>
          {isLoggedIn ? (
            <button onClick={() => onOpenApp()} className="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition">
              Open Dashboard
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          ) : (
            <div className="flex items-center gap-1.5">
              <button onClick={onLogin} className="h-9 px-3.5 text-xs font-semibold text-slate-700 hover:text-slate-900 rounded-lg transition">
                Log in
              </button>
              <button onClick={onSignup} className="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-white rounded-lg transition">
                Sign up free
              </button>
            </div>
          )}
        </div>
      </header>

      {/* ---------- Hero ---------- */}
      <section className="relative">
        {/* Background: glow + grid */}
        <div className="absolute inset-0 -z-0 pointer-events-none" aria-hidden>
          <div className="absolute inset-0 [background-image:linear-gradient(rgb(52_80_109/0.25)_1px,transparent_1px),linear-gradient(90deg,rgb(52_80_109/0.25)_1px,transparent_1px)] [background-size:56px_56px] [mask-image:radial-gradient(ellipse_70%_60%_at_50%_0%,black,transparent)]" />
          <div className="absolute -top-48 left-1/2 -translate-x-1/2 w-[1100px] h-[620px] rounded-full bg-[radial-gradient(closest-side,rgb(85_115_146/0.45),transparent)]" />
        </div>

        <div className="relative max-w-6xl mx-auto px-4 sm:px-6 pt-16 md:pt-24 text-center">
          <button
            onClick={() => scrollTo('features')}
            className="inline-flex items-center gap-2 pl-1.5 pr-3 py-1 rounded-full bg-surface/80 ring-1 ring-inset ring-slate-300 text-xs text-slate-600 hover:ring-slate-400 transition"
          >
            <span className="px-2 py-0.5 rounded-full bg-[#557392] text-white text-[10px] font-bold uppercase tracking-wider">New</span>
            AI Studio with NVIDIA NIM & Gemini
            <ArrowRight className="w-3 h-3" />
          </button>

          <h1 className="mt-7 mx-auto max-w-4xl text-4xl sm:text-5xl md:text-7xl font-semibold tracking-[-0.03em] leading-[1.05]">
            <span className={gradientText}>Email campaigns that</span>
            <br />
            <span className="text-[#F2F6F8]">land in the inbox.</span>
          </h1>
          <p className="mt-6 mx-auto max-w-2xl text-base md:text-lg text-slate-500 leading-relaxed">
            MailDart sends your festival greetings, newsletters and reminders <strong className="text-slate-800 font-medium">one email at a time</strong>, from
            your own email account, with every message personalised for each recipient.
          </p>
          <div className="mt-9 flex flex-wrap justify-center gap-3">
            <button onClick={primaryAction} className={primaryBtn}>
              {isLoggedIn ? 'Open Dashboard' : 'Start free'}
              <ArrowRight className="w-4 h-4" />
            </button>
            <button onClick={() => scrollTo('how')} className={secondaryBtn}>
              See how it works
            </button>
          </div>
          <div className="mt-5 flex flex-wrap justify-center gap-x-5 gap-y-2 text-xs text-slate-400">
            {['Free to start', 'Try safely in Sandbox mode', 'Works with your Gmail'].map((t) => (
              <span key={t} className="inline-flex items-center gap-1.5">
                <Check className="w-3.5 h-3.5 text-slate-500" />
                {t}
              </span>
            ))}
          </div>

          <div className="mt-16 md:mt-20 text-left">
            <ProductPreview />
          </div>
        </div>

        {/* Provider strip */}
        <div className="relative max-w-6xl mx-auto px-4 sm:px-6 pt-20 pb-6">
          <p className="text-center text-xs font-medium text-slate-400">Send through the email provider you already use</p>
          <div className="mt-5 flex flex-wrap justify-center gap-x-10 gap-y-4">
            {PROVIDERS.map((p) => (
              <span key={p} className="text-lg md:text-xl font-semibold tracking-tight text-slate-400/80 hover:text-slate-600 transition">
                {p}
              </span>
            ))}
          </div>
        </div>
      </section>

      {/* ---------- Features (bento) ---------- */}
      <section id="features" className="scroll-mt-20 max-w-6xl mx-auto px-4 sm:px-6 py-24">
        <div className="text-center max-w-2xl mx-auto">
          <p className={eyebrow}>Features</p>
          <h2 className="mt-3 text-3xl md:text-5xl font-semibold tracking-[-0.02em] text-slate-900">Everything a campaign needs.</h2>
          <p className="mt-4 text-slate-500">From importing your list to tracking every delivery, in one simple workspace.</p>
        </div>

        <div className="mt-14 grid md:grid-cols-6 gap-4">
          {/* Staggered sending: large */}
          <div className={`${card} md:col-span-4 p-6 md:p-8 overflow-hidden`}>
            <div className="flex items-center gap-2 text-slate-900">
              <Timer className="w-5 h-5 text-slate-500" />
              <h3 className="text-lg font-semibold">Staggered, anti-spam sending</h3>
            </div>
            <p className="mt-2 text-sm text-slate-500 max-w-lg">
              One personalised email every 5 minutes (you choose the gap), so inbox providers see normal one-to-one mail, not a bulk blast.
            </p>
            <div className="mt-8 relative">
              <div className="absolute left-0 right-0 top-[15px] h-px bg-slate-300" />
              <div className="relative grid grid-cols-5 gap-2">
                {['10:00', '10:05', '10:10', '10:15', '10:20'].map((t, i) => (
                  <div key={t} className="flex flex-col items-center gap-2">
                    <span className={`w-8 h-8 rounded-full flex items-center justify-center ring-4 ring-surface ${i < 3 ? 'bg-[#557392] text-white' : 'bg-slate-100 text-slate-500 ring-1'}`}>
                      {i < 3 ? <Check className="w-4 h-4" /> : <Mail className="w-3.5 h-3.5" />}
                    </span>
                    <span className="text-[11px] font-mono text-slate-500">{t}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Excel import */}
          <div className={`${card} md:col-span-2 p-6`}>
            <div className="flex items-center gap-2 text-slate-900">
              <FileSpreadsheet className="w-5 h-5 text-slate-500" />
              <h3 className="text-base font-semibold">Excel &amp; CSV import</h3>
            </div>
            <p className="mt-2 text-sm text-slate-500">Every column becomes a tag, with no manual typing.</p>
            <div className="mt-5 space-y-2 text-xs">
              {[
                ['City', '{{city}}'],
                ['Order ID', '{{order_id}}'],
                ['Gift Item', '{{gift_item}}'],
              ].map(([col, tag]) => (
                <div key={col} className="flex items-center gap-2">
                  <span className="flex-1 px-2.5 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-slate-700">{col}</span>
                  <ArrowRight className="w-3.5 h-3.5 text-slate-400" />
                  <code className="flex-1 px-2.5 py-1.5 rounded-md bg-slate-100 text-brand-700 font-mono">{tag}</code>
                </div>
              ))}
            </div>
          </div>

          {/* AI Studio */}
          <div className={`${card} md:col-span-2 p-6`}>
            <div className="flex items-center gap-2 text-slate-900">
              <Sparkles className="w-5 h-5 text-slate-500" />
              <h3 className="text-base font-semibold">Templates + AI Studio</h3>
            </div>
            <p className="mt-2 text-sm text-slate-500">{PRESET_TEMPLATES.length} ready designs, or generate one with AI.</p>
            <div className="mt-5 rounded-lg bg-slate-50 border border-slate-200 p-3">
              <p className="text-xs text-slate-500">“A warm Diwali email with a 40% offer for loyal customers…”</p>
              <div className="mt-3 flex items-center justify-end">
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-[#557392] text-white text-[11px] font-semibold">
                  <Wand2 className="w-3 h-3" /> Generate
                </span>
              </div>
            </div>
          </div>

          {/* Live preview */}
          <div className={`${card} md:col-span-2 p-6`}>
            <div className="flex items-center gap-2 text-slate-900">
              <MonitorSmartphone className="w-5 h-5 text-slate-500" />
              <h3 className="text-base font-semibold">Live preview</h3>
            </div>
            <p className="mt-2 text-sm text-slate-500">See the exact email each person gets, on desktop and mobile.</p>
            <div className="mt-5 flex items-end justify-center gap-3">
              <div className="w-28 h-20 rounded-md bg-slate-50 border border-slate-200 p-2 space-y-1.5">
                <Monitor className="w-3.5 h-3.5 text-slate-400" />
                <span className="block h-1.5 w-3/4 rounded bg-slate-300" />
                <span className="block h-1.5 w-1/2 rounded bg-slate-200" />
                <span className="block h-1.5 w-2/3 rounded bg-slate-200" />
              </div>
              <div className="w-12 h-24 rounded-lg bg-slate-50 border border-slate-200 p-1.5 space-y-1.5">
                <Smartphone className="w-3 h-3 text-slate-400" />
                <span className="block h-1 w-full rounded bg-slate-300" />
                <span className="block h-1 w-2/3 rounded bg-slate-200" />
              </div>
            </div>
          </div>

          {/* Delivery logs */}
          <div className={`${card} md:col-span-2 p-6`}>
            <div className="flex items-center gap-2 text-slate-900">
              <ScrollText className="w-5 h-5 text-slate-500" />
              <h3 className="text-base font-semibold">Delivery logs</h3>
            </div>
            <p className="mt-2 text-sm text-slate-500">Status, time and message ID for every email. Export as CSV.</p>
            <div className="mt-5 space-y-1.5">
              {[
                ['10:05', 'Delivered'],
                ['10:10', 'Delivered'],
                ['10:15', 'Failed · retry'],
              ].map(([t, s]) => (
                <div key={t} className="flex items-center justify-between px-2.5 py-1.5 rounded-md bg-slate-50 border border-slate-200 text-[11px]">
                  <span className="font-mono text-slate-500">{t}</span>
                  <span className={s.startsWith('Failed') ? 'text-red-600 font-semibold' : 'text-brand-700 font-semibold'}>{s}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Small features row */}
          {[
            { icon: Server, title: 'Your own email', text: 'Gmail, Elastic Email, Brevo, SendGrid, SES or any SMTP.' },
            { icon: FlaskConical, title: 'Sandbox mode', text: 'Practise the full flow without sending real emails.' },
            { icon: UserRound, title: 'Personal for everyone', text: 'Names, offers and any data, filled in per recipient.' },
            { icon: Cloud, title: 'Cloud sync', text: 'Your SMTP settings, safe in your account on any device.' },
          ].map((f) => {
            const Icon = f.icon;
            return (
              <div key={f.title} className={`${card} md:col-span-3 lg:col-span-3 xl:col-span-3 p-5 flex items-start gap-4`}>
                <span className="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
                  <Icon className="w-5 h-5" />
                </span>
                <div>
                  <h3 className="text-sm font-semibold text-slate-900">{f.title}</h3>
                  <p className="mt-1 text-sm text-slate-500">{f.text}</p>
                </div>
              </div>
            );
          })}
        </div>
      </section>

      {/* ---------- How it works ---------- */}
      <section id="how" className="scroll-mt-20 border-y border-slate-200/70 bg-[#0D2139]">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 py-24">
          <div className="text-center max-w-2xl mx-auto">
            <p className={eyebrow}>How it works</p>
            <h2 className="mt-3 text-3xl md:text-5xl font-semibold tracking-[-0.02em] text-slate-900">From spreadsheet to inbox in 5 steps.</h2>
            <p className="mt-4 text-slate-500">The dashboard guides you through each step in order and ticks it off when it’s done.</p>
          </div>
          <ol className="mt-16 grid md:grid-cols-5 gap-6 md:gap-4">
            {STEP_ITEMS.map((step, i) => {
              const Icon = step.icon;
              return (
                <li key={step.id} className="relative text-center">
                  {i < STEP_ITEMS.length - 1 && (
                    <span className="hidden md:block absolute top-7 left-[calc(50%+36px)] right-[calc(-50%+36px)] h-px bg-gradient-to-r from-slate-300 to-slate-200" aria-hidden />
                  )}
                  <div className="relative mx-auto w-14 h-14 rounded-2xl bg-surface ring-1 ring-slate-300 flex items-center justify-center shadow-card">
                    <Icon className="w-6 h-6 text-slate-700" />
                    <span className="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-[#557392] text-white text-[11px] font-bold flex items-center justify-center ring-4 ring-[#0D2139]">
                      {step.step}
                    </span>
                  </div>
                  <h3 className="mt-5 text-sm font-semibold text-slate-900">{step.label}</h3>
                  <p className="mt-2 text-sm text-slate-500 leading-relaxed">{STEP_TEXT[step.id]}</p>
                </li>
              );
            })}
          </ol>
        </div>
      </section>

      {/* ---------- Why ---------- */}
      <section id="why" className="scroll-mt-20 max-w-6xl mx-auto px-4 sm:px-6 py-24">
        <div className="text-center max-w-2xl mx-auto">
          <p className={eyebrow}>Why MailDart</p>
          <h2 className="mt-3 text-3xl md:text-5xl font-semibold tracking-[-0.02em] text-slate-900">Bulk emails get blocked.</h2>
          <p className="mt-4 text-slate-500">
            Most tools blast every email at once, and inbox providers treat that as a bot. MailDart paces delivery so every email looks like
            normal, one-to-one mail.
          </p>
        </div>
        <div className="mt-14 grid md:grid-cols-2 gap-4">
          <div className={`${card} p-7`}>
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">The usual way</p>
            <h3 className="mt-1 text-xl font-semibold text-slate-900">Bulk blast</h3>
            <ul className="mt-6 space-y-3.5">
              {['Hundreds of emails leave in the same second', 'Gmail, Yahoo and Outlook flag the sender', 'Emails land in spam or get blocked', 'The same generic message for everyone'].map((t) => (
                <li key={t} className="flex items-start gap-3 text-sm text-slate-500">
                  <span className="mt-0.5 w-5 h-5 shrink-0 rounded-full bg-red-50 ring-1 ring-inset ring-red-200 flex items-center justify-center">
                    <X className="w-3 h-3 text-red-600" />
                  </span>
                  {t}
                </li>
              ))}
            </ul>
          </div>
          <div className="rounded-2xl p-7 bg-gradient-to-br from-[#1B3B5A] to-[#112A46] ring-1 ring-inset ring-[#557392]/70 shadow-card">
            <p className="text-xs font-semibold uppercase tracking-wider text-[#A1B2C4]">The MailDart way</p>
            <h3 className="mt-1 text-xl font-semibold text-[#F2F6F8]">Staggered &amp; personal</h3>
            <ul className="mt-6 space-y-3.5">
              {['One email at a time, with a gap you choose', 'Sent from your own trusted email account', 'Much better chance of reaching the Primary inbox', 'Each email personalised with the recipient’s data'].map((t) => (
                <li key={t} className="flex items-start gap-3 text-sm text-[#E1E8F0]">
                  <span className="mt-0.5 w-5 h-5 shrink-0 rounded-full bg-[#557392] flex items-center justify-center">
                    <Check className="w-3 h-3 text-white" strokeWidth={3} />
                  </span>
                  {t}
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      {/* ---------- Use cases ---------- */}
      <section className="max-w-6xl mx-auto px-4 sm:px-6 pb-24">
        <div className="text-center max-w-2xl mx-auto">
          <p className={eyebrow}>Who it’s for</p>
          <h2 className="mt-3 text-3xl md:text-4xl font-semibold tracking-[-0.02em] text-slate-900">Built for businesses that talk to customers by email.</h2>
        </div>
        <div className="mt-12 grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
          {USE_CASES.map((u) => {
            const Icon = u.icon;
            return (
              <div key={u.title} className={`${card} p-5 hover:shadow-card-hover hover:-translate-y-0.5 transition`}>
                <span className="w-9 h-9 rounded-lg bg-slate-100 ring-1 ring-inset ring-slate-300 flex items-center justify-center">
                  <Icon className="w-4.5 h-4.5 text-slate-700" />
                </span>
                <h3 className="mt-4 text-sm font-semibold text-slate-900">{u.title}</h3>
                <p className="mt-1.5 text-sm text-slate-500 leading-relaxed">{u.text}</p>
              </div>
            );
          })}
        </div>
      </section>

      {/* ---------- FAQ ---------- */}
      <section id="faq" className="scroll-mt-20 border-t border-slate-200/70">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 py-24 grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] gap-10 lg:gap-16">
          <div className="lg:sticky lg:top-24 self-start">
            <p className={eyebrow}>FAQ</p>
            <h2 className="mt-3 text-3xl md:text-5xl font-semibold tracking-[-0.02em] text-slate-900">
              Questions,
              <br />
              <span className={gradientText}>answered.</span>
            </h2>
            <p className="mt-4 text-slate-500 leading-relaxed">Everything you need to know before sending your first campaign.</p>

            <div className="mt-8 rounded-2xl bg-gradient-to-br from-[#1B3B5A] to-[#112A46] ring-1 ring-inset ring-[#34506D] p-6">
              <span className="w-10 h-10 rounded-xl bg-[#557392] text-white flex items-center justify-center shadow-button">
                <BookOpen className="w-5 h-5" />
              </span>
              <p className="mt-4 text-base font-semibold text-[#F2F6F8]">Still have questions?</p>
              <p className="mt-1 text-sm text-[#A1B2C4] leading-relaxed">
                The step-by-step guide covers SMTP setup, Excel import, templates and troubleshooting.
              </p>
              <button
                onClick={() => onOpenApp('guide')}
                className="mt-5 inline-flex items-center gap-1.5 h-10 px-4 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-white rounded-lg transition"
              >
                Read the guide
                <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          </div>

          <div className="space-y-3">
            {FAQS.map((f, i) => {
              const open = openFaq === i;
              return (
                <div
                  key={f.q}
                  className={`relative rounded-2xl border transition overflow-hidden ${
                    open ? 'bg-surface border-[#557392]/70 shadow-card' : 'bg-surface/50 border-slate-200/80 hover:border-slate-300'
                  }`}
                >
                  {open && <span className="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-[#C4D2E1] to-[#557392]" aria-hidden />}
                  <button
                    onClick={() => setOpenFaq(open ? null : i)}
                    aria-expanded={open}
                    className="w-full flex items-center gap-4 px-6 py-5 text-left"
                  >
                    <span className={`text-xs font-mono font-semibold tabular-nums ${open ? 'text-slate-700' : 'text-slate-400'}`}>
                      {String(i + 1).padStart(2, '0')}
                    </span>
                    <span className={`flex-1 text-[15px] font-semibold ${open ? 'text-slate-900' : 'text-slate-800'}`}>{f.q}</span>
                    <span
                      className={`w-8 h-8 shrink-0 rounded-full flex items-center justify-center ring-1 ring-inset transition ${
                        open ? 'bg-[#557392] ring-[#557392] text-white rotate-45' : 'ring-slate-300 text-slate-500'
                      }`}
                    >
                      <Plus className="w-4 h-4" />
                    </span>
                  </button>
                  {open && <p className="pl-[3.75rem] pr-6 pb-6 -mt-1 text-sm text-slate-500 leading-relaxed animate-fade-in">{f.a}</p>}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* ---------- Final CTA ---------- */}
      <section className="max-w-6xl mx-auto px-4 sm:px-6 pb-24">
        <div className="relative rounded-[28px] p-px bg-gradient-to-br from-[#A1B2C4]/60 via-[#34506D]/60 to-[#1B3B5A]/40 shadow-[0_40px_120px_-40px_rgb(85_115_146/0.6)]">
          <div className="relative overflow-hidden rounded-[27px] bg-gradient-to-br from-[#1B3B5A] via-[#112A46] to-[#0A1B2E] px-6 py-12 md:px-14 md:py-16">
            <div className="absolute -top-32 -right-24 w-[420px] h-[420px] rounded-full bg-[radial-gradient(closest-side,rgb(122_140_166/0.35),transparent)]" aria-hidden />
            <div className="absolute inset-0 [background-image:linear-gradient(rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(90deg,rgb(255_255_255/0.04)_1px,transparent_1px)] [background-size:40px_40px] [mask-image:linear-gradient(to_left,black,transparent_70%)]" aria-hidden />

            <div className="relative grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-[#A1B2C4]">Get started today</p>
                <h2 className="mt-3 text-3xl md:text-5xl font-semibold tracking-[-0.02em] leading-[1.1] text-[#F2F6F8]">
                  Your next campaign,
                  <br />
                  straight to the inbox.
                </h2>
                <ul className="mt-7 space-y-3">
                  {['Set up in about 5 minutes', 'Practise safely in Sandbox mode first', 'Send from your own Gmail or SMTP account'].map((t) => (
                    <li key={t} className="flex items-center gap-3 text-sm text-[#E1E8F0]">
                      <span className="w-5 h-5 shrink-0 rounded-full bg-[#557392] flex items-center justify-center">
                        <Check className="w-3 h-3 text-white" strokeWidth={3} />
                      </span>
                      {t}
                    </li>
                  ))}
                </ul>
                <div className="mt-9 flex flex-wrap gap-3">
                  <button onClick={primaryAction} className={primaryBtn}>
                    {isLoggedIn ? 'Open Dashboard' : 'Create free account'}
                    <ArrowRight className="w-4 h-4" />
                  </button>
                  {!isLoggedIn && (
                    <button
                      onClick={onLogin}
                      className="inline-flex items-center justify-center gap-2 h-12 px-6 text-sm font-medium text-[#F2F6F8] bg-white/5 hover:bg-white/10 ring-1 ring-inset ring-white/20 rounded-xl transition"
                    >
                      Log in
                    </button>
                  )}
                </div>
              </div>

              {/* "Campaign ready" visual */}
              <div className="relative mx-auto w-full max-w-sm">
                <div className="absolute -inset-4 rounded-3xl bg-[#557392]/20 blur-2xl" aria-hidden />
                <div className="relative rounded-2xl bg-[#0E2440]/90 ring-1 ring-[#34506D] p-5 backdrop-blur">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-semibold text-[#F2F6F8]">Diwali Campaign</p>
                    <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#1B3B5A] text-[#C4D2E1] ring-1 ring-inset ring-[#34506D]">Ready to send</span>
                  </div>
                  <ol className="mt-4 space-y-2">
                    {STEP_ITEMS.map((s) => (
                      <li key={s.id} className="flex items-center gap-3 rounded-lg bg-[#112A46] ring-1 ring-[#1F4060] px-3 py-2.5">
                        <span className="w-5 h-5 shrink-0 rounded-full bg-[#557392] flex items-center justify-center">
                          <Check className="w-3 h-3 text-white" strokeWidth={3} />
                        </span>
                        <span className="text-xs font-medium text-[#E1E8F0]">{s.label}</span>
                      </li>
                    ))}
                  </ol>
                  <div className="mt-4 flex items-center justify-between rounded-lg bg-[#E1E8F0] px-4 py-2.5">
                    <span className="text-xs font-semibold text-[#0A1B2E]">Start 5m campaign</span>
                    <ArrowRight className="w-4 h-4 text-[#0A1B2E]" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ---------- Footer ---------- */}
      <footer className="border-t border-slate-200/70">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 py-12 grid sm:grid-cols-2 md:grid-cols-4 gap-8 text-sm">
          <div className="sm:col-span-2">
            <AppLogo size="md" showTagline={false} />
            <p className="mt-3 text-slate-500 max-w-xs">Smart, staggered email campaigns that reach the inbox, from your own email account.</p>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Product</p>
            <ul className="mt-3 space-y-2">
              {[
                ['features', 'Features'],
                ['how', 'How it works'],
                ['why', 'Why MailDart'],
                ['faq', 'FAQ'],
              ].map(([id, label]) => (
                <li key={id}>
                  <button onClick={() => scrollTo(id)} className="text-slate-500 hover:text-slate-900 transition">
                    {label}
                  </button>
                </li>
              ))}
            </ul>
          </div>
          <div>
            <p className="text-xs font-semibold uppercase tracking-wider text-slate-400">Get started</p>
            <ul className="mt-3 space-y-2">
              {(isLoggedIn
                ? [['Open Dashboard', () => onOpenApp()], ['Guide', () => onOpenApp('guide')]]
                : [['Sign up free', onSignup], ['Log in', onLogin], ['Guide', () => onOpenApp('guide')]]
              ).map(([label, fn]) => (
                <li key={label as string}>
                  <button onClick={fn as () => void} className="text-slate-500 hover:text-slate-900 transition">
                    {label as string}
                  </button>
                </li>
              ))}
            </ul>
          </div>
        </div>
        <div className="border-t border-slate-200/70">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 py-5 text-xs text-slate-400">© {new Date().getFullYear()} MailDart Pro. All rights reserved.</div>
        </div>
      </footer>
    </div>
  );
};
