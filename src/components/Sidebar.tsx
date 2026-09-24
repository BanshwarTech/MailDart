import React from 'react';
import { Check, X } from 'lucide-react';
import { CampaignState, SmtpConfig, ViewSection } from '../types';
import { getSmtpStatus } from '../utils/campaignHelper';
import { AppLogo } from './AppLogo';
import { NAV_ITEMS, NavItem, TOTAL_STEPS } from '../data/navigation';

interface SidebarProps {
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
  activeSection: ViewSection;
  onNavigate: (section: ViewSection) => void;
  stepDone: Partial<Record<ViewSection, boolean>>;
  counts: Partial<Record<ViewSection, number>>;
  isSending: boolean;
  mobileOpen: boolean;
  onCloseMobile: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({
  campaign,
  smtpConfig,
  activeSection,
  onNavigate,
  stepDone,
  counts,
  isSending,
  mobileOpen,
  onCloseMobile,
}) => {
  const doneCount = NAV_ITEMS.filter((n) => n.step && stepDone[n.id]).length;

  const total = campaign.recipients.length;
  const delivered = campaign.recipients.filter((r) => r.status === 'delivered').length;
  const progress = total > 0 ? Math.round((delivered / total) * 100) : 0;
  const smtpStatus = getSmtpStatus(smtpConfig);

  const STATUS: Record<CampaignState['status'], { label: string; dot: string }> = {
    idle: { label: 'Draft / Ready', dot: 'bg-[#7A8CA6]' },
    running: { label: 'Sending', dot: 'bg-amber-500 animate-pulse' },
    paused: { label: 'Paused', dot: 'bg-amber-500' },
    completed: { label: 'Completed', dot: 'bg-[#C4D2E1]' },
  };

  const go = (id: ViewSection) => {
    onNavigate(id);
    onCloseMobile();
  };

  const renderItem = (item: NavItem) => {
    const Icon = item.icon;
    const active = activeSection === item.id;
    const done = !!stepDone[item.id];
    const count = counts[item.id];

    return (
      <li key={item.id} className="relative">
        <button
          onClick={() => go(item.id)}
          aria-current={active ? 'page' : undefined}
          className={`group w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition ${
            active ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
          }`}
        >
          {item.step ? (
            <span
              className={`relative z-10 w-5 h-5 shrink-0 rounded-full flex items-center justify-center text-[10px] font-bold ${
                done
                  ? 'bg-brand-600 text-white'
                  : active
                  ? 'bg-surface ring-1 ring-inset ring-brand-400 text-brand-800'
                  : 'bg-surface ring-1 ring-inset ring-slate-300 text-slate-500 group-hover:text-slate-700'
              }`}
            >
              {done ? <Check className="w-3 h-3" strokeWidth={3} /> : item.step}
            </span>
          ) : (
            <Icon className={`w-[18px] h-[18px] shrink-0 ${active ? 'text-brand-700' : 'text-slate-400 group-hover:text-slate-600'}`} />
          )}
          <span className="flex-1 text-left">{item.label}</span>
          {item.id === 'dispatch' && isSending && (
            <span className="relative flex w-2 h-2" title="Sending in progress">
              <span className="absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75 animate-ping" />
              <span className="relative inline-flex w-2 h-2 rounded-full bg-amber-500" />
            </span>
          )}
          {count !== undefined && (
            <span
              className={`min-w-[22px] px-1.5 py-0.5 rounded-md text-[11px] font-semibold tabular-nums text-center ${
                active ? 'bg-surface text-brand-700 ring-1 ring-brand-100' : 'bg-slate-100 text-slate-500'
              }`}
            >
              {count}
            </span>
          )}
        </button>
      </li>
    );
  };

  const overview = NAV_ITEMS.filter((n) => n.id === 'overview');
  const steps = NAV_ITEMS.filter((n) => n.step);
  const help = NAV_ITEMS.filter((n) => !n.step && n.id !== 'overview');

  const content = (
    <div className="flex flex-col h-full">
      {/* Brand */}
      <div className="h-16 px-5 flex items-center justify-between border-b border-slate-200/80">
        <button onClick={() => go('home')} title="MailDart home" className="rounded-lg focus-visible:outline-offset-4">
          <AppLogo size="md" showTagline={false} />
        </button>
        <button
          onClick={onCloseMobile}
          className="lg:hidden p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
          aria-label="Close menu"
        >
          <X className="w-5 h-5" />
        </button>
      </div>

      <nav className="flex-1 overflow-y-auto px-3 py-5 space-y-6">
        <ul className="space-y-0.5">{overview.map(renderItem)}</ul>

        <div>
          <div className="px-3 mb-2 flex items-center justify-between">
            <p className="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Campaign steps</p>
            <span className="text-[11px] font-semibold text-slate-500 tabular-nums">
              {doneCount}/{TOTAL_STEPS} done
            </span>
          </div>
          <ol className="relative space-y-0.5">
            {/* Connector line between step numbers */}
            <span className="absolute left-[22px] top-5 bottom-5 w-px bg-slate-300/60" aria-hidden />
            {steps.map(renderItem)}
          </ol>
        </div>

        <div>
          <p className="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Help</p>
          <ul className="space-y-0.5">{help.map(renderItem)}</ul>
        </div>
      </nav>

      {/* Campaign summary card */}
      <div className="p-3 border-t border-slate-200/80">
        <button
          type="button"
          onClick={() => go('dispatch')}
          title="Open the Dispatcher"
          className="w-full text-left rounded-xl bg-gradient-to-br from-[#1B3B5A] to-[#0A1B2E] ring-1 ring-inset ring-[#34506D]/70 hover:ring-[#557392] p-4 text-white shadow-card transition"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#A1B2C4]">Campaign</span>
            <span className="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#E1E8F0]">
              <span className={`w-1.5 h-1.5 rounded-full ${STATUS[campaign.status].dot}`} />
              {STATUS[campaign.status].label}
            </span>
          </div>
          <p className="mt-2 text-sm font-semibold text-[#F2F6F8] truncate">{campaign.companyName || 'Untitled campaign'}</p>
          <div className="mt-3 flex items-baseline justify-between text-xs">
            <span className="text-[#A1B2C4]">
              {delivered} / {total} sent
            </span>
            <span className="font-semibold tabular-nums text-[#F2F6F8]">{progress}%</span>
          </div>
          <div className="mt-1.5 h-1.5 rounded-full bg-white/10 overflow-hidden">
            <div className="h-full rounded-full bg-gradient-to-r from-[#557392] to-[#C4D2E1] transition-all duration-500" style={{ width: `${progress}%` }} />
          </div>
          <div className="mt-3 pt-3 border-t border-white/10 flex items-center justify-between text-[11px]">
            <span className="text-[#A1B2C4]">Every {campaign.intervalMinutes} min</span>
            <span className={smtpStatus === 'live' ? 'text-[#C4D2E1]' : 'text-amber-400'}>
              {{ live: '● Live SMTP', incomplete: '● SMTP setup needed', sandbox: '● Sandbox' }[smtpStatus]}
            </span>
          </div>
        </button>
      </div>
    </div>
  );

  return (
    <>
      {/* Desktop */}
      <aside className="hidden lg:block fixed inset-y-0 left-0 z-30 w-64 bg-surface/80 backdrop-blur-xl border-r border-slate-200/80">
        {content}
      </aside>

      {/* Mobile drawer */}
      {mobileOpen && (
        <div className="lg:hidden fixed inset-0 z-50">
          <div className="absolute inset-0 bg-slate-950/40 backdrop-blur-[2px] animate-fade-in" onClick={onCloseMobile} />
          <aside className="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-surface shadow-modal animate-slide-in">
            {content}
          </aside>
        </div>
      )}
    </>
  );
};
