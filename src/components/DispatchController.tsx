import React, { useState } from 'react';
import {
  Play,
  Pause,
  RotateCcw,
  Zap,
  Clock,
  CheckCircle2,
  AlertCircle,
  Users,
  Send,
  Sparkles,
  ChevronRight,
  ShieldCheck,
  Flame,
  Info
} from 'lucide-react';
import { CampaignState, SmtpConfig, Recipient } from '../types';
import { formatTimeRemaining, replacePlaceholders, getSmtpStatus } from '../utils/campaignHelper';

interface DispatchControllerProps {
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
  onStartCampaign: () => void;
  onPauseCampaign: () => void;
  onResetCampaign: () => void;
  onSendNextImmediately: () => void;
  onUpdateInterval: (minutes: number) => void;
}

export const DispatchController: React.FC<DispatchControllerProps> = ({
  campaign,
  smtpConfig,
  onStartCampaign,
  onPauseCampaign,
  onResetCampaign,
  onSendNextImmediately,
  onUpdateInterval,
}) => {
  const [customMinutes, setCustomMinutes] = useState(campaign.intervalMinutes.toString());

  const total = campaign.recipients.length;
  const delivered = campaign.recipients.filter((r) => r.status === 'delivered').length;
  const failed = campaign.recipients.filter((r) => r.status === 'failed').length;
  const pending = campaign.recipients.filter((r) => r.status === 'pending').length;
  const sending = campaign.recipients.filter((r) => r.status === 'sending').length;

  const progressPercent = total > 0 ? Math.round((delivered / total) * 100) : 0;

  // Next recipient to be sent
  const nextRecipient: Recipient | undefined = campaign.recipients.find(
    (r) => r.status === 'pending'
  );

  // Estimated time remaining (in minutes)
  const estMinutesRemaining = Math.max(0, pending * campaign.intervalMinutes);

  const handleCustomIntervalSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const val = parseFloat(customMinutes);
    if (!isNaN(val) && val > 0) {
      onUpdateInterval(val);
    }
  };

  return (
    <div className="bg-surface border border-slate-200/80 rounded-2xl p-5 md:p-6 shadow-card">
      


      {/* Header & Core Status */}
      <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-5 mb-5 border-b border-slate-100">
        <div>
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
              <Clock className="w-5 h-5" />
            </div>
            <h2 className="text-base font-semibold tracking-tight text-slate-900">
              Staggered Email Dispatcher
            </h2>
            <span className="hidden md:inline-flex text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-full whitespace-nowrap">
              Anti-Spam Throttling
            </span>
          </div>
          <p className="text-sm text-slate-500 mt-1 lg:pl-[52px]">
            Sends personalized festival emails to recipients every {campaign.intervalMinutes} minutes
          </p>
        </div>

        {/* Interval Selection presets */}
        <div className="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-lg">
          <span className="text-[11px] font-semibold text-slate-500 px-2">Every:</span>
          {[
            { label: '5 Min (Recommended)', value: 5, highlight: true },
            { label: '2 Min', value: 2 },
            { label: '1 Min', value: 1 },
            { label: '30 Sec (Test)', value: 0.5 },
          ].map((item) => (
            <button
              key={item.value}
              type="button"
              disabled={campaign.status === 'running'}
              onClick={() => onUpdateInterval(item.value)}
              className={`px-2.5 py-1 text-xs rounded-md font-medium transition ${ campaign.intervalMinutes === item.value ? item.highlight ? 'bg-brand-600 text-white font-semibold shadow-sm'
                    : 'bg-slate-300 text-slate-900 shadow-sm'
                  : 'text-slate-500 hover:text-slate-900 disabled:opacity-50'
              }`}
            >
              {item.label}
            </button>
          ))}
        </div>
      </div>

      {/* Primary Action Card: Countdown & Big Controls */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        
        {/* Countdown Box */}
        <div className="bg-gradient-to-b from-brand-50 to-surface border border-brand-100 rounded-xl p-5 flex flex-col items-center justify-center text-center">
          <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1 flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5 text-brand-700" /> Next Email Dispatch In
          </div>
          
          <div className="text-4xl md:text-5xl font-bold font-mono tracking-tight text-slate-900 tabular-nums my-2">
            {campaign.status === 'running'
              ? formatTimeRemaining(campaign.remainingSeconds)
              : campaign.status === 'paused'
              ? formatTimeRemaining(campaign.remainingSeconds)
              : formatTimeRemaining(campaign.intervalMinutes * 60)}
          </div>

          <div className="text-xs text-slate-500 mt-1 flex items-center gap-1">
            {campaign.status === 'running' ? (
              <span className="text-brand-700 font-medium flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-brand-500 animate-pulse inline-block" />
                Active countdown ticking...
              </span>
            ) : campaign.status === 'paused' ? (
              <span className="text-amber-700 font-medium">Paused at current timer</span>
            ) : (
              <span className="text-slate-500">Waiting for start trigger</span>
            )}
          </div>
        </div>

        {/* Next Recipient in Queue */}
        <div className="bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between">
          <div>
            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1 flex items-center justify-between">
              <span className="flex items-center gap-1.5">
                <Users className="w-3.5 h-3.5 text-brand-700" /> Next In Queue
              </span>
              <span className="text-[11px] text-brand-700 bg-brand-50 border border-brand-100 px-1.5 py-0.5 rounded font-semibold font-mono normal-case tracking-normal">
                #{total - pending + 1} of {total}
              </span>
            </div>

            {nextRecipient ? (
              <div className="mt-2 space-y-1">
                <div className="text-sm font-semibold text-slate-900 truncate">
                  {nextRecipient.name}
                </div>
                <div className="text-xs text-slate-500 font-mono truncate">
                  {nextRecipient.email}
                </div>
                <div className="text-xs text-slate-600 truncate pt-1.5">
                  Preview Subject: "{replacePlaceholders(campaign.subject, nextRecipient, campaign)}"
                </div>
              </div>
            ) : (
              <div className="text-sm text-brand-700 font-medium py-4 flex items-center gap-2">
                <CheckCircle2 className="w-4 h-4" /> All queued emails have been dispatched!
              </div>
            )}
          </div>

          {nextRecipient && (
            <button
              onClick={onSendNextImmediately}
              disabled={campaign.recipients.length === 0}
              className="mt-3 w-full py-2 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 shadow-xs rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition"
              title="Skip remaining minutes and dispatch to this recipient immediately"
            >
              <Zap className="w-3.5 h-3.5 text-amber-500" />
              <span>Send To This User Immediately (Skip Timer)</span>
            </button>
          )}
        </div>

        {/* Master Execution Controls */}
        <div className="bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between gap-3">
          <div>
            <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2 flex items-center justify-between">
              <span>Campaign Controls</span>
              <span className="text-[10px] font-medium normal-case tracking-normal text-slate-500">
                {{ live: '🟢 Live', incomplete: '🟠 Setup needed', sandbox: '🟠 Sandbox' }[getSmtpStatus(smtpConfig)]}
              </span>
            </div>

            {/* Outgoing Mail Sender Display */}
            <div className="mb-3 px-2.5 py-2 rounded-lg bg-surface border border-slate-200 text-[11px] flex items-center justify-between">
              <span className="text-slate-500">Sender:</span>
              <span className="font-mono text-slate-900 font-semibold truncate max-w-[160px]" title={smtpConfig.fromEmail || smtpConfig.username || 'Sandbox'}>
                {getSmtpStatus(smtpConfig) === 'live' ? (smtpConfig.fromEmail || smtpConfig.username) : getSmtpStatus(smtpConfig) === 'incomplete' ? 'SMTP not configured' : 'Sandbox (Simulated)'}
              </span>
            </div>

            <div className="space-y-2">
              {campaign.status !== 'running' ? (
                <button
                  onClick={onStartCampaign}
                  disabled={total === 0 || pending === 0}
                  className="w-full py-2.5 px-4 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm rounded-lg flex items-center justify-center gap-2 transition shadow-button"
                >
                  <Play className="w-4 h-4 fill-white" />
                  <span>
                    {campaign.status === 'paused' ? 'Resume 5m Campaign' : 'Start 5m Campaign'}
                  </span>
                </button>
              ) : (
                <button
                  onClick={onPauseCampaign}
                  className="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm rounded-lg flex items-center justify-center gap-2 transition shadow-button"
                >
                  <Pause className="w-4 h-4" />
                  <span>Pause Campaign</span>
                </button>
              )}

              <button
                onClick={onResetCampaign}
                className="w-full py-2 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 shadow-xs rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition"
              >
                <RotateCcw className="w-3.5 h-3.5 text-slate-400" />
                <span>Reset All Statuses to Pending</span>
              </button>
            </div>
          </div>

          <div className="text-[11px] text-slate-500 flex items-center justify-between border-t border-slate-200 pt-2.5">
            <span>Estimated Total Time:</span>
            <span className="text-slate-900 font-mono font-semibold">
              ~{estMinutesRemaining} mins remaining
            </span>
          </div>
        </div>

      </div>

      {/* Progress Bar & Metric Pills */}
      <div className="space-y-2">
        <div className="flex items-center justify-between text-xs">
          <span className="text-slate-600 font-medium">
            Overall Progress: <strong className="text-slate-900">{progressPercent}%</strong> ({delivered} of {total} sent)
          </span>
          <div className="flex items-center gap-3 font-mono text-[11px]">
            <span className="text-brand-700 font-semibold">✓ {delivered} Delivered</span>
            <span className="text-slate-600">⏳ {pending} Pending</span>
            {failed > 0 && <span className="text-red-600">✕ {failed} Failed</span>}
          </div>
        </div>

        {/* Progress track */}
        <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden flex">
          <div
            className="h-full bg-brand-500 rounded-full transition-all duration-500"
            style={{ width: `${progressPercent}%` }}
          />
        </div>
      </div>

    </div>
  );
};
