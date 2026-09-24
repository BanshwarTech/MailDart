import React from 'react';
import { Sparkles, Mail, Settings, HelpCircle, ShieldCheck, Play, Pause, CheckCircle2 } from 'lucide-react';
import { CampaignState, SmtpConfig } from '../types';
import { AppLogo } from './AppLogo';

interface HeaderProps {
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
  onOpenGuide: () => void;
  onOpenSmtpSettings: () => void;
  onOpenTestEmail: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  campaign,
  smtpConfig,
  onOpenGuide,
  onOpenSmtpSettings,
  onOpenTestEmail,
}) => {
  const getStatusBadge = () => {
    switch (campaign.status) {
      case 'running':
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-semibold rounded-full animate-pulse">
            <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
            Running 5m Dispatch
          </span>
        );
      case 'paused':
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-amber-500/15 border border-amber-500/30 text-amber-400 text-xs font-semibold rounded-full">
            <Pause className="w-3 h-3" />
            Dispatch Paused
          </span>
        );
      case 'completed':
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-purple-500/15 border border-purple-500/30 text-purple-400 text-xs font-semibold rounded-full">
            <CheckCircle2 className="w-3 h-3" />
            Campaign Completed
          </span>
        );
      default:
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-slate-800 border border-slate-700 text-slate-400 text-xs font-medium rounded-full">
            Draft / Ready
          </span>
        );
    }
  };

  return (
    <header className="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 px-4 lg:px-8 py-3.5 transition-all">
      <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        
        {/* Brand & All-Purpose Logo */}
        <div className="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
          <AppLogo />

          <div className="sm:hidden">
            {getStatusBadge()}
          </div>
        </div>

        {/* Center / Status */}
        <div className="hidden sm:flex items-center gap-3">
          {getStatusBadge()}
          
          <div className="flex items-center gap-1.5 text-xs text-slate-400 bg-slate-850 px-2.5 py-1 rounded-lg border border-slate-800">
            <span className="text-slate-500">Company:</span>
            <span className="text-white font-medium truncate max-w-[140px]">
              {campaign.companyName || 'Not Set'}
            </span>
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
          
          <button
            onClick={onOpenGuide}
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-750 border border-slate-700 rounded-xl transition"
            title="Read Guide in Hindi / English"
          >
            <HelpCircle className="w-3.5 h-3.5 text-amber-400" />
            <span>Guide (गाइड)</span>
          </button>

          <button
            onClick={onOpenTestEmail}
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-200 bg-slate-800 hover:bg-slate-750 border border-slate-700 rounded-xl transition"
            title="Send a single test email immediately"
          >
            <Mail className="w-3.5 h-3.5 text-sky-400" />
            <span className="hidden md:inline">Test Send</span>
          </button>

          <button
            onClick={onOpenSmtpSettings}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-xl border transition ${
              smtpConfig.enabled && (smtpConfig.fromEmail || smtpConfig.username)
                ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/50 hover:bg-emerald-500/20'
                : 'bg-amber-500/10 text-amber-300 border-amber-500/50 hover:bg-amber-500/20'
            }`}
            title="Configure User SMTP Delivery Account"
          >
            <Settings className="w-3.5 h-3.5" />
            <div className="flex flex-col text-left">
              <span className="text-[9px] uppercase tracking-wider text-slate-400">Sender Account:</span>
              <span className="font-mono text-[11px] max-w-[170px] truncate font-bold text-white">
                {smtpConfig.enabled
                  ? (smtpConfig.fromEmail || smtpConfig.username || 'Real SMTP Active')
                  : 'Sandbox Mode'}
              </span>
            </div>
          </button>

        </div>

      </div>
    </header>
  );
};
