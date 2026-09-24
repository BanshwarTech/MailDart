import React, { useState } from 'react';
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
import { useAuth } from '../context/AuthContext';

interface SmtpSettingsModalProps {
  isOpen: boolean;
  onClose: () => void;
  config: SmtpConfig;
  onSave: (config: SmtpConfig) => void;
}

export const SmtpSettingsModal: React.FC<SmtpSettingsModalProps> = ({
  isOpen,
  onClose,
  config,
  onSave,
}) => {
  const { currentUser, saveUserSmtp, openAuthModal } = useAuth();
  const [formData, setFormData] = useState<SmtpConfig>({
    ...config,
    host: config.host || 'smtp.gmail.com',
    port: config.port || 465,
    secure: config.secure !== undefined ? config.secure : true,
    enabled: config.enabled !== undefined ? config.enabled : true,
    fromEmail: config.fromEmail || config.username || '',
    fromName: config.fromName || 'MailDart Campaigns',
  });
  const [isVerifying, setIsVerifying] = useState(false);
  const [isSendingTest, setIsSendingTest] = useState(false);
  const [verifyStatus, setVerifyStatus] = useState<{ success?: boolean; message?: string } | null>(null);
  const [showGmailHelp, setShowGmailHelp] = useState(!config.password);
  const [showElasticHelp, setShowElasticHelp] = useState(false);

  if (!isOpen) return null;

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
        message: 'कृपया Host, Username (Email) और Password / App Key भरें।',
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
          message: data.message || `SMTP कनेक्शन सफल! ईमेल ${formData.username} से भेजे जाएंगे।`,
        });
      } else {
        setVerifyStatus({
          success: false,
          message: data.error || 'SMTP वेरिफिकेशन असफल रहा। कृपया पासवर्ड और होस्ट जांचें।',
        });
      }
    } catch (err: any) {
      setVerifyStatus({
        success: false,
        message: err.message || 'SMTP सर्वर से संपर्क नहीं हो सका।',
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
        message: 'कृपया पहले मान्य Username / Sender Email भरें।',
      });
      return;
    }

    if (!formData.password) {
      setVerifyStatus({
        success: false,
        message: 'कृपया SMTP पासवर्ड / App Password भरें।',
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
          html: `<div style="font-family: sans-serif; padding: 20px; border: 1px solid #10b981; border-radius: 8px;">
            <h2 style="color: #10b981;">SMTP Verification Successful!</h2>
            <p>यह टेस्ट ईमेल पुष्टि करता है कि आपके ईमेल <strong>${targetEmail}</strong> और SMTP सर्वर <strong>${formData.host}</strong> से ईमेल सफलतापूर्वक भेजा जा रहा है।</p>
            <p style="color: #666; font-size: 12px;">Sent via FestivaMail Staggered Dispatcher on ${new Date().toLocaleString()}</p>
          </div>`,
          fromName: formData.fromName || 'FestivaMail Tester',
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
          message: `शानदार! टेस्ट ईमेल आपके इनबॉक्स ${targetEmail} पर भेज दिया गया है। (Message ID: ${data.messageId || 'sent'})`,
        });
      } else {
        setVerifyStatus({
          success: false,
          message: data.error || 'टेस्ट ईमेल भेजने में त्रुटि हुई। कृपया क्रेडेंशियल जांचें।',
        });
      }
    } catch (err: any) {
      setVerifyStatus({
        success: false,
        message: err.message || 'नेटवर्क त्रुटि: टेस्ट ईमेल नहीं भेजा जा सका।',
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
          port: 465,
          secure: true,
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
    onClose();
  };

  const activeSender = formData.fromEmail || formData.username || 'Not configured yet';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-fade-in overflow-y-auto">
      <div className="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl text-slate-200 p-6 md:p-8 my-4">
        
        <button
          onClick={onClose}
          className="absolute top-5 right-5 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition"
        >
          <X className="w-5 h-5" />
        </button>

        <div className="flex items-center gap-3 mb-5 border-b border-slate-800 pb-4">
          <div className="p-3 bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 rounded-xl">
            <Server className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-white">ईमेल प्रेषक एवं SMTP सेटिंग्स (Sender & SMTP Setup)</h2>
            <p className="text-xs text-slate-400">
              क्रेडेंशियल्स दर्ज करें ताकि अभियान के सारे ईमेल सीधे आपके व्यक्तिगत ईमेल पते से भेजे जाएं
            </p>
          </div>
        </div>

        {/* Account Sync Status Banner */}
        {currentUser ? (
          <div className="mb-5 p-3 rounded-xl bg-cyan-950/40 border border-cyan-500/40 flex items-center justify-between text-xs text-cyan-200">
            <div className="flex items-center gap-2">
              <ShieldCheck className="w-4 h-4 text-cyan-400 shrink-0" />
              <span>
                लॉगिन खाता: <strong className="text-white">{currentUser.email}</strong> — आपकी SMTP क्रेडेंशियल्स आपके निजी Firestore खाते में एन्क्रिप्टेड और सुरक्षित रहेंगी।
              </span>
            </div>
            <span className="text-[10px] bg-cyan-900/60 text-cyan-300 font-mono px-2 py-0.5 rounded border border-cyan-500/30 shrink-0">
              Cloud Sync Active
            </span>
          </div>
        ) : (
          <div className="mb-5 p-3 rounded-xl bg-amber-950/40 border border-amber-500/40 flex items-center justify-between text-xs text-amber-200">
            <div className="flex items-center gap-2">
              <AlertTriangle className="w-4 h-4 text-amber-400 shrink-0" />
              <span>
                <strong>गेस्ट मोड:</strong> सेटिंग्स केवल इस ब्राउज़र में रहेंगी। किसी भी डिवाइस से एक्सेस और सुरक्षित रखने के लिए अकाउंट लॉगिन करें।
              </span>
            </div>
            <button
              type="button"
              onClick={() => openAuthModal('login')}
              className="px-2.5 py-1 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-lg text-xs shrink-0 transition"
            >
              लॉगिन करें
            </button>
          </div>
        )}

        {/* Highlighted Banner: Verified Sender Notice */}
        <div className="mb-5 p-3.5 rounded-xl bg-gradient-to-r from-emerald-950/70 via-slate-900 to-slate-900 border border-emerald-500/40 flex items-start gap-3">
          <UserCheck className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
          <div className="text-xs">
            <span className="font-semibold text-white block mb-0.5">
              सक्रिय प्रेषक खाता (Active Outgoing Mail):
            </span>
            <p className="text-slate-300">
              जो भी SMTP विवरण आप यहाँ सहेजेंगे, कैम्पेन चलते समय{' '}
              <strong className="text-emerald-400 font-mono underline">{activeSender}</strong> की ओर से ही सभी
              प्राप्तकर्ताओं को ईमेल भेजा जाएगा।
            </p>
          </div>
        </div>

        {/* Mode Switcher */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
          <button
            type="button"
            onClick={() => setFormData((p) => ({ ...p, enabled: true }))}
            className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${
              formData.enabled
                ? 'bg-emerald-500/15 border-emerald-500 text-white shadow-lg shadow-emerald-950/40'
                : 'bg-slate-850 border-slate-800 text-slate-400 hover:bg-slate-800'
            }`}
          >
            <div className="flex items-center justify-between mb-2">
              <span className="font-semibold text-emerald-400 text-sm flex items-center gap-1.5">
                <Server className="w-4 h-4" /> Real Custom SMTP (आपकी मेल से)
              </span>
              {formData.enabled && <CheckCircle2 className="w-4 h-4 text-emerald-400" />}
            </div>
            <p className="text-xs text-slate-300">
              सक्रिय मोड। आपके Gmail या कस्टम ईमेल सर्वर से असली ईमेल इनबॉक्स में भेजे जाएंगे।
            </p>
          </button>

          <button
            type="button"
            onClick={() => setFormData((p) => ({ ...p, enabled: false }))}
            className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${
              !formData.enabled
                ? 'bg-amber-500/15 border-amber-500 text-white shadow-lg shadow-amber-950/40'
                : 'bg-slate-850 border-slate-800 text-slate-400 hover:bg-slate-800'
            }`}
          >
            <div className="flex items-center justify-between mb-2">
              <span className="font-semibold text-amber-400 text-sm flex items-center gap-1.5">
                <ShieldAlert className="w-4 h-4" /> Sandbox / Simulation Mode
              </span>
              {!formData.enabled && <CheckCircle2 className="w-4 h-4 text-amber-400" />}
            </div>
            <p className="text-xs text-slate-300">
              सुरक्षित सिमुलेशन मोड। बिना पासवर्ड डाले 5-मिनट टाइमर और पूरे HTML का अभ्यास करें।
            </p>
          </button>
        </div>

        {/* Sender Info */}
        <div className="space-y-4 mb-5 p-4 rounded-xl bg-slate-850 border border-slate-800">
          <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
            <Mail className="w-4 h-4 text-sky-400" /> प्रेषक जानकारी (Sender Information)
          </h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs text-slate-400 mb-1">
                Company / Sender Name (प्रेषक का नाम)
              </label>
              <input
                type="text"
                value={formData.fromName}
                onChange={(e) => setFormData({ ...formData, fromName: e.target.value })}
                placeholder="e.g. Alekh Banshwar / Acme Corp"
                className="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
            <div>
              <label className="block text-xs text-slate-400 mb-1">
                From Email Address (ईमेल पता जिससे मेल जाएगा)
              </label>
              <input
                type="email"
                value={formData.fromEmail}
                onChange={(e) => setFormData({ ...formData, fromEmail: e.target.value })}
                placeholder="e.g. alekhbanshwar2000@gmail.com"
                className="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500 font-mono"
              />
            </div>
          </div>
        </div>

        {/* SMTP Server Credentials */}
        {formData.enabled && (
          <div className="space-y-4 mb-5 p-4 rounded-xl bg-slate-850 border border-slate-800 animate-fade-in">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <h3 className="text-sm font-semibold text-slate-200 flex items-center gap-2">
                <Key className="w-4 h-4 text-emerald-400" /> SMTP Server Credentials
              </h3>
              
              {/* Presets */}
              <div className="flex flex-wrap items-center gap-1.5 text-xs">
                <span className="text-slate-500 text-[11px]">Quick Setup:</span>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('gmail')}
                  className="px-3 py-1 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 rounded-lg border border-amber-400 font-bold shadow-sm flex items-center gap-1.5 transition"
                  title="Gmail: Default Recommended (smtp.gmail.com:465 using 16-letter App Password)"
                >
                  <span>★ Gmail (Default)</span>
                  <span className="text-[10px] bg-slate-950/20 text-slate-950 px-1.5 py-0.5 rounded font-mono font-bold">16-Letter App Pass</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('elasticmail')}
                  className="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-sky-300 rounded-lg border border-slate-700 font-medium transition"
                  title="Elastic Email: Most affordable high-volume SMTP ($0.50/1k)"
                >
                  <span>⚡ Elastic Email</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('brevo')}
                  className="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg border border-slate-700 transition"
                >
                  Brevo
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('sendgrid')}
                  className="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg border border-slate-700 transition"
                >
                  SendGrid
                </button>
                <button
                  type="button"
                  onClick={() => handleQuickPreset('ses')}
                  className="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg border border-slate-700 transition"
                >
                  AWS SES
                </button>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="sm:col-span-2">
                <label className="block text-xs text-slate-400 mb-1">SMTP Host</label>
                <input
                  type="text"
                  value={formData.host}
                  onChange={(e) => setFormData({ ...formData, host: e.target.value })}
                  placeholder="smtp.elasticemail.com or smtp.gmail.com"
                  className="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500 font-mono"
                />
              </div>
              <div>
                <label className="block text-xs text-slate-400 mb-1">Port</label>
                <input
                  type="number"
                  value={formData.port}
                  onChange={(e) => setFormData({ ...formData, port: Number(e.target.value) })}
                  placeholder="2525, 587, or 465"
                  className="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500 font-mono"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs text-slate-400 mb-1">
                  SMTP Username / Email <span className="text-emerald-400">*</span>
                </label>
                <input
                  type="text"
                  value={formData.username}
                  onChange={(e) => handleUsernameChange(e.target.value)}
                  placeholder="alekhbanshwar2000@gmail.com"
                  className="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500 font-mono"
                />
              </div>
              <div>
                <label className="block text-xs text-slate-400 mb-1 flex items-center justify-between">
                  <span>Password / API Key</span>
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => {
                        setShowElasticHelp(!showElasticHelp);
                        if (!showElasticHelp) setShowGmailHelp(false);
                      }}
                      className="text-[11px] text-sky-400 hover:underline flex items-center gap-0.5"
                    >
                      <HelpCircle className="w-3 h-3" /> Elastic Help
                    </button>
                    <span className="text-slate-600">|</span>
                    <button
                      type="button"
                      onClick={() => {
                        setShowGmailHelp(!showGmailHelp);
                        if (!showGmailHelp) setShowElasticHelp(false);
                      }}
                      className="text-[11px] text-amber-400 hover:underline flex items-center gap-0.5"
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
                  className="w-full px-3 py-2 text-sm bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500 font-mono"
                />
              </div>
            </div>

            {/* Elastic Email Step-by-Step Guide */}
            {showElasticHelp && (
              <div className="p-3.5 rounded-xl bg-slate-900/90 border border-sky-500/40 text-xs text-slate-300 space-y-2 animate-fade-in">
                <div className="font-semibold text-sky-400 flex items-center justify-between">
                  <span className="flex items-center gap-1.5">
                    <span>⚡ Elastic Email क्रेडेंशियल्स सेटअप गाइड (सस्ता और 100% विश्वसनीय):</span>
                  </span>
                  <button
                    type="button"
                    onClick={() => setShowElasticHelp(false)}
                    className="text-slate-400 hover:text-white"
                  >
                    ✕
                  </button>
                </div>
                <ol className="list-decimal list-inside space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
                  <li>
                    <a
                      href="https://elasticemail.com"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-sky-400 underline font-semibold inline-flex items-center gap-0.5"
                    >
                      Elastic Email <ExternalLink className="w-2.5 h-2.5" />
                    </a>{' '}
                    पर फ्री अकाउंट बनाएं या लॉगिन करें।
                  </li>
                  <li>
                    Elastic Email डैशबोर्ड में जाकर <strong>Settings → Manage API Keys</strong> खोलें।
                  </li>
                  <li>
                    <strong>&quot;Create API Key&quot;</strong> पर क्लिक करें, उसे <em>Full Access</em> दें और जनरेट की हुई <strong>API Key</strong> को कॉपी करें।
                  </li>
                  <li>
                    यहाँ क्रेडेंशियल्स में भरें:
                    <ul className="list-disc list-inside pl-4 mt-1 text-slate-300 space-y-0.5">
                      <li><strong>SMTP Host:</strong> <code className="text-sky-300 bg-slate-950 px-1.5 py-0.5 rounded">smtp.elasticemail.com</code></li>
                      <li><strong>Port:</strong> <code className="text-sky-300 bg-slate-950 px-1.5 py-0.5 rounded">2525</code> (या <code className="text-sky-300 bg-slate-950 px-1.5 py-0.5 rounded">587</code>)</li>
                      <li><strong>SMTP Username:</strong> आपका Elastic Email रजिस्टर्ड लॉगिन ईमेल</li>
                      <li><strong>Password:</strong> आपकी जनरेट की हुई <strong>API Key</strong></li>
                    </ul>
                  </li>
                  <li className="text-emerald-400">
                    💡 <em>फायदा:</em> Elastic Email दुनिया के सबसे सस्ते प्रोवाइडर्स में से एक है ($0.50 प्रति 1,000 मेल) और 5-मिनट स्टैगर्ड डिस्पैच के साथ इसका इनबॉक्स डिलीवरी रेट 99%+ रहता है!
                  </li>
                </ol>
              </div>
            )}

            {/* Gmail App Password Step-by-Step Guide */}
            {showGmailHelp && (
              <div className="p-3.5 rounded-xl bg-slate-900/90 border border-amber-500/40 text-xs text-slate-300 space-y-2">
                <div className="font-semibold text-amber-400 flex items-center justify-between">
                  <span>📌 Gmail App Password कैसे बनाएं (30 सेकंड):</span>
                  <button
                    type="button"
                    onClick={() => setShowGmailHelp(false)}
                    className="text-slate-400 hover:text-white"
                  >
                    ✕
                  </button>
                </div>
                <ol className="list-decimal list-inside space-y-1 text-slate-300 text-[11px] leading-relaxed">
                  <li>
                    अपने Google Account की{' '}
                    <a
                      href="https://myaccount.google.com/security"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-emerald-400 underline font-semibold"
                    >
                      Security Settings
                    </a>{' '}
                    में जाएं।
                  </li>
                  <li>सुनिश्चित करें कि <strong>2-Step Verification</strong> चालू (ON) है।</li>
                  <li>
                    Search बार में <strong>&quot;App passwords&quot;</strong> टाइप करें या Security में नीचे App passwords खोलें।
                  </li>
                  <li>ऐप का नाम <code>FestivaMail</code> लिखें और <strong>Create</strong> पर क्लिक करें।</li>
                  <li>जो 16-अक्षरों का पीला कोड दिखेगा (उदा. <code>abcd efgh ijkl mnop</code>), उसे यहाँ Password में पेस्ट करें।</li>
                </ol>
              </div>
            )}

            <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
              <label className="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={formData.secure}
                  onChange={(e) => setFormData({ ...formData, secure: e.target.checked })}
                  className="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0"
                />
                Use SSL / TLS (पोर्ट 465 के लिए अनुशंसित)
              </label>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleTestConnection}
                  disabled={isVerifying || isSendingTest}
                  className="px-3 py-1.5 bg-slate-800 hover:bg-slate-750 text-xs font-semibold text-slate-200 border border-slate-700 rounded-lg flex items-center gap-1.5 transition"
                >
                  {isVerifying ? (
                    <>
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-emerald-400" /> जाँच हो रही है...
                    </>
                  ) : (
                    <>
                      <Server className="w-3.5 h-3.5 text-emerald-400" /> Test Connection
                    </>
                  )}
                </button>

                <button
                  type="button"
                  onClick={handleSendLiveTest}
                  disabled={isVerifying || isSendingTest}
                  className="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-600 text-xs font-semibold text-white rounded-lg flex items-center gap-1.5 shadow transition"
                  title="Send a real test email directly to your own address"
                >
                  {isSendingTest ? (
                    <>
                      <Loader2 className="w-3.5 h-3.5 animate-spin text-white" /> टेस्ट ईमेल भेजा जा रहा है...
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
                className={`p-3 rounded-xl text-xs flex items-start gap-2.5 ${
                  verifyStatus.success
                    ? 'bg-emerald-950/70 text-emerald-200 border border-emerald-800'
                    : 'bg-rose-950/70 text-rose-200 border border-rose-800'
                }`}
              >
                {verifyStatus.success ? (
                  <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-400 mt-0.5" />
                ) : (
                  <AlertTriangle className="w-4 h-4 shrink-0 text-rose-400 mt-0.5" />
                )}
                <span className="leading-relaxed">{verifyStatus.message}</span>
              </div>
            )}
          </div>
        )}

        {/* Footer */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-800">
          <div className="text-xs text-slate-400 text-center sm:text-left">
            <span>सक्रिय मोड: </span>
            <strong className={formData.enabled ? 'text-emerald-400 font-semibold' : 'text-amber-400'}>
              {formData.enabled
                ? `● Real SMTP Active (${formData.fromEmail || formData.username || 'Custom'})`
                : '● Safe Sandbox Simulation'}
            </strong>
          </div>

          <div className="flex items-center gap-2.5 w-full sm:w-auto justify-end">
            <button
              onClick={onClose}
              className="px-4 py-2 text-xs text-slate-400 hover:text-white rounded-lg transition"
            >
              Cancel
            </button>
            <button
              onClick={handleSave}
              className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-emerald-950/60 transition flex items-center gap-1.5"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>क्रेडेंशियल सेव करें (Save & Use My Mail)</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};
