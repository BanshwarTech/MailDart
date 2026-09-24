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
import { CampaignState, SmtpConfig, Recipient, DispatchLog, FestivalType, ViewSection } from './types';
import { PRESET_TEMPLATES } from './data/presetTemplates';
import { INITIAL_SAMPLE_RECIPIENTS } from './data/sampleRecipients';
import { replacePlaceholders, getFestivalTitle, getSmtpStatus } from './utils/campaignHelper';

import { Header } from './components/Header';
import { GuidePage } from './components/GuidePage';
import { SmtpSettingsPage } from './components/SmtpSettingsPage';
import { AiTemplateModal } from './components/AiTemplateModal';
import { TestEmailModal } from './components/TestEmailModal';
import { TemplateEditor } from './components/TemplateEditor';
import { DispatchController } from './components/DispatchController';
import { RecipientsManager } from './components/RecipientsManager';
import { DeliveryLogs } from './components/DeliveryLogs';
import { AuthPage } from './components/AuthPage';
import { OverviewPanel } from './components/OverviewPanel';
import { LandingPage } from './components/LandingPage';
import { CategorySelect } from './components/CategorySelect';
import { Sidebar } from './components/Sidebar';
import { StepNav } from './components/StepNav';
import { NAV_ITEMS, findNavItem, TOTAL_STEPS } from './data/navigation';
import { useAuth } from './context/AuthContext';

const STORAGE_KEY_CAMPAIGN = 'festivamail_campaign_v1';
const STORAGE_KEY_SMTP = 'festivamail_smtp_v1';

export default function App() {
  const { currentUser, loadUserSmtp, loading: authLoading, isAuthModalOpen, authModalMode, closeAuthModal } = useAuth();
  // Dashboard page a logged-out visitor asked for; opened automatically after login
  const [pendingSection, setPendingSection] = useState<ViewSection | null>(null);
  
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
          port: parsed.port || 587,
          // 587 uses STARTTLS; SSL must stay off (only 465 uses direct SSL)
          secure: (parsed.port || 587) === 465,
        };
      } catch (e) {
        console.error('Failed to parse saved smtp', e);
      }
    }
    return {
      enabled: true,
      host: 'smtp.gmail.com',
      port: 587,
      secure: false,
      username: '',
      password: '',
      fromName: 'MailDart Campaigns',
      fromEmail: '',
      replyTo: '',
    };
  });

  // Modals state
  const [isAiModalOpen, setIsAiModalOpen] = useState(false);
  const [isTestEmailOpen, setIsTestEmailOpen] = useState(false);
  const [activeViewSection, setActiveViewSection] = useState<ViewSection>(() => {
    const fromHash = window.location.hash.replace('#', '') as ViewSection;
    // No hash (or #home) opens the landing page; app pages stay directly linkable
    if (fromHash === 'login' || fromHash === 'register') return fromHash;
    return NAV_ITEMS.some((n) => n.id === fromHash) ? fromHash : 'home';
  });
  const [isMobileNavOpen, setIsMobileNavOpen] = useState(false);
  const [excelImportToast, setExcelImportToast] = useState<{ message: string; variables: string[] } | null>(null);

  const isAuthView = activeViewSection === 'login' || activeViewSection === 'register';
  const isAppView = activeViewSection !== 'home' && !isAuthView;

  // Auth routing: logged-out visitors on a dashboard page go to Sign In (and come back after login);
  // logged-in users never stay on the Sign In / Sign Up pages.
  useEffect(() => {
    if (authLoading) return;
    if (!currentUser && isAppView) {
      setPendingSection(activeViewSection);
      setActiveViewSection('login');
    } else if (currentUser && isAuthView) {
      setActiveViewSection(pendingSection ?? 'overview');
      setPendingSection(null);
    }
  }, [authLoading, currentUser, activeViewSection, isAppView, isAuthView, pendingSection]);

  // Anything that asks for the old login pop-up now opens the full-page Sign In / Sign Up
  useEffect(() => {
    if (isAuthModalOpen) {
      setActiveViewSection(authModalMode === 'register' ? 'register' : 'login');
      closeAuthModal();
    }
  }, [isAuthModalOpen, authModalMode, closeAuthModal]);

  // Keep the active page in the URL hash (shareable links + browser back/forward)
  useEffect(() => {
    window.scrollTo({ top: 0 });
    // The landing page lives at the plain root URL (no #home); app pages use #section
    const target = activeViewSection === 'home' ? window.location.pathname + window.location.search : `#${activeViewSection}`;
    const current = activeViewSection === 'home' ? (window.location.hash ? 'hash' : '') : window.location.hash;
    if (activeViewSection === 'home' ? current !== '' : current !== target) {
      window.history.pushState(null, '', target);
    }
  }, [activeViewSection]);

  useEffect(() => {
    const onHashChange = () => {
      const fromHash = window.location.hash.replace('#', '') as ViewSection;
      if (fromHash === 'home' || !fromHash) setActiveViewSection('home');
      else if (fromHash === 'login' || fromHash === 'register' || NAV_ITEMS.some((n) => n.id === fromHash)) setActiveViewSection(fromHash);
    };
    window.addEventListener('popstate', onHashChange);
    return () => window.removeEventListener('popstate', onHashChange);
  }, []);

  // Persist changes to localStorage
  useEffect(() => {
    localStorage.setItem(STORAGE_KEY_CAMPAIGN, JSON.stringify(campaign));
  }, [campaign]);

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY_SMTP, JSON.stringify(smtpConfig));
  }, [smtpConfig]);

  // When user logs in, load their private SMTP settings from Firestore
  useEffect(() => {
    if (currentUser) {
      loadUserSmtp().then((userSmtp) => {
        if (userSmtp) {
          setSmtpConfig(userSmtp);
        }
      });
    }
  }, [currentUser]);

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
                colors: ['#f59e0b', '#ec4899', '#1a3d63', '#3b82f6', '#ffd700'],
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

          const blockHtml = `\n<!-- Auto-Generated Excel Variables Section -->\n<div style="margin: 20px 0; padding: 14px 18px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(26, 61, 99, 0.35); border-left: 4px solid #1a3d63; border-radius: 8px;">\n  <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #1a3d63;">✨ Your Personalised Details:</p>\n${rowsHtml}\n</div>\n`;

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
      message: `${recipients.length} recipients and ${discoveredVariables.length} variables imported from your Excel sheet!`,
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

  const activeNav = findNavItem(activeViewSection);
  const pageSubtitle = activeNav.step ? `Step ${activeNav.step} of ${TOTAL_STEPS} · ${activeNav.description}` : activeNav.description;

  // Completion state for each workflow step (drives sidebar ticks, Overview and step navigation)
  const stepDone: Partial<Record<ViewSection, boolean>> = {
    settings: getSmtpStatus(smtpConfig) === 'live',
    recipients: campaign.recipients.length > 0,
    editor: !!campaign.subject.trim() && !!campaign.htmlTemplate.trim(),
    dispatch: campaign.status === 'completed' || campaign.recipients.some((r) => r.status === 'delivered'),
    logs: campaign.logs.length > 0,
  };

  // Wait for Firebase to restore the session so the landing page doesn't flash for logged-in users
  if (authLoading) {
    return (
      <div className="min-h-screen bg-page flex items-center justify-center">
        <div className="w-8 h-8 rounded-full border-2 border-slate-300 border-t-brand-400 animate-spin" aria-label="Loading" />
      </div>
    );
  }

  // Sign In / Sign Up pages (full page, no sidebar)
  if (!currentUser && isAuthView) {
    return (
      <AuthPage
        mode={activeViewSection as 'login' | 'register'}
        onSwitchMode={(mode) => setActiveViewSection(mode)}
        onBackHome={() => setActiveViewSection('home')}
      />
    );
  }

  // The dashboard (with sidebar) is only available after login
  if (!currentUser || activeViewSection === 'home') {
    return (
      <LandingPage
        isLoggedIn={!!currentUser}
        onOpenApp={(section) => {
          if (currentUser) {
            setActiveViewSection(section ?? 'overview');
          } else {
            setPendingSection(section ?? 'overview');
            setActiveViewSection('login');
          }
        }}
        onLogin={() => setActiveViewSection('login')}
        onSignup={() => setActiveViewSection('register')}
      />
    );
  }

  return (
    <div className="min-h-screen bg-page text-slate-900 font-sans selection:bg-[#34506D] selection:text-white">

      {/* Sidebar Navigation */}
      <Sidebar
        campaign={campaign}
        smtpConfig={smtpConfig}
        activeSection={activeViewSection}
        onNavigate={setActiveViewSection}
        stepDone={stepDone}
        counts={{ recipients: campaign.recipients.length, logs: campaign.logs.length }}
        isSending={campaign.status === 'running'}
        mobileOpen={isMobileNavOpen}
        onCloseMobile={() => setIsMobileNavOpen(false)}
      />

      <div className="lg:pl-64 min-h-screen flex flex-col min-w-0">

      {/* Top Bar */}
      <Header
        campaign={campaign}
        smtpConfig={smtpConfig}
        title={activeNav.label}
        subtitle={pageSubtitle}
        onOpenMenu={() => setIsMobileNavOpen(true)}
        onOpenHome={() => setActiveViewSection('home')}
        onOpenSmtpSettings={() => setActiveViewSection('settings')}
      />

      {/* Main Container */}
      <main className="flex-1 min-w-0 w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 space-y-6">

        {/* Mobile page title */}
        <div className="lg:hidden">
          <h1 className="text-lg font-semibold text-slate-900">{activeNav.label}</h1>
          <p className="text-sm text-slate-500">{pageSubtitle}</p>
        </div>

        {/* Campaign details (step 3 only) */}
        {activeViewSection === 'editor' && (
          <div className="relative z-10 bg-surface ring-1 ring-slate-200/80 rounded-2xl shadow-card">
            <div className="absolute inset-y-0 right-0 w-1/2 rounded-r-2xl bg-dots [mask-image:linear-gradient(to_left,black,transparent)] pointer-events-none" />
            <div className="relative p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
              <div className="flex items-center gap-4 min-w-0">
                <div className="w-12 h-12 shrink-0 flex items-center justify-center bg-gradient-to-br from-brand-500 to-accent-600 text-white rounded-xl shadow-button">
                  <Flame className="w-5 h-5" />
                </div>
                <div className="min-w-0">
                  <p className="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">Campaign details</p>
                  <div className="flex flex-wrap items-center gap-2">
                    <input
                      type="text"
                      value={campaign.companyName}
                      onChange={(e) => handleUpdateCampaign({ companyName: e.target.value })}
                      placeholder="Enter Company Name"
                      className="bg-transparent border border-transparent rounded-md hover:border-slate-200 hover:bg-slate-50 focus:bg-surface focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-lg md:text-xl font-semibold tracking-tight text-slate-900 placeholder:text-slate-400 focus:outline-none px-1.5 py-0.5 -ml-1.5 transition"
                    />
                    <CategorySelect
                      value={campaign.festival}
                      onChange={(festival) => handleUpdateCampaign({ festival })}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Excel Variables Auto-Generated Banner Notification */}
        {excelImportToast && (
          <div className="p-4 bg-brand-50 border border-brand-200 rounded-xl shadow-sm flex items-center justify-between gap-4 animate-fade-in">
            <div className="flex items-start sm:items-center gap-3">
              <div className="p-2 rounded-lg bg-surface text-brand-700 border border-brand-200 shrink-0">
                <Sparkles className="w-5 h-5" />
              </div>
              <div className="space-y-1">
                <p className="text-xs sm:text-sm font-semibold text-brand-900 flex items-center gap-1.5">
                  <span>⚡ {excelImportToast.message}</span>
                </p>
                <div className="flex flex-wrap items-center gap-1.5 pt-0.5">
                  <span className="text-[11px] text-brand-800/80">Tags ready to use:</span>
                  {excelImportToast.variables.map((v) => (
                    <code
                      key={v}
                      className="px-2 py-0.5 bg-surface border border-brand-200 text-brand-700 font-mono text-[11px] rounded"
                    >
                      {'{{' + v + '}}'}
                    </code>
                  ))}
                  <span className="text-[11px] text-brand-700 ml-1">
                    (you can use these in your email template right away)
                  </span>
                </div>
              </div>
            </div>

            <button
              onClick={() => setExcelImportToast(null)}
              className="p-1.5 text-brand-700 hover:text-brand-900 rounded-lg hover:bg-brand-100 transition shrink-0"
              title="Dismiss"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        )}

        {/* Overview */}
        {activeViewSection === 'overview' && (
          <OverviewPanel campaign={campaign} smtpConfig={smtpConfig} stepDone={stepDone} onNavigate={setActiveViewSection} />
        )}

        {/* Step 1: SMTP Settings */}
        {activeViewSection === 'settings' && (
          <SmtpSettingsPage config={smtpConfig} onSave={(newConfig) => setSmtpConfig(newConfig)} />
        )}

        {/* Step 2: Recipients */}
        {activeViewSection === 'recipients' && (
          <RecipientsManager
            campaign={campaign}
            onUpdateRecipients={(recipients) => handleUpdateCampaign({ recipients })}
            onRetryRecipient={handleRetryRecipient}
            onImportExcelData={handleImportExcelData}
          />
        )}

        {/* Step 3: Template Studio */}
        {activeViewSection === 'editor' && (
          <TemplateEditor
            campaign={campaign}
            onUpdateCampaign={handleUpdateCampaign}
            onOpenAiModal={() => setIsAiModalOpen(true)}
            onOpenTestEmail={() => setIsTestEmailOpen(true)}
          />
        )}

        {/* Step 4: Dispatcher */}
        {activeViewSection === 'dispatch' && (
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

        {/* Step 5: Delivery Logs */}
        {activeViewSection === 'logs' && (
          <DeliveryLogs logs={campaign.logs} onClearLogs={() => handleUpdateCampaign({ logs: [] })} />
        )}

        {/* Help: Guide */}
        {activeViewSection === 'guide' && (
          <GuidePage
            intervalMinutes={campaign.intervalMinutes}
            onNavigate={setActiveViewSection}
            onOpenSmtpSettings={() => setActiveViewSection('settings')}
            onOpenTestEmail={() => setIsTestEmailOpen(true)}
          />
        )}

        {/* Previous / next step */}
        <StepNav current={activeViewSection} stepDone={stepDone} onNavigate={setActiveViewSection} />

      </main>

      {/* Footer */}
      <footer className="mt-auto border-t border-slate-200/80 py-5 text-xs text-slate-500">
        <div className="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>© {new Date().getFullYear()} MailDart Pro — All-Purpose Email Marketing & 5-Minute Staggered Dispatcher</span>
          <div className="flex items-center gap-3 text-slate-400">
            <span>Anti-Spam Throttling</span>
            <span className="w-1 h-1 rounded-full bg-slate-300" />
            <span>Custom HTML Templates</span>
            <span className="w-1 h-1 rounded-full bg-slate-300" />
            <span>Excel Dynamic Variables</span>
          </div>
        </div>
      </footer>

      </div>

      {/* Modals */}
      
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
