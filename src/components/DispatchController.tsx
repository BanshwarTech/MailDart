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
import { formatTimeRemaining, replacePlaceholders } from '../utils/campaignHelper';

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
    <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 md:p-6 shadow-xl relative overflow-hidden">
      
      {/* Background festive glow */}
      <div className="absolute top-0 right-0 w-72 h-72 bg-amber-500/5 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-72 h-72 bg-sky-500/5 rounded-full blur-3xl pointer-events-none" />

      {/* Header & Core Status */}
      <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6 relative z-10">
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-lg font-bold text-white flex items-center gap-2">
              <Clock className="w-5 h-5 text-amber-400" />
              ऑटोमेटेड 5-मिनट डिस्पैचर इंजन (Staggered Interval Dispatcher)
            </h2>
            <span className="text-[11px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded-full">
              Anti-Spam Throttling
            </span>
          </div>
          <p className="text-xs text-slate-400 mt-0.5">
            Sends personalized festival emails to recipients every {campaign.intervalMinutes} minutes
          </p>
        </div>

        {/* Interval Selection presets */}
        <div className="flex flex-wrap items-center gap-1.5 bg-slate-850 p-1 rounded-xl border border-slate-750">
          <span className="text-[11px] font-semibold text-slate-400 px-2">हर:</span>
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
              className={`px-2.5 py-1 text-xs rounded-lg font-medium transition ${
                campaign.intervalMinutes === item.value
                  ? item.highlight
                    ? 'bg-amber-500 text-slate-950 font-bold shadow'
                    : 'bg-slate-700 text-white shadow'
                  : 'text-slate-400 hover:text-white disabled:opacity-50'
              }`}
            >
              {item.label}
            </button>
          ))}
        </div>
      </div>

      {/* Primary Action Card: Countdown & Big Controls */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 relative z-10">
        
        {/* Countdown Box */}
        <div className="bg-slate-950 border border-slate-800 rounded-xl p-5 flex flex-col items-center justify-center text-center relative overflow-hidden">
          <div className="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5 text-amber-400" /> Next Email Dispatch In
          </div>
          
          <div className="text-4xl md:text-5xl font-black font-mono tracking-tight text-white my-1">
            {campaign.status === 'running'
              ? formatTimeRemaining(campaign.remainingSeconds)
              : campaign.status === 'paused'
              ? formatTimeRemaining(campaign.remainingSeconds)
              : formatTimeRemaining(campaign.intervalMinutes * 60)}
          </div>

          <div className="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
            {campaign.status === 'running' ? (
              <span className="text-emerald-400 font-semibold flex items-center gap-1">
                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping inline-block" />
                Active countdown ticking...
              </span>
            ) : campaign.status === 'paused' ? (
              <span className="text-amber-400 font-semibold">Paused at current timer</span>
            ) : (
              <span className="text-slate-400">Waiting for start trigger</span>
            )}
          </div>
        </div>

        {/* Next Recipient in Queue */}
        <div className="bg-slate-950 border border-slate-800 rounded-xl p-5 flex flex-col justify-between">
          <div>
            <div className="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1 flex items-center justify-between">
              <span className="flex items-center gap-1.5">
                <Users className="w-3.5 h-3.5 text-sky-400" /> Next In Queue
              </span>
              <span className="text-xs text-sky-400 font-semibold font-mono">
                #{total - pending + 1} of {total}
              </span>
            </div>

            {nextRecipient ? (
              <div className="mt-2 space-y-1">
                <div className="text-sm font-bold text-white truncate">
                  {nextRecipient.name}
                </div>
                <div className="text-xs text-slate-400 font-mono truncate">
                  {nextRecipient.email}
                </div>
                <div className="text-[11px] text-amber-300/80 truncate pt-1">
                  Preview Subject: "{replacePlaceholders(campaign.subject, nextRecipient, campaign)}"
                </div>
              </div>
            ) : (
              <div className="text-xs text-emerald-400 font-semibold py-4 flex items-center gap-2">
                <CheckCircle2 className="w-4 h-4" /> All queued emails have been dispatched!
              </div>
            )}
          </div>

          {nextRecipient && (
            <button
              onClick={onSendNextImmediately}
              disabled={campaign.recipients.length === 0}
              className="mt-3 w-full py-1.5 px-3 bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition"
              title="Skip remaining minutes and dispatch to this recipient immediately"
            >
              <Zap className="w-3.5 h-3.5 text-amber-400" />
              <span>Send To This User Immediately (Skip Timer)</span>
            </button>
          )}
        </div>

        {/* Master Execution Controls */}
        <div className="bg-slate-950 border border-slate-800 rounded-xl p-5 flex flex-col justify-between gap-3">
          <div>
            <div className="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center justify-between">
              <span>कैम्पेन नियंत्रण (Campaign Execution)</span>
              <span className="text-[10px] font-normal text-slate-400">
                {smtpConfig.enabled ? '🟢 Live' : '🟠 Sandbox'}
              </span>
            </div>

            {/* Outgoing Mail Sender Display */}
            <div className="mb-3 p-2 rounded-lg bg-slate-900 border border-slate-800 text-[11px] flex items-center justify-between">
              <span className="text-slate-400">प्रेषक (Sender):</span>
              <span className="font-mono text-emerald-400 font-bold truncate max-w-[160px]" title={smtpConfig.fromEmail || smtpConfig.username || 'Sandbox'}>
                {smtpConfig.enabled ? (smtpConfig.fromEmail || smtpConfig.username || 'Real SMTP Active') : 'Sandbox (Simulated)'}
              </span>
            </div>

            <div className="space-y-2">
              {campaign.status !== 'running' ? (
                <button
                  onClick={onStartCampaign}
                  disabled={total === 0 || pending === 0}
                  className="w-full py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-40 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-950 flex items-center justify-center gap-2 transition"
                >
                  <Play className="w-4 h-4 fill-white" />
                  <span>
                    {campaign.status === 'paused' ? 'Resume 5m Campaign' : 'Start 5m Campaign'}
                  </span>
                </button>
              ) : (
                <button
                  onClick={onPauseCampaign}
                  className="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm rounded-xl shadow-lg flex items-center justify-center gap-2 transition"
                >
                  <Pause className="w-4 h-4" />
                  <span>Pause Campaign</span>
                </button>
              )}

              <button
                onClick={onResetCampaign}
                className="w-full py-2 px-3 bg-slate-850 hover:bg-slate-800 text-slate-300 border border-slate-750 rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition"
              >
                <RotateCcw className="w-3.5 h-3.5 text-slate-400" />
                <span>Reset All Statuses to Pending</span>
              </button>
            </div>
          </div>

          <div className="text-[11px] text-slate-400 flex items-center justify-between border-t border-slate-850 pt-2">
            <span>Estimated Total Time:</span>
            <span className="text-white font-mono font-semibold">
              ~{estMinutesRemaining} mins remaining
            </span>
          </div>
        </div>

      </div>

      {/* Progress Bar & Metric Pills */}
      <div className="space-y-2 relative z-10">
        <div className="flex items-center justify-between text-xs">
          <span className="text-slate-300 font-medium">
            Overall Progress: <strong className="text-white">{progressPercent}%</strong> ({delivered} of {total} sent)
          </span>
          <div className="flex items-center gap-3 font-mono text-[11px]">
            <span className="text-emerald-400 font-semibold">✓ {delivered} Delivered</span>
            <span className="text-sky-400">⏳ {pending} Pending</span>
            {failed > 0 && <span className="text-rose-400">✕ {failed} Failed</span>}
          </div>
        </div>

        {/* Progress track */}
        <div className="w-full h-3 bg-slate-950 rounded-full overflow-hidden p-0.5 border border-slate-800 flex">
          <div
            className="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-500"
            style={{ width: `${progressPercent}%` }}
          />
        </div>
      </div>

    </div>
  );
};
