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
  FileSpreadsheet
} from 'lucide-react';
import { CampaignState, FestivalPreset, Recipient } from '../types';
import { PRESET_TEMPLATES } from '../data/presetTemplates';
import { replacePlaceholders } from '../utils/campaignHelper';

interface TemplateEditorProps {
  campaign: CampaignState;
  onUpdateCampaign: (updates: Partial<CampaignState>) => void;
  onOpenAiModal: () => void;
}

export const TemplateEditor: React.FC<TemplateEditorProps> = ({
  campaign,
  onUpdateCampaign,
  onOpenAiModal,
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

    const blockHtml = `\n<!-- Auto-Generated Excel Variables Block -->\n<div style="margin: 20px 0; padding: 14px 18px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(16, 185, 129, 0.4); border-left: 4px solid #10b981; border-radius: 8px;">\n  <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: bold; color: #10b981;">✨ आपके लिए विशेष जानकारी (Personalized Details):</p>\n${rows}\n</div>\n`;

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

  return (
    <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl flex flex-col h-full">
      
      {/* Top Bar: Festival presets & Title */}
      <div className="p-4 border-b border-slate-800 bg-slate-900/60">
        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 mb-3">
          <div>
            <h2 className="text-base font-bold text-white flex items-center gap-2">
              <Code className="w-4 h-4 text-amber-400" />
              ईमेल टेम्पलेट स्टूडियो (Email HTML Template Studio)
            </h2>
            <p className="text-xs text-slate-400">
              Paste any custom HTML code or choose a festive template with variables
            </p>
          </div>

          {/* Quick Actions */}
          <div className="flex items-center gap-2 w-full md:w-auto justify-end">
            <button
              onClick={onOpenAiModal}
              className="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 text-white text-xs font-semibold rounded-xl shadow transition"
              title="Generate festive HTML templates using NVIDIA NIM or Gemini AI"
            >
              <Sparkles className="w-3.5 h-3.5" />
              <span>AI Studio (NVIDIA / Gemini)</span>
            </button>

            <button
              onClick={handleCopyHtml}
              className="flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-medium rounded-xl border border-slate-700 transition"
              title="Copy HTML to clipboard"
            >
              {isCopied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{isCopied ? 'Copied!' : 'Copy Code'}</span>
            </button>
          </div>
        </div>

        {/* Preset Festival Buttons */}
        <div className="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
          <span className="text-slate-400 text-[11px] font-semibold flex items-center gap-1 shrink-0 mr-1">
            <Layers className="w-3 h-3 text-slate-400" /> Presets:
          </span>
          {PRESET_TEMPLATES.map((preset) => {
            const isCurrent = campaign.festival === preset.festival;
            return (
              <button
                key={preset.id}
                onClick={() => handleSelectPreset(preset)}
                className={`px-3 py-1 rounded-lg text-xs font-medium transition whitespace-nowrap border ${
                  isCurrent
                    ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-sm'
                    : 'bg-slate-800/80 hover:bg-slate-750 text-slate-400 border-slate-750 hover:text-slate-200'
                }`}
              >
                {preset.name.split(' (')[0]}
              </button>
            );
          })}
        </div>
      </div>

      {/* Campaign Details inputs: Subject & Company & Discount */}
      <div className="p-4 bg-slate-850 border-b border-slate-800 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div className="md:col-span-2">
          <label className="block text-[11px] font-semibold text-slate-400 mb-1 flex items-center justify-between">
            <span>ईमेल विषय (Email Subject Line)</span>
            <span className="text-slate-400 text-[10px]">Supports {'{{name}}'}, {'{{company}}'}, {'{{city}}'}, etc.</span>
          </label>
          <input
            type="text"
            value={campaign.subject}
            onChange={(e) => onUpdateCampaign({ subject: e.target.value })}
            placeholder="e.g. Happy Diwali from {{company}} to {{name}}! 🪔"
            className="w-full px-3 py-2 text-xs bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-amber-500"
          />
        </div>

        <div>
          <label className="block text-[11px] font-semibold text-slate-400 mb-1">
            डिफ़ॉल्ट डिस्काउंट कोड (Fallback Promo Code)
          </label>
          <input
            type="text"
            value={campaign.discountCode}
            onChange={(e) => onUpdateCampaign({ discountCode: e.target.value })}
            placeholder="e.g. FESTIVE50"
            className="w-full px-3 py-2 text-xs bg-slate-900 border border-slate-750 rounded-lg text-amber-300 font-mono focus:outline-none focus:border-amber-500 font-semibold"
          />
        </div>
      </div>

      {/* Variables Tag Bar (Built-in + Auto-Generated Excel Variables + Add Variable) */}
      <div className="px-4 py-2.5 bg-slate-900 border-b border-slate-800 flex items-center gap-2 overflow-x-auto text-xs">
        <span className="text-slate-400 text-[11px] font-semibold flex items-center gap-1 shrink-0">
          <Tag className="w-3 h-3 text-sky-400" /> Insert Variable:
        </span>

        {/* Standard built-in variables */}
        {[
          { tag: '{{name}}', label: '{{name}} (Name)', color: 'text-amber-400 hover:bg-amber-400/10' },
          { tag: '{{company}}', label: '{{company}} (Company)', color: 'text-sky-400 hover:bg-sky-400/10' },
          { tag: '{{discount}}', label: '{{discount}} (Offer)', color: 'text-pink-400 hover:bg-pink-400/10' },
          { tag: '{{festival}}', label: '{{festival}} (Occasion)', color: 'text-emerald-400 hover:bg-emerald-400/10' },
          { tag: '{{email}}', label: '{{email}} (Recipient)', color: 'text-purple-400 hover:bg-purple-400/10' },
        ].map((item) => (
          <button
            key={item.tag}
            type="button"
            onClick={() => handleInsertTag(item.tag)}
            className={`px-2 py-0.5 rounded text-[11px] font-mono border border-slate-750 bg-slate-800/80 transition shrink-0 ${item.color}`}
            title={`Insert ${item.tag} into template`}
          >
            + {item.label}
          </button>
        ))}

        {/* AUTO-GENERATED EXCEL VARIABLES SECTION */}
        {customVariableKeys.length > 0 && (
          <div className="flex items-center gap-1.5 pl-2 border-l border-slate-750 shrink-0">
            <span className="text-emerald-400 font-bold text-[11px] flex items-center gap-1 shrink-0 bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-500/40">
              <Sparkles className="w-3 h-3 text-emerald-400" />
              एक्सेल से स्वतः जनरेटेड:
            </span>
            {customVariableKeys.map((key) => (
              <button
                key={key}
                type="button"
                onClick={() => handleInsertTag(`{{${key}}}`)}
                className="px-2 py-0.5 rounded text-[11px] font-mono border border-emerald-500/50 bg-emerald-950/40 text-emerald-300 hover:bg-emerald-900/60 transition shrink-0 flex items-center gap-1"
                title={`Insert {{${key}}} from uploaded Excel data`}
              >
                <span>+ {'{{' + key + '}}'}</span>
              </button>
            ))}
            <button
              type="button"
              onClick={handleInsertAllExcelVariables}
              className="px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition shrink-0 flex items-center gap-1"
              title="Insert all auto-generated Excel variables block into template"
            >
              <span>+ सभी जोड़ें (Insert All Block)</span>
            </button>
          </div>
        )}

        {/* Button to Add / Setup New Custom Variable */}
        <button
          type="button"
          onClick={() => setShowVarSetupModal(true)}
          className="px-2.5 py-0.5 rounded text-[11px] font-semibold border border-dashed border-amber-500/50 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 transition shrink-0 flex items-center gap-1 ml-auto lg:ml-0"
          title="Setup a new custom mail variable (e.g. {{city}}, {{gift}}, {{order_id}})"
        >
          <Plus className="w-3 h-3" />
          <span>नया टैग जोड़ें (Add Custom Tag)</span>
        </button>

        {/* View Switchers */}
        <div className="ml-auto flex items-center gap-1 shrink-0">
          <div className="bg-slate-800 p-0.5 rounded-lg border border-slate-700 flex items-center">
            <button
              onClick={() => setActiveTab('editor')}
              className={`px-2.5 py-1 text-xs rounded-md font-medium transition ${
                activeTab === 'editor'
                  ? 'bg-slate-700 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              <Code className="w-3.5 h-3.5 inline mr-1" />
              Code
            </button>
            <button
              onClick={() => setActiveTab('preview')}
              className={`px-2.5 py-1 text-xs rounded-md font-medium transition ${
                activeTab === 'preview'
                  ? 'bg-slate-700 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              <Eye className="w-3.5 h-3.5 inline mr-1" />
              Preview
            </button>
            <button
              onClick={() => setActiveTab('split')}
              className={`hidden lg:flex items-center px-2.5 py-1 text-xs rounded-md font-medium transition ${
                activeTab === 'split'
                  ? 'bg-slate-700 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              <Split className="w-3.5 h-3.5 inline mr-1" />
              Split
            </button>
          </div>
        </div>
      </div>

      {/* Main Workspace: Code Editor & Live Preview */}
      <div className="flex-1 grid grid-cols-1 lg:grid-cols-2 min-h-[520px] divide-y lg:divide-y-0 lg:divide-x divide-slate-800">
        
        {/* Code Editor Panel */}
        {(activeTab === 'editor' || activeTab === 'split') && (
          <div className={`flex flex-col h-full bg-slate-950 ${activeTab === 'editor' ? 'lg:col-span-2' : ''}`}>
            <div className="px-4 py-2 bg-slate-900/80 border-b border-slate-850 flex items-center justify-between text-xs text-slate-400">
              <span className="font-mono text-[11px] text-slate-400">HTML Source Code</span>
              <span className="text-[10px] text-slate-500">Auto-saves instantly</span>
            </div>

            <textarea
              ref={textareaRef}
              value={campaign.htmlTemplate}
              onChange={(e) => onUpdateCampaign({ htmlTemplate: e.target.value })}
              placeholder="Paste your HTML email template here..."
              spellCheck={false}
              className="flex-1 w-full p-4 font-mono text-xs bg-slate-950 text-slate-200 resize-none focus:outline-none leading-relaxed selection:bg-amber-500/30 overflow-auto"
            />
          </div>
        )}

        {/* Live Preview Panel */}
        {(activeTab === 'preview' || activeTab === 'split') && (
          <div className={`flex flex-col h-full bg-slate-900/50 ${activeTab === 'preview' ? 'lg:col-span-2' : ''}`}>
            
            {/* Preview Controls Bar */}
            <div className="px-4 py-2 bg-slate-900 border-b border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs">
              <div className="flex items-center gap-2">
                <span className="text-slate-400 text-[11px]">Preview as:</span>
                <select
                  value={selectedRecipientId}
                  onChange={(e) => setSelectedRecipientId(e.target.value)}
                  className="bg-slate-800 text-xs text-white border border-slate-750 rounded px-2 py-1 focus:outline-none focus:border-amber-500"
                >
                  {campaign.recipients.map((rec) => (
                    <option key={rec.id} value={rec.id}>
                      {rec.name} ({rec.email})
                    </option>
                  ))}
                </select>
              </div>

              {/* Device switch */}
              <div className="flex items-center gap-1 bg-slate-800 p-0.5 rounded-lg border border-slate-750">
                <button
                  onClick={() => setPreviewDevice('desktop')}
                  className={`p-1 rounded ${previewDevice === 'desktop' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'}`}
                  title="Desktop View (600px)"
                >
                  <Monitor className="w-3.5 h-3.5" />
                </button>
                <button
                  onClick={() => setPreviewDevice('mobile')}
                  className={`p-1 rounded ${previewDevice === 'mobile' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'}`}
                  title="Mobile View (375px)"
                >
                  <Smartphone className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            {/* Email Client Simulated Header with Active Variables Display */}
            <div className="px-4 py-2.5 bg-slate-850/80 border-b border-slate-800 text-xs space-y-1.5">
              <div className="flex items-center gap-2 text-slate-300">
                <span className="text-slate-400 w-14 shrink-0 font-medium">Subject:</span>
                <span className="font-semibold text-white truncate">{personalizedSubject || 'No Subject'}</span>
              </div>
              <div className="flex items-center gap-2 text-slate-400 text-[11px]">
                <span className="w-14 shrink-0 font-medium">To:</span>
                <span className="text-slate-300">{currentRecipient.name} &lt;{currentRecipient.email}&gt;</span>
              </div>

              {/* Display replaced variables for current recipient */}
              {currentRecipient.customData && Object.keys(currentRecipient.customData).length > 0 && (
                <div className="pt-1 flex flex-wrap items-center gap-1 text-[10px]">
                  <span className="text-slate-400 font-medium">Active Tags:</span>
                  {Object.entries(currentRecipient.customData).slice(0, 4).map(([k, v]) => (
                    <span key={k} className="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700 font-mono">
                      <span className="text-amber-400">{'{{' + k + '}}'}:</span> &quot;{v}&quot;
                    </span>
                  ))}
                </div>
              )}
            </div>

            {/* Rendered HTML Iframe Container */}
            <div className="flex-1 p-4 overflow-auto flex items-center justify-center bg-slate-950/70">
              <div
                className={`bg-white rounded-xl overflow-hidden shadow-2xl transition-all duration-300 ${
                  previewDevice === 'desktop'
                    ? 'w-full max-w-[620px] h-[550px]'
                    : 'w-[375px] h-[580px] border-4 border-slate-800 rounded-3xl'
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
          <div className="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl text-slate-200">
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
              <div className="flex items-center gap-2">
                <Sliders className="w-5 h-5 text-amber-400" />
                <h3 className="text-sm font-bold text-white">नया मेल वेरिएबल जोड़ें (Setup Custom Variable)</h3>
              </div>
              <button
                onClick={() => setShowVarSetupModal(false)}
                className="text-slate-400 hover:text-white p-1"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <p className="text-xs text-slate-400 mb-4">
              अगर आप हर यूजर के लिए कोई कस्टम डेटा (जैसे <code>city</code>, <code>gift</code>, <code>phone</code>, <code>order_id</code>) भेजना चाहते हैं, तो यहाँ नया वेरिएबल बना सकते हैं:
            </p>

            <form onSubmit={handleCreateCustomVariable} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1">
                  वेरिएबल का नाम (Tag Name) <span className="text-rose-400">*</span>
                </label>
                <div className="relative">
                  <span className="absolute left-3 top-2 text-amber-400 font-mono text-xs">{'{{'}</span>
                  <input
                    type="text"
                    value={newVarName}
                    onChange={(e) => setNewVarName(e.target.value.toLowerCase().replace(/[^\w-]/g, '_'))}
                    placeholder="city या gift_hamper या order_id"
                    required
                    className="w-full pl-8 pr-8 py-2 text-xs bg-slate-950 border border-slate-750 rounded-lg text-amber-300 font-mono focus:outline-none focus:border-amber-500 font-semibold"
                  />
                  <span className="absolute right-3 top-2 text-amber-400 font-mono text-xs">{'}}'}</span>
                </div>
                <p className="text-[10px] text-slate-500 mt-1">
                  ईमेल में जहाँ भी <code>{'{{' + (newVarName || 'variable') + '}}'}</code> लिखा होगा, वहाँ हर यूजर की वैल्यू आ जाएगी।
                </p>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-300 mb-1">
                  डिफ़ॉल्ट वैल्यू (Default Value for all recipients)
                </label>
                <input
                  type="text"
                  value={newVarDefaultVal}
                  onChange={(e) => setNewVarDefaultVal(e.target.value)}
                  placeholder="e.g. Royal Sweets Box या Mumbai या Valued Customer"
                  className="w-full px-3 py-2 text-xs bg-slate-950 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-amber-500"
                />
                <p className="text-[10px] text-slate-500 mt-1">
                  जिन प्राप्तकर्ताओं का यह मान खाली होगा, उन्हें यह डिफ़ॉल्ट वैल्यू भेजी जाएगी।
                </p>
              </div>

              <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowVarSetupModal(false)}
                  className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  <span>वेरिएबल सेव करें व टेम्पलेट में डालें</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
};
