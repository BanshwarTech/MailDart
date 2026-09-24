import React, { useState, useRef, useMemo } from 'react';
import {
  Code,
  Eye,
  Smartphone,
  Monitor,
  Copy,
  Check,
  RotateCcw,
  Sparkles,
  Layers,
  Tag,
  ExternalLink,
  Split,
  Maximize2,
  Plus,
  X,
  Sliders,
  CheckCircle2,
  HelpCircle,
  FileSpreadsheet,
  Send,
} from 'lucide-react';
import { CampaignState, FestivalPreset, Recipient } from '../types';
import { SelectMenu } from './SelectMenu';
import { PRESET_TEMPLATES } from '../data/presetTemplates';
import { replacePlaceholders } from '../utils/campaignHelper';

interface TemplateEditorProps {
  campaign: CampaignState;
  onUpdateCampaign: (updates: Partial<CampaignState>) => void;
  onOpenAiModal: () => void;
  onOpenTestEmail: () => void;
}

export const TemplateEditor: React.FC<TemplateEditorProps> = ({
  campaign,
  onUpdateCampaign,
  onOpenAiModal,
  onOpenTestEmail,
}) => {
  const [activeTab, setActiveTab] = useState<'editor' | 'preview' | 'split'>('split');
  const [previewDevice, setPreviewDevice] = useState<'desktop' | 'mobile'>('desktop');
  const [selectedRecipientId, setSelectedRecipientId] = useState<string>(
    campaign.recipients[0]?.id || ''
  );
  const [isCopied, setIsCopied] = useState(false);
  const [showVarSetupModal, setShowVarSetupModal] = useState(false);

  // New Variable Setup Modal State
  const [newVarName, setNewVarName] = useState('');
  const [newVarDefaultVal, setNewVarDefaultVal] = useState('');

  const textareaRef = useRef<HTMLTextAreaElement>(null);

  // Extract all distinct custom variables present from Excel or campaign recipients
  const customVariableKeys = useMemo(() => {
    const keysSet = new Set<string>();
    if (campaign.customVariables) {
      campaign.customVariables.forEach((k) => {
        const lower = k.toLowerCase().trim();
        if (lower !== 'name' && lower !== 'email') {
          keysSet.add(k.trim());
        }
      });
    }
    campaign.recipients.forEach((r) => {
      if (r.customData) {
        Object.keys(r.customData).forEach((k) => {
          const lower = k.toLowerCase().trim();
          if (lower !== 'name' && lower !== 'email') {
            keysSet.add(k.trim());
          }
        });
      }
    });
    return Array.from(keysSet);
  }, [campaign.customVariables, campaign.recipients]);

  // Selected recipient for live preview
  const currentRecipient =
    campaign.recipients.find((r) => r.id === selectedRecipientId) ||
    campaign.recipients[0] || {
      id: 'preview-default',
      name: 'Aarav Sharma',
      email: 'aarav.sharma@example.com',
      customData: {
        city: 'Mumbai',
        gift: 'Royal Sweets Hamper',
      },
      status: 'pending' as const,
    };

  // Personalized HTML for live preview
  const personalizedPreviewHtml = replacePlaceholders(
    campaign.htmlTemplate,
    currentRecipient,
    campaign
  );

  const personalizedSubject = replacePlaceholders(
    campaign.subject,
    currentRecipient,
    campaign
  );

  // Handle Tag Insertion at cursor position
  const handleInsertTag = (tag: string) => {
    if (!textareaRef.current) return;
    const start = textareaRef.current.selectionStart;
    const end = textareaRef.current.selectionEnd;
    const current = campaign.htmlTemplate;
    const updated = current.substring(0, start) + tag + current.substring(end);
    onUpdateCampaign({ htmlTemplate: updated });

    setTimeout(() => {
      if (textareaRef.current) {
        textareaRef.current.focus();
        textareaRef.current.setSelectionRange(start + tag.length, start + tag.length);
      }
    }, 50);
  };

  // Insert all auto-generated Excel variables as an attractive HTML block
  const handleInsertAllExcelVariables = () => {
    if (customVariableKeys.length === 0) return;
    const rows = customVariableKeys
      .map(
        (key) =>
          `    <p style="margin: 4px 0; font-size: 13px; color: #cbd5e1;"><strong style="color: #ffffff; text-transform: capitalize;">${key.replace(/_/g, ' ')}:</strong> {{${key}}}</p>`
      )
      .join('\n');

    const blockHtml = `\n<!-- Auto-Generated Excel Variables Block -->\n<div style="margin: 20px 0; padding: 14px 18px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(26, 61, 99, 0.35); border-left: 4px solid #1a3d63; border-radius: 8px;">\n  <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #1a3d63;">✨ Your Personalised Details:</p>\n${rows}\n</div>\n`;

    handleInsertTag(blockHtml);
  };

  const handleCreateCustomVariable = (e: React.FormEvent) => {
    e.preventDefault();
    const cleanKey = newVarName.trim().replace(/[^\w-]/g, '_');
    if (!cleanKey) return;

    const tagToInsert = `{{${cleanKey}}}`;

    // Apply to current recipients if requested
    if (newVarDefaultVal.trim() && campaign.recipients.length > 0) {
      const updatedRecipients = campaign.recipients.map((r) => ({
        ...r,
        customData: {
          ...(r.customData || {}),
          [cleanKey]: r.customData?.[cleanKey] || newVarDefaultVal.trim(),
        },
      }));
      onUpdateCampaign({ recipients: updatedRecipients });
    }

    // Insert into template at cursor
    handleInsertTag(tagToInsert);

    setNewVarName('');
    setNewVarDefaultVal('');
    setShowVarSetupModal(false);
  };

  const handleCopyHtml = () => {
    navigator.clipboard.writeText(campaign.htmlTemplate);
    setIsCopied(true);
    setTimeout(() => setIsCopied(false), 2000);
  };

  const handleSelectPreset = (preset: FestivalPreset) => {
    if (
      window.confirm(
        `Load "${preset.name}"? This will replace the current HTML template with the festive design.`
      )
    ) {
      onUpdateCampaign({
        festival: preset.festival,
        htmlTemplate: preset.html,
        subject: preset.subject,
        discountCode: preset.defaultDiscount,
      });
    }
  };

  const presetDisplayName = (preset: FestivalPreset) => preset.name.split(' (')[0];

  const builtInTags = [
    { tag: '{{name}}', hint: 'Recipient name' },
    { tag: '{{company}}', hint: 'Your company name' },
    { tag: '{{discount}}', hint: 'Discount code' },
    { tag: '{{festival}}', hint: 'Occasion / category' },
    { tag: '{{email}}', hint: 'Recipient email' },
  ];

  const chip =
    'inline-flex items-center h-7 px-1.5 rounded-md bg-surface ring-1 ring-inset ring-slate-200 text-slate-600 font-mono text-[11px] hover:bg-brand-50 hover:ring-brand-200 hover:text-brand-800 transition';

  const viewTabs: { id: typeof activeTab; label: string; icon: React.ElementType; className?: string }[] = [
    { id: 'editor', label: 'Code', icon: Code },
    { id: 'split', label: 'Split', icon: Split, className: 'hidden lg:inline-flex' },
    { id: 'preview', label: 'Preview', icon: Eye },
  ];

  return (
    <div className="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col h-full">

      {/* Header */}
      <div className="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div className="flex items-center gap-3 min-w-0">
          <div className="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <Code className="w-5 h-5" />
          </div>
          <div className="min-w-0">
            <h2 className="text-base font-semibold tracking-tight text-slate-900">Email Template Studio</h2>
            <p className="text-sm text-slate-500">Choose a template, personalise it and preview it live</p>
          </div>
        </div>

        <div className="flex flex-wrap xl:flex-nowrap items-center gap-2 shrink-0">
          {/* View switch */}
          <div className="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
            {viewTabs.map((t) => {
              const Icon = t.icon;
              return (
                <button
                  key={t.id}
                  onClick={() => setActiveTab(t.id)}
                  className={`${t.className ?? 'inline-flex'} items-center gap-1.5 h-7 px-3 text-xs font-medium rounded-md transition ${
                    activeTab === t.id ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                  }`}
                >
                  <Icon className="w-3.5 h-3.5" />
                  {t.label}
                </button>
              );
            })}
          </div>

          <button
            onClick={handleCopyHtml}
            className="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
            title="Copy HTML to clipboard"
          >
            {isCopied ? <Check className="w-3.5 h-3.5 text-brand-700" /> : <Copy className="w-3.5 h-3.5" />}
            <span>{isCopied ? 'Copied!' : 'Copy Code'}</span>
          </button>

          <button
            onClick={onOpenTestEmail}
            className="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
            title="Send this email to yourself to check it in a real inbox"
          >
            <Send className="w-3.5 h-3.5" />
            <span>Send test</span>
          </button>

          <button
            onClick={onOpenAiModal}
            className="inline-flex items-center gap-1.5 h-9 px-3.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition shadow-button"
            title="Generate festive HTML templates using NVIDIA NIM or Gemini AI"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>AI Studio</span>
          </button>
        </div>
      </div>

      {/* Setup: template, subject & discount */}
      <div className="px-5 sm:px-6 py-5 border-b border-slate-200/80 space-y-5">
        <div>
          <p className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-2">
            <Layers className="w-3.5 h-3.5 text-slate-400" />
            Start from a template
          </p>
          <div className="relative">
            <div className="flex items-center gap-2 overflow-x-auto pb-1 pr-8 [scrollbar-width:thin]">
              {PRESET_TEMPLATES.map((preset) => {
                const isCurrent = campaign.festival === preset.festival;
                return (
                  <button
                    key={preset.id}
                    onClick={() => handleSelectPreset(preset)}
                    className={`shrink-0 inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-medium whitespace-nowrap ring-1 ring-inset transition ${
                      isCurrent
                        ? 'bg-brand-50 text-brand-700 ring-brand-200'
                        : 'bg-surface text-slate-600 ring-slate-200 hover:ring-slate-300 hover:text-slate-900'
                    }`}
                  >
                    {isCurrent && <Check className="w-3.5 h-3.5" />}
                    {presetDisplayName(preset)}
                  </button>
                );
              })}
            </div>
            <div className="pointer-events-none absolute inset-y-0 right-0 w-10 bg-gradient-to-l from-surface to-transparent" />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_240px] gap-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1.5">Email Subject Line</label>
            <input
              type="text"
              value={campaign.subject}
              onChange={(e) => onUpdateCampaign({ subject: e.target.value })}
              placeholder="e.g. Happy Diwali from {{company}} to {{name}}! 🪔"
              className="w-full h-10 px-3 text-sm bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            />
            <p className="mt-1.5 text-[11px] text-slate-400">Supports {'{{name}}'}, {'{{company}}'}, {'{{city}}'} and other tags.</p>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1.5">Default Discount Code</label>
            <input
              type="text"
              value={campaign.discountCode}
              onChange={(e) => onUpdateCampaign({ discountCode: e.target.value })}
              placeholder="e.g. FESTIVE50"
              className="w-full h-10 px-3 text-sm bg-surface border border-slate-300 rounded-lg text-slate-900 font-mono font-semibold tracking-wide uppercase focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
            />
            <p className="mt-1.5 text-[11px] text-slate-400">Fallback for {'{{discount}}'}.</p>
          </div>
        </div>
      </div>

      {/* Main Workspace: Code Editor & Live Preview */}
      <div className="flex-1 grid grid-cols-1 lg:grid-cols-2 min-h-[560px] divide-y lg:divide-y-0 lg:divide-x divide-slate-200/80">

        {/* Code Editor Panel */}
        {(activeTab === 'editor' || activeTab === 'split') && (
          <div className={`flex flex-col h-full min-w-0 bg-slate-50/60 ${activeTab === 'editor' ? 'lg:col-span-2' : ''}`}>
            {/* Editor toolbar: variables */}
            <div className="px-4 py-3 min-h-[61px] bg-surface border-b border-slate-200/80 flex items-center">
              <div className="flex flex-wrap items-center gap-1.5">
                <span className="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mr-1" title="Click a tag to insert it at the cursor">
                  <Tag className="w-3.5 h-3.5 text-slate-400" />
                  Insert
                </span>
                {builtInTags.map((item) => (
                  <button key={item.tag} type="button" onClick={() => handleInsertTag(item.tag)} className={chip} title={`${item.hint}: insert ${item.tag}`}>
                    {item.tag}
                  </button>
                ))}

                {customVariableKeys.map((key) => (
                  <button
                    key={key}
                    type="button"
                    onClick={() => handleInsertTag(`{{${key}}}`)}
                    className={`${chip} gap-1`}
                    title={`From your Excel sheet: insert {{${key}}}`}
                  >
                    <FileSpreadsheet className="w-3 h-3 text-slate-400" />
                    {'{{' + key + '}}'}
                  </button>
                ))}

                {customVariableKeys.length > 0 && (
                  <button
                    type="button"
                    onClick={handleInsertAllExcelVariables}
                    className="inline-flex items-center h-7 px-2.5 rounded-md text-[11px] font-semibold text-brand-700 hover:bg-brand-50 transition"
                    title="Insert a block with all Excel variables"
                  >
                    Insert all
                  </button>
                )}

                <button
                  type="button"
                  onClick={() => setShowVarSetupModal(true)}
                  className="inline-flex items-center justify-center h-7 w-7 rounded-md text-slate-500 border border-dashed border-slate-300 hover:text-brand-800 hover:border-brand-300 hover:bg-brand-50 transition"
                  title="Add a custom tag (e.g. {{city}}, {{gift}}, {{order_id}})"
                  aria-label="Add custom tag"
                >
                  <Plus className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div className="px-4 py-2 border-b border-slate-200/80 flex items-center justify-between">
              <span className="font-mono text-[11px] font-medium text-slate-500">index.html <span className="font-sans text-slate-400">· auto-saves</span></span>
              <span className="text-[11px] text-slate-400 tabular-nums">{campaign.htmlTemplate.split('\n').length} lines</span>
            </div>

            <textarea
              ref={textareaRef}
              value={campaign.htmlTemplate}
              onChange={(e) => onUpdateCampaign({ htmlTemplate: e.target.value })}
              placeholder="Paste your HTML email template here..."
              spellCheck={false}
              className="flex-1 w-full p-4 font-mono text-xs bg-transparent text-slate-800 resize-none focus:outline-none leading-relaxed selection:bg-brand-200 overflow-auto"
            />
          </div>
        )}

        {/* Live Preview Panel */}
        {(activeTab === 'preview' || activeTab === 'split') && (
          <div className={`flex flex-col h-full min-w-0 bg-surface ${activeTab === 'preview' ? 'lg:col-span-2' : ''}`}>

            {/* Preview Controls Bar */}
            <div className="px-4 py-3 min-h-[61px] bg-surface border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
              <div className="flex items-center gap-2 min-w-0">
                <span className="text-xs font-semibold text-slate-700 shrink-0">Preview as</span>
                <SelectMenu
                  className="w-64 max-w-full"
                  aria-label="Preview as recipient"
                  value={selectedRecipientId}
                  onChange={setSelectedRecipientId}
                  placeholder="No recipients yet"
                  monoDescription
                  options={campaign.recipients.map((rec) => ({
                    value: rec.id,
                    label: rec.name || rec.email,
                    description: rec.email,
                  }))}
                />
              </div>

              {/* Device switch */}
              <div className="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
                <button
                  onClick={() => setPreviewDevice('desktop')}
                  className={`h-7 w-8 inline-flex items-center justify-center rounded-md transition ${previewDevice === 'desktop' ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'}`}
                  title="Desktop View (600px)"
                >
                  <Monitor className="w-3.5 h-3.5" />
                </button>
                <button
                  onClick={() => setPreviewDevice('mobile')}
                  className={`h-7 w-8 inline-flex items-center justify-center rounded-md transition ${previewDevice === 'mobile' ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'}`}
                  title="Mobile View (375px)"
                >
                  <Smartphone className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            {/* Email client style header */}
            <div className="px-4 py-3 border-b border-slate-200/80 text-xs space-y-1">
              <div className="flex items-baseline gap-2">
                <span className="text-slate-400 w-14 shrink-0">Subject</span>
                <span className="font-semibold text-slate-900 truncate">{personalizedSubject || 'No Subject'}</span>
              </div>
              <div className="flex items-baseline gap-2">
                <span className="text-slate-400 w-14 shrink-0">To</span>
                <span className="text-slate-600 truncate">{currentRecipient.name} &lt;{currentRecipient.email}&gt;</span>
              </div>
              {currentRecipient.customData && Object.keys(currentRecipient.customData).length > 0 && (
                <div className="flex items-start gap-2 pt-1">
                  <span className="text-slate-400 w-14 shrink-0 pt-0.5">Data</span>
                  <div className="flex flex-wrap gap-1">
                    {Object.entries(currentRecipient.customData).slice(0, 4).map(([k, v]) => (
                      <span key={k} className="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10.5px]">
                        <span className="text-slate-400">{k}:</span> {v}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* Rendered HTML Iframe Container */}
            <div className="flex-1 p-5 overflow-auto flex items-start justify-center bg-slate-100/70">
              <div
                className={`bg-surface rounded-2xl overflow-hidden shadow-card border border-slate-200/80 transition-all duration-300 ${ previewDevice === 'desktop'
                    ? 'w-full max-w-[620px] h-[550px]'
                    : 'w-[375px] h-[580px] border-4 border-slate-300 rounded-3xl'
                }`}
              >
                <iframe
                  title="Email Preview"
                  srcDoc={personalizedPreviewHtml}
                  className="w-full h-full border-0"
                  sandbox="allow-same-origin"
                />
              </div>
            </div>

          </div>
        )}

      </div>

      {/* Modal: Setup Custom Mail Variable */}
      {showVarSetupModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in">
          <div className="w-full max-w-md bg-surface border border-slate-200 rounded-2xl p-6 shadow-modal text-slate-700">
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Sliders className="w-5 h-5 text-brand-700" />
                <h3 className="text-sm font-semibold text-slate-900">Add a Custom Variable</h3>
              </div>
              <button
                onClick={() => setShowVarSetupModal(false)}
                className="text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg p-1"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs text-slate-500 mb-4">
              Want to send personalised data to each recipient (like <code>city</code>, <code>gift</code>, <code>phone</code> or <code>order_id</code>)? Create a new variable here:
            </p>

            <form onSubmit={handleCreateCustomVariable} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Variable Name <span className="text-red-600">*</span>
                </label>
                <div className="relative">
                  <span className="absolute left-3 top-2 text-amber-600 font-mono text-xs">{'{{'}</span>
                  <input
                    type="text"
                    value={newVarName}
                    onChange={(e) => setNewVarName(e.target.value.toLowerCase().replace(/[^\w-]/g, '_'))}
                    placeholder="e.g. city, gift_hamper or order_id"
                    required
                    className="w-full pl-8 pr-8 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-amber-700 font-mono focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-semibold"
                  />
                  <span className="absolute right-3 top-2 text-amber-600 font-mono text-xs">{'}}'}</span>
                </div>
                <p className="text-[10px] text-slate-400 mt-1">
                  Wherever <code>{'{{' + (newVarName || 'variable') + '}}'}</code> appears in the email, it will be replaced with each recipient's value.
                </p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Default Value (for all recipients)
                </label>
                <input
                  type="text"
                  value={newVarDefaultVal}
                  onChange={(e) => setNewVarDefaultVal(e.target.value)}
                  placeholder="e.g. Royal Sweets Box, Mumbai or Valued Customer"
                  className="w-full px-3 py-2 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
                />
                <p className="text-[10px] text-slate-400 mt-1">
                  Recipients who don't have this value will get the default value instead.
                </p>
              </div>

              <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowVarSetupModal(false)}
                  className="px-3.5 py-1.5 text-xs text-slate-500 hover:text-slate-900"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs rounded-lg transition flex items-center gap-1.5 shadow-button"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  <span>Save Variable & Insert in Template</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
};
