import React, { useState, useEffect } from 'react';
import {
  X,
  Server,
  CheckCircle2,
  AlertTriangle,
  Key,
  Mail,
  ShieldAlert,
  Loader2,
  ExternalLink,
  Send,
  HelpCircle,
  UserCheck,
  ShieldCheck
} from 'lucide-react';
import { SmtpConfig } from '../types';
import { getSmtpStatus } from '../utils/campaignHelper';
import { useAuth } from '../context/AuthContext';

interface SmtpSettingsPageProps {
  config: SmtpConfig;
  onSave: (config: SmtpConfig) => void;
}

const toFormData = (config: SmtpConfig): SmtpConfig => ({
  ...config,
  host: config.host || 'smtp.gmail.com',
  port: config.port || 587,
  // Only port 465 uses direct SSL; 587 / 2525 / 25 use STARTTLS
  secure: (config.port || 587) === 465,
  enabled: config.enabled !== undefined ? config.enabled : true,
  fromEmail: config.fromEmail || config.username || '',
  fromName: config.fromName || 'MailDart Campaigns',
});

export const SmtpSettingsPage: React.FC<SmtpSettingsPageProps> = ({ config, onSave }) => {
  const { currentUser, saveUserSmtp, openAuthModal } = useAuth();
  const [formData, setFormData] = useState<SmtpConfig>(() => toFormData(config));
  const [savedAt, setSavedAt] = useState<number | null>(null);

  // Pick up settings loaded later (e.g. from the cloud after login)
  useEffect(() => {
    setFormData(toFormData(config));
  }, [config]);

  useEffect(() => {
    if (!savedAt) return;
    const t = setTimeout(() => setSavedAt(null), 3000);
    return () => clearTimeout(t);
  }, [savedAt]);
  const [isVerifying, setIsVerifying] = useState(false);
  const [isSendingTest, setIsSendingTest] = useState(false);
  const [verifyStatus, setVerifyStatus] = useState<{ success?: boolean; message?: string } | null>(null);
  const [showGmailHelp, setShowGmailHelp] = useState(!config.password);
  const [showElasticHelp, setShowElasticHelp] = useState(false);

  // Auto-sync sender email when username changes
  const handleUsernameChange = (newUsername: string) => {
    setFormData((prev) => ({
      ...prev,
      username: newUsername,
      // If fromEmail was same as previous username or empty, keep in sync
      fromEmail: prev.fromEmail === prev.username || !prev.fromEmail ? newUsername : prev.fromEmail,
    }));
  };

  const handleTestConnection = async () => {
    if (!formData.host || !formData.username || !formData.password) {
      setVerifyStatus({
        success: false,
        message: 'Please fill in the Host, Username (Email) and Password / App Key.',
      });
      return;
    }

    setIsVerifying(true);
    setVerifyStatus(null);

    try {
      const res = await fetch('/api/verify-smtp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          host: formData.host,
          port: formData.port,
          secure: formData.secure,
          username: formData.username,
          password: formData.password,
        }),
      });

      const data = await res.json();
      if (data.success) {
        setVerifyStatus({
          success: true,
          message: data.message || `SMTP connection successful! Emails will be sent from ${formData.username}.`,
        });
      } else {
        setVerifyStatus({
          success: false,
          message: data.error || 'SMTP verification failed. Please check the password and host.',
        });
      }
    } catch (err: any) {
      setVerifyStatus({
        success: false,
        message: err.message || 'Could not connect to the SMTP server.',
      });
    } finally {
      setIsVerifying(false);
    }
  };

  // Send an immediate test email to verify user's sender address
  const handleSendLiveTest = async () => {
    const targetEmail = formData.username || formData.fromEmail;
    if (!targetEmail || !targetEmail.includes('@')) {
      setVerifyStatus({
        success: false,
        message: 'Please enter a valid Username / Sender Email first.',
      });
      return;
    }

    if (!formData.password) {
      setVerifyStatus({
        success: false,
        message: 'Please enter the SMTP Password / App Password.',
      });
      return;
    }

    setIsSendingTest(true);
    setVerifyStatus(null);

    try {
      const res = await fetch('/api/send-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          to: targetEmail,
          subject: `✅ SMTP Test: Verified Dispatch from ${targetEmail}`,
          html: `<div style="font-family: sans-serif; padding: 20px; border: 1px solid #1a3d63; border-radius: 8px;">
            <h2 style="color: #1a3d63;">SMTP Verification Successful!</h2>
            <p>This test email confirms that emails are being sent successfully from <strong>${targetEmail}</strong> through the SMTP server <strong>${formData.host}</strong>.</p>
            <p style="color: #666; font-size: 12px;">Sent via MailDart Staggered Dispatcher on ${new Date().toLocaleString()}</p>
          </div>`,
          fromName: formData.fromName || 'MailDart Tester',
          fromEmail: formData.fromEmail || targetEmail,
          smtpConfig: {
            ...formData,
            enabled: true,
          },
          simulate: false,
        }),
      });

      const data = await res.json();
      if (data.success) {
        setVerifyStatus({
          success: true,
          message: `Great! The test email has been sent to ${targetEmail}. (Message ID: ${data.messageId || 'sent'})`,
        });
      } else {
        setVerifyStatus({
          success: false,
          message: data.error || 'Could not send the test email. Please check your credentials.',
        });
      }
    } catch (err: any) {
      setVerifyStatus({
        success: false,
        message: err.message || 'Network error: the test email could not be sent.',
      });
    } finally {
      setIsSendingTest(false);
    }
  };

  const handleQuickPreset = (preset: 'gmail' | 'elasticmail' | 'brevo' | 'sendgrid' | 'ses') => {
    switch (preset) {
      case 'elasticmail':
        setFormData((prev) => ({
          ...prev,
          host: 'smtp.elasticemail.com',
          port: 2525,
          secure: false,
          enabled: true,
        }));
        setShowGmailHelp(false);
        setShowElasticHelp(true);
        break;
      case 'gmail':
        setFormData((prev) => ({
          ...prev,
          host: 'smtp.gmail.com',
          port: 587,
          secure: false,
          enabled: true,
        }));
        setShowGmailHelp(true);
        setShowElasticHelp(false);
        break;
      case 'brevo':
        setFormData((prev) => ({
          ...prev,
          host: 'smtp-relay.brevo.com',
          port: 587,
          secure: false,
          enabled: true,
        }));
        setShowGmailHelp(false);
        setShowElasticHelp(false);
        break;
      case 'sendgrid':
        setFormData((prev) => ({
          ...prev,
          host: 'smtp.sendgrid.net',
          port: 587,
          secure: false,
          enabled: true,
        }));
        setShowGmailHelp(false);
        setShowElasticHelp(false);
        break;
      case 'ses':
        setFormData((prev) => ({
          ...prev,
          host: 'email-smtp.us-east-1.amazonaws.com',
          port: 587,
          secure: false,
          enabled: true,
        }));
        setShowGmailHelp(false);
        setShowElasticHelp(false);
        break;
    }
  };

  const handleSave = async () => {
    const finalConfig: SmtpConfig = {
      ...formData,
      fromEmail: formData.fromEmail || formData.username,
      fromName: formData.fromName || 'MailDart Campaigns',
    };
    onSave(finalConfig);
    if (currentUser) {
      await saveUserSmtp(finalConfig);
    }
    setSavedAt(Date.now());
  };

  const activeSender = formData.fromEmail || formData.username || 'Not configured yet';

  return (
    <div className="max-w-5xl mx-auto">
      <div className="relative bg-surface border border-slate-200/80 rounded-2xl shadow-card text-slate-700 p-6 md:p-8">

        <div className="flex items-center gap-4 mb-6 border-b border-slate-200/80 pb-5">
          <div className="w-11 h-11 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <Server className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-lg font-semibold tracking-tight text-slate-900">Sender & SMTP Settings</h2>
            <p className="text-sm text-slate-500">
              Enter your credentials so that all campaign emails are sent directly from your own email address
            </p>
          </div>
        </div>

        {/* Account Sync Status Banner */}
        {currentUser ? (
          <div className="mb-5 p-3 rounded-xl bg-sky-50 border border-sky-200 flex items-center justify-between text-xs text-sky-700">
            <div className="flex items-center gap-2">
              <ShieldCheck className="w-4 h-4 text-sky-600 shrink-0" />
              <span>
                Logged in as <strong className="text-slate-900">{currentUser.email}</strong>. Your SMTP credentials are stored securely in your private Firestore account.
              </span>
            </div>
            <span className="text-[10px] bg-sky-100 text-sky-700 font-mono px-2 py-0.5 rounded border border-sky-200 shrink-0">
              Cloud Sync Active
            </span>
          </div>
        ) : (
          <div className="mb-5 p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between text-xs text-amber-800">
            <div className="flex items-center gap-2">
              <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />
              <span>
                <strong>Guest mode:</strong> settings are saved only in this browser. Log in to keep them safe and use them on any device.
              </span>
            </div>
            <button
              type="button"
              onClick={() => openAuthModal('login')}
              className="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-white font-semibold rounded-lg text-xs shrink-0 transition shadow-button"
            >
              Log In
            </button>
          </div>
        )}

        {/* Mode Switcher */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
          <button
            type="button"
            onClick={() => setFormData((p) => ({ ...p, enabled: true }))}
            className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${ formData.enabled ? 'bg-brand-50 border-brand-500 text-slate-900 shadow-sm'
                : 'bg-surface border-slate-200 text-slate-500 hover:bg-slate-50'
            }`}
          >
            <div className="flex items-center justify-between mb-2">
              <span className="font-semibold text-brand-700 text-sm flex items-center gap-1.5">
                <Server className="w-4 h-4" /> Real SMTP (Your Email)
              </span>
              {formData.enabled && <CheckCircle2 className="w-4 h-4 text-brand-700" />}
            </div>
            <p className="text-xs text-slate-500">
              Live mode. Real emails are delivered to inboxes through your Gmail or custom mail server.
            </p>
          </button>

          <button
            type="button"
            onClick={() => setFormData((p) => ({ ...p, enabled: false }))}
            className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${ !formData.enabled ? 'bg-amber-50 border-amber-500 text-slate-900 shadow-sm'
                : 'bg-surface border-slate-200 text-slate-500 hover:bg-slate-50'
            }`}
          >
            <div className="flex items-center justify-between mb-2">
              <span className="font-semibold text-amber-700 text-sm flex items-center gap-1.5">
                <ShieldAlert className="w-4 h-4" /> Sandbox / Simulation Mode
              </span>
              {!formData.enabled && <CheckCircle2 className="w-4 h-4 text-amber-600" />}
            </div>
            <p className="text-xs text-slate-500">
              Safe simulation mode. Practise with the 5-minute timer and your full HTML without entering a password.
            </p>
          </button>
        </div>

        {/* Sender Info */}
        <div className="space-y-4 mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
          <h3 className="text-sm font-semibold text-slate-900 flex items-center gap-2">
            <Mail className="w-4 h-4 text-sky-600" /> Sender Information
          </h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs text-slate-500 mb-1">
                Company / Sender Name
              </label>
              <input
                type="text"
                value={formData.fromName}
                onChange={(e) => setFormData({ ...formData, fromName: e.target.value })}
                placeholder="e.g. Alekh Banshwar / Acme Corp"
                className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
              />
            </div>
            <div>
              <label className="block text-xs text-slate-500 mb-1">
                From Email Address
              </label>
              <input
                type="email"
                value={formData.fromEmail}
                onChange={(e) => setFormData({ ...formData, fromEmail: e.target.value })}
                placeholder="e.g. you@yourcompany.com"
                className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
              />
            </div>
          </div>
        </div>

        {/* SMTP Server Credentials */}
        {formData.enabled && (
          <div className="space-y-4 mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200 animate-fade-in">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <h3 className="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <Key className="w-4 h-4 text-brand-700" /> SMTP Server Credentials
              </h3>

              {/* Presets */}
              <div className="flex flex-wrap items-center gap-1.5 text-xs">
                <span className="text-slate-500 text-[11px]">Quick Setup:</span>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('gmail')}
                  className="px-3 py-1 bg-brand-600 hover:bg-brand-500 text-white rounded-lg border border-brand-600 font-semibold flex items-center gap-1.5 transition shadow-button"
                  title="Gmail: Default Recommended (smtp.gmail.com:587 using 16-letter App Password)"
                >
                  <span>★ Gmail (Default)</span>
                  <span className="text-[10px] bg-white/20 text-white px-1.5 py-0.5 rounded font-mono font-semibold">16-Letter App Pass</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('elasticmail')}
                  className="px-2.5 py-1 bg-surface hover:bg-slate-50 text-sky-700 rounded-lg border border-slate-300 hover:border-slate-400 font-medium shadow-xs transition"
                  title="Elastic Email: Most affordable high-volume SMTP ($0.50/1k)"
                >
                  <span>⚡ Elastic Email</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('brevo')}
                  className="px-2 py-1 bg-surface hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
                >
                  Brevo
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('sendgrid')}
                  className="px-2 py-1 bg-surface hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
                >
                  SendGrid
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('ses')}
                  className="px-2 py-1 bg-surface hover:bg-slate-50 text-slate-700 rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
                >
                  AWS SES
                </button>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="sm:col-span-2">
                <label className="block text-xs text-slate-500 mb-1">SMTP Host</label>
                <input
                  type="text"
                  value={formData.host}
                  onChange={(e) => setFormData({ ...formData, host: e.target.value })}
                  placeholder="smtp.elasticemail.com or smtp.gmail.com"
                  className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
                />
              </div>
              <div>
                <label className="block text-xs text-slate-500 mb-1">Port</label>
                <input
                  type="number"
                  value={formData.port}
                  onChange={(e) => {
                    const port = Number(e.target.value);
                    setFormData({ ...formData, port, secure: port === 465 });
                  }}
                  placeholder="2525, 587, or 465"
                  className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs text-slate-500 mb-1">
                  SMTP Username / Email <span className="text-brand-700">*</span>
                </label>
                <input
                  type="text"
                  value={formData.username}
                  onChange={(e) => handleUsernameChange(e.target.value)}
                  placeholder="you@gmail.com"
                  className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
                />
              </div>
              <div>
                <label className="block text-xs text-slate-500 mb-1 flex items-center justify-between">
                  <span>Password / API Key</span>
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => {
                        setShowElasticHelp(!showElasticHelp);
                        if (!showElasticHelp) setShowGmailHelp(false);
                      }}
                      className="text-[11px] text-sky-600 hover:underline flex items-center gap-0.5"
                    >
                      <HelpCircle className="w-3 h-3" /> Elastic Help
                    </button>
                    <span className="text-slate-400">|</span>
                    <button
                      type="button"
                      onClick={() => {
                        setShowGmailHelp(!showGmailHelp);
                        if (!showGmailHelp) setShowElasticHelp(false);
                      }}
                      className="text-[11px] text-amber-600 hover:underline flex items-center gap-0.5"
                    >
                      <HelpCircle className="w-3 h-3" /> Gmail
                    </button>
                  </div>
                </label>
                <input
                  type="password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  placeholder="Elastic API Key or Gmail App Password"
                  className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
                />
              </div>
            </div>

            {/* Elastic Email Step-by-Step Guide */}
            {showElasticHelp && (
              <div className="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-xs text-slate-700 space-y-2 animate-fade-in">
                <div className="font-semibold text-sky-700 flex items-center justify-between">
                  <span className="flex items-center gap-1.5">
                    <span>⚡ Elastic Email setup guide (affordable and reliable):</span>
                  </span>
                  <button
                    type="button"
                    onClick={() => setShowElasticHelp(false)}
                    className="text-slate-400 hover:text-slate-900"
                  >
                    ✕
                  </button>
                </div>
                <ol className="list-decimal list-inside space-y-1.5 text-slate-600 text-[11px] leading-relaxed">
                  <li>
                    <a
                      href="https://elasticemail.com"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-sky-700 underline font-semibold inline-flex items-center gap-0.5"
                    >
                      Elastic Email <ExternalLink className="w-2.5 h-2.5" />
                    </a>{' '}
                    — create a free account or log in.
                  </li>
                  <li>
                    In the Elastic Email dashboard, open <strong>Settings → Manage API Keys</strong>.
                  </li>
                  <li>
                    Click <strong>&quot;Create API Key&quot;</strong>, give it <em>Full Access</em> and copy the generated <strong>API Key</strong>.
                  </li>
                  <li>
                    Fill in these details here:
                    <ul className="list-disc list-inside pl-4 mt-1 text-slate-600 space-y-0.5">
                      <li><strong>SMTP Host:</strong> <code className="bg-slate-100 border border-slate-200 text-brand-700 px-1.5 py-0.5 rounded">smtp.elasticemail.com</code></li>
                      <li><strong>Port:</strong> <code className="bg-slate-100 border border-slate-200 text-brand-700 px-1.5 py-0.5 rounded">2525</code> (or <code className="bg-slate-100 border border-slate-200 text-brand-700 px-1.5 py-0.5 rounded">587</code>)</li>
                      <li><strong>SMTP Username:</strong> the email you registered with Elastic Email</li>
                      <li><strong>Password:</strong> your generated <strong>API Key</strong></li>
                    </ul>
                  </li>
                  <li className="text-brand-700">
                    💡 <em>Benefit:</em> Elastic Email is one of the most affordable providers ($0.50 per 1,000 emails), and with 5-minute staggered sending its inbox delivery rate stays very high.
                  </li>
                </ol>
              </div>
            )}

            {/* Gmail App Password Step-by-Step Guide */}
            {showGmailHelp && (
              <div className="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-slate-700 space-y-2">
                <div className="font-semibold text-amber-800 flex items-center justify-between">
                  <span>📌 How to create a Gmail App Password (30 seconds):</span>
                  <button
                    type="button"
                    onClick={() => setShowGmailHelp(false)}
                    className="text-slate-400 hover:text-slate-900"
                  >
                    ✕
                  </button>
                </div>
                <ol className="list-decimal list-inside space-y-1 text-slate-600 text-[11px] leading-relaxed">
                  <li>
                    Open your Google Account{' '}
                    <a
                      href="https://myaccount.google.com/security"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-brand-700 underline font-semibold"
                    >
                      Security Settings
                    </a>.
                  </li>
                  <li>Make sure <strong>2-Step Verification</strong> is turned ON.</li>
                  <li>
                    Type <strong>&quot;App passwords&quot;</strong> in the search bar, or open App passwords under Security.
                  </li>
                  <li>Enter the app name <code>MailDart</code> and click <strong>Create</strong>.</li>
                  <li>Copy the 16-letter code shown (e.g. <code>abcd efgh ijkl mnop</code>) and paste it in the Password field here.</li>
                </ol>
              </div>
            )}

            <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
              <label className="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                <input
                  type="checkbox"
                  checked={formData.secure}
                  onChange={(e) => setFormData({ ...formData, secure: e.target.checked })}
                  className="rounded border-slate-300 accent-brand-600 focus:ring-0"
                />
                Use SSL / TLS (only for port 465; keep off for 587)
              </label>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleTestConnection}
                  disabled={isVerifying || isSendingTest}
                  className="px-3 py-1.5 bg-surface hover:bg-slate-50 text-xs font-semibold text-slate-700 border border-slate-300 hover:border-slate-400 rounded-lg shadow-xs flex items-center gap-1.5 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isVerifying ? (
                    <>
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-brand-700" /> Checking...
                    </>
                  ) : (
                    <>
                      <Server className="w-3.5 h-3.5 text-brand-700" /> Test Connection
                    </>
                  )}
                </button>

                <button
                  type="button"
                  onClick={handleSendLiveTest}
                  disabled={isVerifying || isSendingTest}
                  className="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-xs font-semibold text-white rounded-lg flex items-center gap-1.5 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-button"
                  title="Send a real test email directly to your own address"
                >
                  {isSendingTest ? (
                    <>
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-white" /> Sending test email...
                    </>
                  ) : (
                    <>
                      <Send className="w-3.5 h-3.5 text-white" /> Send Live Test to My Email
                    </>
                  )}
                </button>
              </div>
            </div>

            {verifyStatus && (
              <div
                className={`p-3 rounded-xl text-xs flex items-start gap-2.5 ${ verifyStatus.success ? 'bg-brand-50 text-brand-700 border border-brand-200'
                    : 'bg-red-50 text-red-700 border border-red-200'
                }`}
              >
                {verifyStatus.success ? (
                  <CheckCircle2 className="w-4 h-4 shrink-0 text-brand-700 mt-0.5" />
                ) : (
                  <AlertTriangle className="w-4 h-4 shrink-0 text-red-600 mt-0.5" />
                )}
                <span className="leading-relaxed">{verifyStatus.message}</span>
              </div>
            )}
          </div>
        )}

        {/* Footer */}
        <div className="sticky bottom-0 z-10 -mx-6 md:-mx-8 -mb-6 md:-mb-8 mt-6 px-6 md:px-8 py-4 bg-surface/90 backdrop-blur-xl border-t border-slate-200/80 rounded-b-2xl flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="text-xs text-slate-500 text-center sm:text-left">
            <span>Active mode: </span>
            <strong className={getSmtpStatus(formData) === 'live' ? 'text-brand-700 font-semibold' : 'text-amber-700'}>
              {{
                live: `● Real SMTP (${formData.fromEmail || formData.username})`,
                incomplete: '● Real SMTP (username / password missing)',
                sandbox: '● Safe Sandbox Simulation',
              }[getSmtpStatus(formData)]}
            </strong>
          </div>

          <div className="flex items-center gap-2.5 w-full sm:w-auto justify-end">
            {savedAt && (
              <span className="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 animate-fade-in">
                <CheckCircle2 className="w-4 h-4" /> Settings saved
              </span>
            )}
            <button
              onClick={() => {
                setFormData(toFormData(config));
                setVerifyStatus(null);
              }}
              className="px-4 py-2 text-xs text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
            >
              Discard changes
            </button>
            <button
              onClick={handleSave}
              className="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-xl transition flex items-center gap-1.5 shadow-button"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>Save Settings</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
