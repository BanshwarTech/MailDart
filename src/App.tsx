import React, { useState, useEffect, useRef, useCallback } from 'react';
import confetti from 'canvas-confetti';
import {
  Sparkles,
  Clock,
  Mail,
  Users,
  Settings,
  HelpCircle,
  FileCode,
  ShieldCheck,
  CheckCircle2,
  Calendar,
  Building2,
  Flame,
  Award,
  X
} from 'lucide-react';
import { CampaignState, SmtpConfig, Recipient, DispatchLog, FestivalType } from './types';
import { PRESET_TEMPLATES } from './data/presetTemplates';
import { INITIAL_SAMPLE_RECIPIENTS } from './data/sampleRecipients';
import { replacePlaceholders, getFestivalTitle } from './utils/campaignHelper';

import { Header } from './components/Header';
import { GuideModal } from './components/GuideModal';
import { SmtpSettingsModal } from './components/SmtpSettingsModal';
import { AiTemplateModal } from './components/AiTemplateModal';
import { TestEmailModal } from './components/TestEmailModal';
import { TemplateEditor } from './components/TemplateEditor';
import { DispatchController } from './components/DispatchController';
import { RecipientsManager } from './components/RecipientsManager';
import { DeliveryLogs } from './components/DeliveryLogs';

const STORAGE_KEY_CAMPAIGN = 'festivamail_campaign_v1';
const STORAGE_KEY_SMTP = 'festivamail_smtp_v1';

export default function App() {
  // Initial Campaign Setup
  const [campaign, setCampaign] = useState<CampaignState>(() => {
    const saved = localStorage.getItem(STORAGE_KEY_CAMPAIGN);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        // Ensure idle on load
        return { ...parsed, status: 'idle', remainingSeconds: (parsed.intervalMinutes || 5) * 60 };
      } catch (e) {
        console.error('Failed to parse saved campaign', e);
      }
    }

    const defaultPreset = PRESET_TEMPLATES[0]; // Diwali preset
    return {
      id: 'camp-default',
      name: 'Festival Grand Celebration Campaign',
      companyName: 'Acme Global Innovations',
      festival: defaultPreset.festival,
      subject: defaultPreset.subject,
      htmlTemplate: defaultPreset.html,
      discountCode: defaultPreset.defaultDiscount,
      intervalMinutes: 5, // Default 5 minutes as requested
      intervalSeconds: 300,
      status: 'idle',
      currentIndex: 0,
      remainingSeconds: 300,
      recipients: INITIAL_SAMPLE_RECIPIENTS,
      logs: [],
    };
  });

  // SMTP / Sender Configuration (Default: Gmail SMTP)
  const [smtpConfig, setSmtpConfig] = useState<SmtpConfig>(() => {
    const saved = localStorage.getItem(STORAGE_KEY_SMTP);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        return {
          ...parsed,
          host: parsed.host || 'smtp.gmail.com',
          port: parsed.port || 465,
        };
      } catch (e) {
        console.error('Failed to parse saved smtp', e);
      }
    }
    return {
      enabled: true,
      host: 'smtp.gmail.com',
      port: 465,
      secure: true,
      username: '',
      password: '',
      fromName: 'MailDart Campaigns',
      fromEmail: '',
      replyTo: '',
    };
  });

  // Modals state
  const [isGuideOpen, setIsGuideOpen] = useState(false);
  const [isSmtpModalOpen, setIsSmtpModalOpen] = useState(false);
  const [isAiModalOpen, setIsAiModalOpen] = useState(false);
  const [isTestEmailOpen, setIsTestEmailOpen] = useState(false);
  const [activeViewSection, setActiveViewSection] = useState<'all' | 'editor' | 'recipients' | 'logs'>('all');
  const [excelImportToast, setExcelImportToast] = useState<{ message: string; variables: string[] } | null>(null);

  // Persist changes to localStorage
  useEffect(() => {
    localStorage.setItem(STORAGE_KEY_CAMPAIGN, JSON.stringify(campaign));
  }, [campaign]);

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY_SMTP, JSON.stringify(smtpConfig));
  }, [smtpConfig]);

  // Update campaign helper
  const handleUpdateCampaign = (updates: Partial<CampaignState>) => {
    setCampaign((prev) => ({ ...prev, ...updates }));
  };

  // Dispatch a single recipient
  const dispatchRecipient = useCallback(
    async (recipient: Recipient) => {
      // Mark as sending
      setCampaign((prev) => ({
        ...prev,
        recipients: prev.recipients.map((r) =>
          r.id === recipient.id ? { ...r, status: 'sending' } : r
        ),
      }));

      const personalizedSubject = replacePlaceholders(campaign.subject, recipient, campaign);
      const personalizedHtml = replacePlaceholders(campaign.htmlTemplate, recipient, campaign);

      try {
        const res = await fetch('/api/send-email', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            to: recipient.email,
            subject: personalizedSubject,
            html: personalizedHtml,
            fromName: smtpConfig.fromName || campaign.companyName,
            fromEmail: smtpConfig.fromEmail,
            replyTo: smtpConfig.replyTo,
            smtpConfig: smtpConfig.enabled ? smtpConfig : undefined,
            simulate: !smtpConfig.enabled,
          }),
        });

        const data = await res.json();
        const isSuccess = data.success;
        const now = new Date().toISOString();
        const messageId = data.messageId || `msg-${Date.now()}`;
        const activeSender = data.sender || smtpConfig.fromEmail || smtpConfig.username || 'Current User';

        // Create log entry with sender identification
        const newLog: DispatchLog = {
          id: `log-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`,
          timestamp: now,
          senderEmail: activeSender,
          recipientEmail: recipient.email,
          recipientName: recipient.name,
          subject: personalizedSubject,
          status: isSuccess ? 'delivered' : 'failed',
          messageId: messageId,
          mode: data.mode === 'smtp' ? 'smtp' : 'simulated',
          detail: data.message || (isSuccess ? `Sent from ${activeSender}` : data.error),
        };

        // Update recipient & logs
        setCampaign((prev) => {
          const updatedRecipients = prev.recipients.map((r) =>
            r.id === recipient.id
              ? {
                  ...r,
                  status: (isSuccess ? 'delivered' : 'failed') as Recipient['status'],
                  sentAt: now,
                  messageId: messageId,
                  errorMessage: isSuccess ? undefined : data.error,
                }
              : r
          );

          // Check if all are done
          const remainingPending = updatedRecipients.filter((r) => r.status === 'pending');
          const isFinished = remainingPending.length === 0;

          if (isFinished) {
            // Trigger festive celebratory confetti
            try {
              confetti({
                particleCount: 120,
                spread: 80,
                origin: { y: 0.6 },
                colors: ['#f59e0b', '#ec4899', '#10b981', '#3b82f6', '#ffd700'],
              });
            } catch (e) {
              console.log('Confetti effect', e);
            }
          }

          return {
            ...prev,
            recipients: updatedRecipients,
            logs: [newLog, ...prev.logs],
            status: isFinished ? 'completed' : prev.status,
            remainingSeconds: isFinished ? 0 : prev.intervalMinutes * 60,
          };
        });
      } catch (err: any) {
        console.error('Dispatch error:', err);
        const now = new Date().toISOString();
        const failedLog: DispatchLog = {
          id: `log-${Date.now()}`,
          timestamp: now,
          recipientEmail: recipient.email,
          recipientName: recipient.name,
          subject: personalizedSubject,
          status: 'failed',
          mode: smtpConfig.enabled ? 'smtp' : 'simulated',
          detail: err.message || 'Network dispatch failure',
        };

        setCampaign((prev) => ({
          ...prev,
          recipients: prev.recipients.map((r) =>
            r.id === recipient.id
              ? { ...r, status: 'failed', errorMessage: err.message }
              : r
          ),
          logs: [failedLog, ...prev.logs],
          remainingSeconds: prev.intervalMinutes * 60,
        }));
      }
    },
    [campaign, smtpConfig]
  );

  // Send next pending recipient immediately (skip timer)
  const handleSendNextImmediately = () => {
    const next = campaign.recipients.find((r) => r.status === 'pending');
    if (!next) return;
    dispatchRecipient(next);
  };

  // Automated 5-Minute Timer Loop
  useEffect(() => {
    let timer: NodeJS.Timeout | null = null;

    if (campaign.status === 'running') {
      timer = setInterval(() => {
        setCampaign((prev) => {
          if (prev.status !== 'running') return prev;

          // If time is up, trigger dispatch of next user
          if (prev.remainingSeconds <= 1) {
            const nextRecipient = prev.recipients.find((r) => r.status === 'pending');
            if (nextRecipient) {
              // Trigger dispatch on next tick
              setTimeout(() => {
                dispatchRecipient(nextRecipient);
              }, 10);

              return {
                ...prev,
                remainingSeconds: prev.intervalMinutes * 60,
              };
            } else {
              // No more pending recipients
              return {
                ...prev,
                status: 'completed',
                remainingSeconds: 0,
              };
            }
          }

          // Decrement countdown by 1 second
          return {
            ...prev,
            remainingSeconds: prev.remainingSeconds - 1,
          };
        });
      }, 1000);
    }

    return () => {
      if (timer) clearInterval(timer);
    };
  }, [campaign.status, dispatchRecipient]);

  // Start / Resume Campaign
  const handleStartCampaign = () => {
    const hasPending = campaign.recipients.some((r) => r.status === 'pending');
    if (!hasPending) {
      alert('All recipients have already been sent! Please reset campaign or add more recipients.');
      return;
    }

    // If starting fresh or was paused, set to running
    setCampaign((prev) => ({
      ...prev,
      status: 'running',
      remainingSeconds:
        prev.remainingSeconds > 0 ? prev.remainingSeconds : prev.intervalMinutes * 60,
    }));
  };

  // Pause Campaign
  const handlePauseCampaign = () => {
    setCampaign((prev) => ({ ...prev, status: 'paused' }));
  };

  // Reset Campaign
  const handleResetCampaign = () => {
    if (window.confirm('Reset all recipients back to Pending status so they can be re-sent?')) {
      setCampaign((prev) => ({
        ...prev,
        status: 'idle',
        remainingSeconds: prev.intervalMinutes * 60,
        recipients: prev.recipients.map((r) => ({
          ...r,
          status: 'pending',
          sentAt: undefined,
          messageId: undefined,
          errorMessage: undefined,
        })),
      }));
    }
  };

  // Retry single recipient
  const handleRetryRecipient = (id: string) => {
    const target = campaign.recipients.find((r) => r.id === id);
    if (!target) return;
    dispatchRecipient(target);
  };

  // Update interval minutes
  const handleUpdateInterval = (minutes: number) => {
    setCampaign((prev) => ({
      ...prev,
      intervalMinutes: minutes,
      intervalSeconds: Math.round(minutes * 60),
      remainingSeconds: Math.round(minutes * 60),
    }));
  };

  // Handle Excel upload data with automatic variable generation
  const handleImportExcelData = (
    recipients: Recipient[],
    replace: boolean,
    discoveredVariables: string[],
    autoInsertBlock: boolean = true
  ) => {
    setCampaign((prev) => {
      let newTemplate = prev.htmlTemplate;

      const nonStandardVars = discoveredVariables.filter(
        (v) => v !== 'name' && v !== 'email' && v !== 'company' && v !== 'discount' && v !== 'festival'
      );

      if (autoInsertBlock && nonStandardVars.length > 0) {
        const missingVars = nonStandardVars.filter(
          (v) => !newTemplate.toLowerCase().includes(`{{${v.toLowerCase()}}}`)
        );

        if (missingVars.length > 0) {
          const rowsHtml = missingVars
            .map(
              (v) =>
                `    <p style="margin: 4px 0; font-size: 13px; color: #cbd5e1;"><strong style="color: #ffffff; text-transform: capitalize;">${v.replace(/_/g, ' ')}:</strong> {{${v}}}</p>`
            )
            .join('\n');

          const blockHtml = `\n<!-- Auto-Generated Excel Variables Section -->\n<div style="margin: 20px 0; padding: 14px 18px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(16, 185, 129, 0.4); border-left: 4px solid #10b981; border-radius: 8px;">\n  <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #10b981;">✨ आपके लिए विशेष विवरण (Personalized Details):</p>\n${rowsHtml}\n</div>\n`;

          if (newTemplate.includes('</body>')) {
            newTemplate = newTemplate.replace('</body>', `${blockHtml}\n</body>`);
          } else {
            newTemplate = newTemplate + blockHtml;
          }
        }
      }

      const mergedRecipients = replace ? recipients : [...prev.recipients, ...recipients];
      const updatedCustomVars = Array.from(
        new Set([...(prev.customVariables || []), ...discoveredVariables])
      );

      return {
        ...prev,
        recipients: mergedRecipients,
        customVariables: updatedCustomVars,
        htmlTemplate: newTemplate,
      };
    });

    setExcelImportToast({
      message: `${recipients.length} प्राप्तकर्ता व ${discoveredVariables.length} वेरिएबल्स एक्सेल से स्वतः जनरेट हो गए!`,
      variables: discoveredVariables,
    });

    try {
      confetti({
        particleCount: 45,
        spread: 60,
        origin: { y: 0.7 },
      });
    } catch {
      // Ignore confetti errors
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-amber-500/30 selection:text-white">
      
      {/* Top Header */}
      <Header
        campaign={campaign}
        smtpConfig={smtpConfig}
        onOpenGuide={() => setIsGuideOpen(true)}
        onOpenSmtpSettings={() => setIsSmtpModalOpen(true)}
        onOpenTestEmail={() => setIsTestEmailOpen(true)}
      />

      {/* Main Container */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-6 space-y-6">
        
        {/* Company Festival Overview Banner */}
        <div className="bg-gradient-to-r from-slate-900 via-slate-850 to-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="flex items-center gap-3.5">
            <div className="p-3 bg-amber-500/10 border border-amber-500/30 text-amber-400 rounded-xl">
              <Flame className="w-6 h-6" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <input
                  type="text"
                  value={campaign.companyName}
                  onChange={(e) => handleUpdateCampaign({ companyName: e.target.value })}
                  placeholder="Enter Company Name"
                  className="bg-transparent border-b border-transparent hover:border-slate-700 focus:border-amber-500 text-lg md:text-xl font-bold text-white focus:outline-none px-1 py-0.5"
                />
                <select
                  value={campaign.festival}
                  onChange={(e) => handleUpdateCampaign({ festival: e.target.value as any })}
                  className="text-xs px-2.5 py-1 rounded-full font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30 focus:outline-none focus:border-amber-400 cursor-pointer"
                  title="Change campaign category"
                >
                  <option value="diwali" className="bg-slate-900 text-white">🪔 Diwali Festival</option>
                  <option value="eid" className="bg-slate-900 text-white">🌙 Eid Mubarak</option>
                  <option value="christmas" className="bg-slate-900 text-white">🎄 Christmas & Holiday</option>
                  <option value="newyear" className="bg-slate-900 text-white">🎉 New Year Greeting</option>
                  <option value="holi" className="bg-slate-900 text-white">🎨 Holi Festival</option>
                  <option value="newsletter" className="bg-slate-900 text-white">📰 Company Newsletter</option>
                  <option value="product_launch" className="bg-slate-900 text-white">🚀 New Product Launch</option>
                  <option value="followup_reminder" className="bg-slate-900 text-white">⏰ Follow-up & Reminder</option>
                  <option value="welcome_onboarding" className="bg-slate-900 text-white">👋 Welcome & Onboarding</option>
                  <option value="custom" className="bg-slate-900 text-white">🎯 General / Custom Campaign</option>
                </select>
              </div>
              <p className="text-xs text-slate-400 mt-1">
                Automated multi-purpose email dispatcher: Sends 1 email every{' '}
                <strong className="text-amber-400">{campaign.intervalMinutes} minutes</strong> to avoid spam filters and ensure inbox delivery.
              </p>
            </div>
          </div>

          {/* Quick Nav Filter Tabs */}
          <div className="flex items-center gap-1.5 bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs">
            <button
              onClick={() => setActiveViewSection('all')}
              className={`px-3 py-1.5 rounded-lg font-medium transition ${
                activeViewSection === 'all'
                  ? 'bg-slate-800 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              All Sections
            </button>
            <button
              onClick={() => setActiveViewSection('editor')}
              className={`px-3 py-1.5 rounded-lg font-medium transition ${
                activeViewSection === 'editor'
                  ? 'bg-slate-800 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Template Studio
            </button>
            <button
              onClick={() => setActiveViewSection('recipients')}
              className={`px-3 py-1.5 rounded-lg font-medium transition ${
                activeViewSection === 'recipients'
                  ? 'bg-slate-800 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Recipients ({campaign.recipients.length})
            </button>
            <button
              onClick={() => setActiveViewSection('logs')}
              className={`px-3 py-1.5 rounded-lg font-medium transition ${
                activeViewSection === 'logs'
                  ? 'bg-slate-800 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Audit Logs ({campaign.logs.length})
            </button>
          </div>
        </div>

        {/* Excel Variables Auto-Generated Banner Notification */}
        {excelImportToast && (
          <div className="p-4 bg-gradient-to-r from-emerald-950 via-slate-900 to-indigo-950 border border-emerald-500/50 rounded-2xl shadow-xl flex items-center justify-between gap-4 animate-fade-in">
            <div className="flex items-start sm:items-center gap-3">
              <div className="p-2 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shrink-0">
                <Sparkles className="w-5 h-5 animate-pulse" />
              </div>
              <div className="space-y-1">
                <p className="text-xs sm:text-sm font-bold text-white flex items-center gap-1.5">
                  <span>⚡ {excelImportToast.message}</span>
                </p>
                <div className="flex flex-wrap items-center gap-1.5 pt-0.5">
                  <span className="text-[11px] text-slate-400">तैयार टैग्स (Auto-Generated Tags Ready):</span>
                  {excelImportToast.variables.map((v) => (
                    <code
                      key={v}
                      className="px-2 py-0.5 bg-slate-900 border border-emerald-500/40 text-emerald-300 font-mono text-[11px] rounded"
                    >
                      {'{{' + v + '}}'}
                    </code>
                  ))}
                  <span className="text-[11px] text-emerald-400 ml-1">
                    (ईमेल टेम्पलेट में तुरंत उपयोग के लिए उपलब्ध)
                  </span>
                </div>
              </div>
            </div>

            <button
              onClick={() => setExcelImportToast(null)}
              className="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition shrink-0"
              title="Dismiss"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        )}

        {/* Section 1: The 5-Minute Staggered Dispatcher Controller */}
        {(activeViewSection === 'all' || activeViewSection === 'recipients') && (
          <DispatchController
            campaign={campaign}
            smtpConfig={smtpConfig}
            onStartCampaign={handleStartCampaign}
            onPauseCampaign={handlePauseCampaign}
            onResetCampaign={handleResetCampaign}
            onSendNextImmediately={handleSendNextImmediately}
            onUpdateInterval={handleUpdateInterval}
          />
        )}

        {/* Section 2: Email HTML Template Studio & Live Preview */}
        {(activeViewSection === 'all' || activeViewSection === 'editor') && (
          <TemplateEditor
            campaign={campaign}
            onUpdateCampaign={handleUpdateCampaign}
            onOpenAiModal={() => setIsAiModalOpen(true)}
          />
        )}

        {/* Section 3: Multiple Recipients Manager */}
        {(activeViewSection === 'all' || activeViewSection === 'recipients') && (
          <RecipientsManager
            campaign={campaign}
            onUpdateRecipients={(recipients) => handleUpdateCampaign({ recipients })}
            onRetryRecipient={handleRetryRecipient}
            onImportExcelData={handleImportExcelData}
          />
        )}

        {/* Section 4: Real-Time Delivery Logs */}
        {(activeViewSection === 'all' || activeViewSection === 'logs') && (
          <DeliveryLogs
            logs={campaign.logs}
            onClearLogs={() => handleUpdateCampaign({ logs: [] })}
          />
        )}

      </main>

      {/* Footer */}
      <footer className="mt-auto border-t border-slate-800/80 bg-slate-900/60 py-5 text-center text-xs text-slate-400">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>MailFlow Pro — All-Purpose Email Marketing & 5-Minute Staggered Dispatcher</span>
          <div className="flex items-center gap-4 text-slate-400">
            <span>Anti-Spam Throttling</span>
            <span>•</span>
            <span>Custom HTML Templates</span>
            <span>•</span>
            <span>Excel Dynamic Variables</span>
          </div>
        </div>
      </footer>

      {/* Modals */}
      <GuideModal isOpen={isGuideOpen} onClose={() => setIsGuideOpen(false)} />
      
      <SmtpSettingsModal
        isOpen={isSmtpModalOpen}
        onClose={() => setIsSmtpModalOpen(false)}
        config={smtpConfig}
        onSave={(newConfig) => setSmtpConfig(newConfig)}
      />

      <AiTemplateModal
        isOpen={isAiModalOpen}
        onClose={() => setIsAiModalOpen(false)}
        currentFestival={campaign.festival}
        currentCompany={campaign.companyName}
        onApplyHtml={(html) => handleUpdateCampaign({ htmlTemplate: html })}
      />

      <TestEmailModal
        isOpen={isTestEmailOpen}
        onClose={() => setIsTestEmailOpen(false)}
        campaign={campaign}
        smtpConfig={smtpConfig}
      />

    </div>
  );
}
