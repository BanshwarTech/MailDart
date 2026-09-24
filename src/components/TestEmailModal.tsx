import React, { useState } from 'react';
import { X, Send, CheckCircle2, AlertCircle, Loader2, Mail } from 'lucide-react';
import { SmtpConfig, CampaignState } from '../types';
import { replacePlaceholders, getSmtpStatus } from '../utils/campaignHelper';

interface TestEmailModalProps {
  isOpen: boolean;
  onClose: () => void;
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
}

export const TestEmailModal: React.FC<TestEmailModalProps> = ({
  isOpen,
  onClose,
  campaign,
  smtpConfig,
}) => {
  const [testEmail, setTestEmail] = useState('');
  const [testName, setTestName] = useState('Campaign Reviewer');
  const [isSending, setIsSending] = useState(false);
  const [result, setResult] = useState<{ success?: boolean; message?: string } | null>(null);

  if (!isOpen) return null;

  const handleSendTest = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!testEmail || !testEmail.includes('@')) {
      setResult({ success: false, message: 'Please enter a valid test recipient email.' });
      return;
    }

    setIsSending(true);
    setResult(null);

    const personalizedSubject = replacePlaceholders(campaign.subject, { name: testName, email: testEmail }, campaign);
    const personalizedHtml = replacePlaceholders(campaign.htmlTemplate, { name: testName, email: testEmail }, campaign);

    try {
      const res = await fetch('/api/send-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          to: testEmail,
          subject: `[TEST] ${personalizedSubject}`,
          html: personalizedHtml,
          fromName: smtpConfig.fromName || campaign.companyName,
          fromEmail: smtpConfig.fromEmail,
          replyTo: smtpConfig.replyTo,
          smtpConfig: smtpConfig.enabled ? smtpConfig : undefined,
          simulate: !smtpConfig.enabled,
        }),
      });

      const data = await res.json();
      if (data.success) {
        setResult({
          success: true,
          message: smtpConfig.enabled
            ? `Real test email delivered to ${testEmail}! (ID: ${data.messageId})`
            : `Delivered in simulation/sandbox mode to ${testEmail}! (Valid HTML parsed & verified)`,
        });
      } else {
        setResult({ success: false, message: data.error || 'Failed to dispatch test email.' });
      }
    } catch (err: any) {
      setResult({ success: false, message: err.message || 'Network error occurred.' });
    } finally {
      setIsSending(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in">
      <div className="relative w-full max-w-md bg-surface border border-slate-200 rounded-2xl shadow-modal text-slate-700 p-6">

        <button
          onClick={onClose}
          className="absolute top-4 right-4 p-2 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition"
        >
          <X className="w-5 h-5" />
        </button>

        <div className="flex items-center gap-3 mb-5 border-b border-slate-200 pb-3">
          <div className="p-2.5 bg-brand-50 text-brand-700 rounded-xl">
            <Mail className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-lg font-semibold text-slate-900">Send Single Test Email</h2>
            <p className="text-sm text-slate-500">Verify rendering in your personal inbox before bulk run</p>
          </div>
        </div>

        <form onSubmit={handleSendTest} className="space-y-4">
          <div>
            <label className="block text-xs text-slate-500 mb-1">Test Recipient Email</label>
            <input
              type="email"
              value={testEmail}
              onChange={(e) => setTestEmail(e.target.value)}
              placeholder="e.g. yourname@gmail.com"
              required
              className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            />
          </div>

          <div>
            <label className="block text-xs text-slate-500 mb-1">Sample Recipient Name</label>
            <input
              type="text"
              value={testName}
              onChange={(e) => setTestName(e.target.value)}
              placeholder="e.g. Aarav Sharma"
              className="w-full px-3 py-2 text-sm bg-surface border border-slate-300 text-slate-900 placeholder:text-slate-400 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            />
          </div>

          <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
            <span className="font-semibold text-slate-900 block mb-0.5">Mode:</span>
            {getSmtpStatus(smtpConfig) === 'live' ? (
              <span className="text-brand-700 font-medium">
                ● Live SMTP ({smtpConfig.host}) - a real email will be sent
              </span>
            ) : getSmtpStatus(smtpConfig) === 'incomplete' ? (
              <span className="text-amber-700 font-medium">
                ● SMTP not configured - add your username and password in SMTP Settings
              </span>
            ) : (
              <span className="text-amber-700 font-medium">
                ● Sandbox / Simulation Active - Safe test delivery verification
              </span>
            )}
          </div>

          {result && (
            <div
              className={`p-3 rounded-lg text-xs flex items-center gap-2 ${ result.success ? 'bg-brand-50 text-brand-700 border border-brand-200'
                  : 'bg-red-50 text-red-700 border border-red-200'
              }`}
            >
              {result.success ? (
                <CheckCircle2 className="w-4 h-4 shrink-0 text-brand-700" />
              ) : (
                <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
              )}
              <span>{result.message}</span>
            </div>
          )}

          <div className="pt-2 flex justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
            >
              Close
            </button>
            <button
              type="submit"
              disabled={isSending}
              className="px-4 py-2 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs rounded-xl flex items-center gap-1.5 transition shadow-button"
            >
              {isSending ? (
                <>
                  <Loader2 className="w-3.5 h-3.5 animate-spin" />
                  Dispatching...
                </>
              ) : (
                <>
                  <Send className="w-3.5 h-3.5" />
                  Send Test Email
                </>
              )}
            </button>
          </div>
        </form>

      </div>
    </div>
  );
};
