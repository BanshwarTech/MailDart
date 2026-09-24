import React, { useState } from 'react';
import { X, Send, CheckCircle2, AlertCircle, Loader2, Mail } from 'lucide-react';
import { SmtpConfig, CampaignState } from '../types';
import { replacePlaceholders } from '../utils/campaignHelper';

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
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
      <div className="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl text-slate-200 p-6">
        
        <button
          onClick={onClose}
          className="absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition"
        >
          <X className="w-5 h-5" />
        </button>

        <div className="flex items-center gap-3 mb-5 border-b border-slate-800 pb-3">
          <div className="p-2.5 bg-blue-600/20 text-blue-400 border border-blue-500/30 rounded-xl">
            <Mail className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">Send Single Test Email</h2>
            <p className="text-xs text-slate-400">Verify rendering in your personal inbox before bulk run</p>
          </div>
        </div>

        <form onSubmit={handleSendTest} className="space-y-4">
          <div>
            <label className="block text-xs text-slate-400 mb-1">Test Recipient Email</label>
            <input
              type="email"
              value={testEmail}
              onChange={(e) => setTestEmail(e.target.value)}
              placeholder="e.g. yourname@gmail.com"
              required
              className="w-full px-3 py-2 text-sm bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-blue-500"
            />
          </div>

          <div>
            <label className="block text-xs text-slate-400 mb-1">Sample Recipient Name</label>
            <input
              type="text"
              value={testName}
              onChange={(e) => setTestName(e.target.value)}
              placeholder="e.g. Aarav Sharma"
              className="w-full px-3 py-2 text-sm bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-blue-500"
            />
          </div>

          <div className="p-3 rounded-lg bg-slate-800/60 border border-slate-750 text-xs text-slate-300">
            <span className="font-semibold text-slate-200 block mb-0.5">Mode:</span>
            {smtpConfig.enabled ? (
              <span className="text-emerald-400 font-medium">
                ● Live SMTP Active ({smtpConfig.host}) - Real email will be sent
              </span>
            ) : (
              <span className="text-amber-400 font-medium">
                ● Sandbox / Simulation Active - Safe test delivery verification
              </span>
            )}
          </div>

          {result && (
            <div
              className={`p-3 rounded-lg text-xs flex items-center gap-2 ${
                result.success
                  ? 'bg-emerald-950/60 text-emerald-300 border border-emerald-800'
                  : 'bg-rose-950/60 text-rose-300 border border-rose-800'
              }`}
            >
              {result.success ? (
                <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-400" />
              ) : (
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-400" />
              )}
              <span>{result.message}</span>
            </div>
          )}

          <div className="pt-2 flex justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="px-3 py-1.5 text-xs text-slate-400 hover:text-white"
            >
              Close
            </button>
            <button
              type="submit"
              disabled={isSending}
              className="px-4 py-2 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-medium text-xs rounded-xl shadow-lg flex items-center gap-1.5 transition"
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
