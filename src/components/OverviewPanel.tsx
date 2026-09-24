import React from 'react';
import { LayoutDashboard, CheckCircle2, ArrowRight, Clock } from 'lucide-react';
import { CampaignState, SmtpConfig, ViewSection } from '../types';
import { getFestivalTitle, getSmtpStatus } from '../utils/campaignHelper';
import { STEP_ITEMS, TOTAL_STEPS } from '../data/navigation';

interface OverviewPanelProps {
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
  stepDone: Partial<Record<ViewSection, boolean>>;
  onNavigate: (section: ViewSection) => void;
}

const STATUS_META: Record<CampaignState['status'], { label: string; className: string }> = {
  idle: { label: 'Draft / Ready', className: 'bg-slate-100 ring-slate-300 text-slate-600' },
  running: { label: 'Sending', className: 'bg-amber-50 ring-amber-200 text-amber-800' },
  paused: { label: 'Paused', className: 'bg-amber-50 ring-amber-200 text-amber-800' },
  completed: { label: 'Completed', className: 'bg-brand-50 ring-brand-200 text-brand-700' },
};

const formatDuration = (minutes: number) => {
  if (minutes <= 0) return '—';
  if (minutes < 60) return `${Math.round(minutes)} min`;
  const h = Math.floor(minutes / 60);
  const m = Math.round(minutes % 60);
  return m ? `${h} h ${m} min` : `${h} h`;
};

export const OverviewPanel: React.FC<OverviewPanelProps> = ({ campaign, smtpConfig, stepDone, onNavigate }) => {
  const total = campaign.recipients.length;
  const delivered = campaign.recipients.filter((r) => r.status === 'delivered').length;
  const failed = campaign.recipients.filter((r) => r.status === 'failed').length;
  const pending = campaign.recipients.filter((r) => r.status === 'pending').length;
  const progressPercent = total > 0 ? Math.round((delivered / total) * 100) : 0;

  const doneCount = STEP_ITEMS.filter((s) => stepDone[s.id]).length;
  const nextStep = STEP_ITEMS.find((s) => !stepDone[s.id]);
  const status = STATUS_META[campaign.status];
  const smtpStatus = getSmtpStatus(smtpConfig);

  const stepDetail: Record<string, string> = {
    settings:
      smtpStatus === 'live'
        ? `Sending from ${smtpConfig.fromEmail || smtpConfig.username} via ${smtpConfig.host}:${smtpConfig.port}`
        : smtpStatus === 'incomplete'
        ? 'Add your email and App Password so real emails can be delivered.'
        : 'Sandbox mode is on: emails are only simulated.',
    recipients: total > 0 ? `${total} recipients loaded, ${pending} waiting to be sent.` : 'Upload an Excel / CSV sheet or add people one by one.',
    editor: campaign.subject.trim()
      ? `Subject: “${campaign.subject}”`
      : 'Pick a template, generate one with AI, or paste your own HTML.',
    dispatch:
      campaign.status === 'running'
        ? `Sending now: one email every ${campaign.intervalMinutes} min.`
        : campaign.status === 'completed'
        ? 'Campaign completed.'
        : `Start sending: one email every ${campaign.intervalMinutes} min.`,
    logs: campaign.logs.length > 0 ? `${campaign.logs.length} dispatches recorded.` : 'Every sent email will appear here.',
  };

  const stats = [
    { label: 'Total recipients', value: total, className: 'text-slate-900' },
    { label: 'Delivered', value: delivered, className: 'text-brand-800' },
    { label: 'Pending', value: pending, className: 'text-slate-900' },
    { label: 'Failed', value: failed, className: failed > 0 ? 'text-red-600' : 'text-slate-900' },
  ];

  return (
    <div className="space-y-6">
      {/* Hero: overall setup progress + next step */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0A1B2E] via-[#112A46] to-[#1B3B5A] ring-1 ring-inset ring-[#34506D]/60 text-white shadow-card">
        <div className="absolute inset-0 opacity-40 [background-image:radial-gradient(rgb(255_255_255/0.12)_1px,transparent_1px)] [background-size:18px_18px] [mask-image:linear-gradient(to_left,black,transparent_70%)]" />
        <div className="relative p-6 md:p-8 flex flex-col xl:flex-row xl:items-end justify-between gap-6">
          <div className="max-w-2xl min-w-0">
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 ring-1 ring-inset ring-white/15 text-[11px] font-semibold uppercase tracking-[0.08em] text-[#E1E8F0]">
              <LayoutDashboard className="w-3.5 h-3.5" />
              {getFestivalTitle(campaign.festival)}
            </span>
            <h2 className="mt-4 text-2xl md:text-3xl font-semibold tracking-tight">{campaign.companyName || 'Your campaign'}</h2>
            <p className="mt-2 text-sm md:text-base text-[#C4D2E1] leading-relaxed">
              {nextStep
                ? `${doneCount} of ${TOTAL_STEPS} steps done. Next up: step ${nextStep.step}, ${nextStep.label}.`
                : 'All steps are done. Your campaign is set up and on its way.'}
            </p>
            <div className="mt-4 flex items-center gap-3 max-w-md">
              <div className="flex-1 h-1.5 rounded-full bg-white/10 overflow-hidden">
                <div className="h-full rounded-full bg-[#A1B2C4] transition-all duration-500" style={{ width: `${(doneCount / TOTAL_STEPS) * 100}%` }} />
              </div>
              <span className="text-xs font-semibold tabular-nums text-[#E1E8F0]">
                {doneCount}/{TOTAL_STEPS}
              </span>
            </div>
          </div>
          <button
            onClick={() => onNavigate(nextStep ? nextStep.id : 'logs')}
            className="shrink-0 self-start xl:self-auto inline-flex items-center gap-2 h-11 px-5 text-sm font-semibold text-[#0A1B2E] bg-[#E1E8F0] hover:bg-[#FFFFFF] rounded-lg shadow-button transition"
          >
            {nextStep ? `Continue: ${nextStep.label}` : 'View Delivery Logs'}
            <ArrowRight className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Campaign stats */}
      <div className="bg-surface border border-slate-200/80 rounded-2xl shadow-card p-5">
        <div className="flex items-center justify-between gap-3 mb-4">
          <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Campaign progress</p>
          <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold ring-1 ring-inset rounded-full ${status.className}`}>
            {campaign.status === 'running' && <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />}
            {status.label}
          </span>
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          {stats.map((s) => (
            <div key={s.label} className="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5">
              <p className="text-[11px] font-medium text-slate-500">{s.label}</p>
              <p className={`text-2xl font-bold tabular-nums ${s.className}`}>{s.value}</p>
            </div>
          ))}
        </div>
        <div className="mt-4 space-y-1.5">
          <div className="flex items-center justify-between text-xs text-slate-500">
            <span>
              Sent <strong className="text-slate-900">{progressPercent}%</strong>
            </span>
            <span className="flex items-center gap-1">
              <Clock className="w-3.5 h-3.5" />
              Every {campaign.intervalMinutes} min · ~{formatDuration(pending * campaign.intervalMinutes)} remaining
            </span>
          </div>
          <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
            <div className="h-full bg-brand-600 rounded-full transition-all duration-500" style={{ width: `${progressPercent}%` }} />
          </div>
        </div>
      </div>

      {/* The five steps */}
      <div className="bg-surface border border-slate-200/80 rounded-2xl shadow-card">
        <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
          <div>
            <h3 className="text-base font-semibold text-slate-900">Campaign steps</h3>
            <p className="text-sm text-slate-500">Follow these in order. Each one opens the right page.</p>
          </div>
          <span className="text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-slate-100 ring-slate-300 text-slate-600 tabular-nums">
            {doneCount} of {TOTAL_STEPS} done
          </span>
        </div>
        <ol className="divide-y divide-slate-100">
          {STEP_ITEMS.map((step) => {
            const Icon = step.icon;
            const done = !!stepDone[step.id];
            const isNext = nextStep?.id === step.id;
            return (
              <li key={step.id}>
                <button
                  onClick={() => onNavigate(step.id)}
                  className={`w-full text-left px-5 py-4 flex items-center gap-4 transition hover:bg-slate-50 ${isNext ? 'bg-brand-50/60' : ''}`}
                >
                  <span
                    className={`w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-sm font-semibold ${
                      done ? 'bg-brand-600 text-white' : isNext ? 'ring-2 ring-inset ring-brand-400 text-brand-800' : 'ring-1 ring-inset ring-slate-300 text-slate-500'
                    }`}
                  >
                    {done ? <CheckCircle2 className="w-4 h-4" /> : step.step}
                  </span>
                  <span className="w-9 h-9 shrink-0 rounded-lg bg-slate-50 border border-slate-200 text-slate-400 flex items-center justify-center">
                    <Icon className="w-4 h-4" />
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="flex items-center gap-2">
                      <span className="text-sm font-semibold text-slate-900">{step.label}</span>
                      {isNext && (
                        <span className="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-brand-600 text-white">Next</span>
                      )}
                    </span>
                    <span className="block text-xs text-slate-500 truncate">{stepDetail[step.id]}</span>
                  </span>
                  <span className="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-brand-700">
                    {done ? 'Review' : 'Open'}
                    <ArrowRight className="w-3.5 h-3.5" />
                  </span>
                </button>
              </li>
            );
          })}
        </ol>
      </div>
    </div>
  );
};
