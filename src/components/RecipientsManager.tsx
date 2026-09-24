import React, { useState, useMemo } from 'react';
import {
  Users,
  UserPlus,
  FileSpreadsheet,
  Trash2,
  Search,
  CheckCircle2,
  Clock,
  AlertCircle,
  Download,
  RotateCw,
  RefreshCw,
  Edit3,
  Plus,
  Tag,
  HelpCircle,
  Sparkles,
  Copy,
  Check,
  ExternalLink
} from 'lucide-react';
import { Recipient, CampaignState } from '../types';
import { parseRecipientsInput, exportToCsv } from '../utils/campaignHelper';
import { INITIAL_SAMPLE_RECIPIENTS } from '../data/sampleRecipients';
import { ExcelUploadModal } from './ExcelUploadModal';
import { downloadSampleExcelTemplate } from '../utils/excelHelper';

interface RecipientsManagerProps {
  campaign: CampaignState;
  onUpdateRecipients: (recipients: Recipient[]) => void;
  onRetryRecipient: (id: string) => void;
  onImportExcelData: (
    recipients: Recipient[],
    replace: boolean,
    discoveredVariables: string[],
    autoInsertBlock?: boolean
  ) => void;
}

export const RecipientsManager: React.FC<RecipientsManagerProps> = ({
  campaign,
  onUpdateRecipients,
  onRetryRecipient,
  onImportExcelData,
}) => {
  const [showAddModal, setShowAddModal] = useState(false);
  const [showBulkModal, setShowBulkModal] = useState(false);
  const [showExcelModal, setShowExcelModal] = useState(false);
  const [editingRecipient, setEditingRecipient] = useState<Recipient | null>(null);
  const [copiedVariable, setCopiedVariable] = useState<string | null>(null);

  // Single Add Form State
  const [singleName, setSingleName] = useState('');
  const [singleEmail, setSingleEmail] = useState('');
  const [singleCustomVars, setSingleCustomVars] = useState<Array<{ key: string; value: string }>>([
    { key: '', value: '' },
  ]);

  // Edit Form State
  const [editName, setEditName] = useState('');
  const [editEmail, setEditEmail] = useState('');
  const [editCustomVars, setEditCustomVars] = useState<Array<{ key: string; value: string }>>([]);

  const [bulkInput, setBulkInput] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'delivered' | 'failed'>('all');

  // Compute all available custom variable keys
  const allActiveVariables = useMemo(() => {
    const set = new Set<string>(campaign.customVariables || []);
    campaign.recipients.forEach((r) => {
      if (r.customData) {
        Object.keys(r.customData).forEach((k) => {
          const lower = k.toLowerCase().trim();
          if (lower !== 'name' && lower !== 'email') {
            set.add(k.trim());
          }
        });
      }
    });
    return Array.from(set);
  }, [campaign.customVariables, campaign.recipients]);

  const filteredRecipients = campaign.recipients.filter((r) => {
    const matchesSearch =
      r.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      r.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (r.customData && Object.values(r.customData).some((val) => val.toLowerCase().includes(searchQuery.toLowerCase())));
    const matchesStatus = statusFilter === 'all' || r.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const handleAddSingle = (e: React.FormEvent) => {
    e.preventDefault();
    if (!singleEmail || !singleEmail.includes('@')) return;

    const customData: Record<string, string> = {};
    singleCustomVars.forEach(({ key, value }) => {
      const cleanKey = key.trim().toLowerCase();
      if (cleanKey && value.trim()) {
        customData[cleanKey] = value.trim();
        customData[key.trim()] = value.trim();
      }
    });

    const newRec: Recipient = {
      id: `rec-${Date.now()}-${Math.random().toString(36).substring(2, 6)}`,
      name: singleName.trim() || 'Valued Customer',
      email: singleEmail.trim(),
      customData: Object.keys(customData).length > 0 ? customData : undefined,
      status: 'pending',
    };

    onUpdateRecipients([...campaign.recipients, newRec]);
    setSingleName('');
    setSingleEmail('');
    setSingleCustomVars([{ key: '', value: '' }]);
    setShowAddModal(false);
  };

  const openEditModal = (rec: Recipient) => {
    setEditingRecipient(rec);
    setEditName(rec.name);
    setEditEmail(rec.email);
    const vars: Array<{ key: string; value: string }> = [];
    if (rec.customData) {
      const seen = new Set<string>();
      Object.entries(rec.customData).forEach(([k, v]) => {
        const lower = k.toLowerCase();
        if (!seen.has(lower)) {
          seen.add(lower);
          vars.push({ key: k, value: v });
        }
      });
    }
    if (vars.length === 0) {
      vars.push({ key: '', value: '' });
    }
    setEditCustomVars(vars);
  };

  const handleSaveEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingRecipient || !editEmail.includes('@')) return;

    const customData: Record<string, string> = {};
    editCustomVars.forEach(({ key, value }) => {
      const cleanKey = key.trim().toLowerCase();
      if (cleanKey && value.trim()) {
        customData[cleanKey] = value.trim();
        customData[key.trim()] = value.trim();
      }
    });

    const updated = campaign.recipients.map((r) =>
      r.id === editingRecipient.id
        ? {
            ...r,
            name: editName.trim() || 'Valued Customer',
            email: editEmail.trim(),
            customData: Object.keys(customData).length > 0 ? customData : undefined,
          }
        : r
    );

    onUpdateRecipients(updated);
    setEditingRecipient(null);
  };

  const handleAddBulk = (e: React.FormEvent) => {
    e.preventDefault();
    const parsed = parseRecipientsInput(bulkInput);
    if (parsed.length > 0) {
      onUpdateRecipients([...campaign.recipients, ...parsed]);
      setBulkInput('');
      setShowBulkModal(false);
    }
  };

  const handleImportExcelRecipients = (
    imported: Recipient[],
    replace: boolean,
    discoveredVariables: string[],
    autoInsertBlock?: boolean
  ) => {
    onImportExcelData(imported, replace, discoveredVariables, autoInsertBlock);
  };

  const handleCopyTag = (tag: string) => {
    navigator.clipboard.writeText(`{{${tag}}}`);
    setCopiedVariable(tag);
    setTimeout(() => setCopiedVariable(null), 2000);
  };

  const handleLoadSample = () => {
    if (campaign.recipients.length > 0) {
      if (!window.confirm('Replace current recipients list with sample festival contacts?')) return;
    }
    onUpdateRecipients(INITIAL_SAMPLE_RECIPIENTS);
  };

  const handleClearAll = () => {
    if (window.confirm('Are you sure you want to clear all recipients from this campaign?')) {
      onUpdateRecipients([]);
    }
  };

  const handleRemoveSingle = (id: string) => {
    onUpdateRecipients(campaign.recipients.filter((r) => r.id !== id));
  };

  const handleExportCsv = () => {
    const rows = [
      ['ID', 'Name', 'Email', 'Status', 'Custom Variables', 'Sent At', 'Message ID', 'Error'],
      ...campaign.recipients.map((r) => [
        r.id,
        r.name,
        r.email,
        r.status,
        r.customData ? JSON.stringify(r.customData) : '',
        r.sentAt || '',
        r.messageId || '',
        r.errorMessage || '',
      ]),
    ];
    exportToCsv(`campaign-recipients-${Date.now()}.csv`, rows);
  };

  const getStatusBadge = (status: Recipient['status']) => {
    switch (status) {
      case 'delivered':
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
            <CheckCircle2 className="w-3 h-3" /> Delivered
          </span>
        );
      case 'sending':
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-500/15 text-sky-400 border border-sky-500/30 animate-pulse">
            <RefreshCw className="w-3 h-3 animate-spin" /> Dispatching...
          </span>
        );
      case 'failed':
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/30">
            <AlertCircle className="w-3 h-3" /> Failed
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-800 text-slate-400 border border-slate-700">
            <Clock className="w-3 h-3 text-slate-500" /> Pending (Queued)
          </span>
        );
    }
  };

  return (
    <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl flex flex-col">
      
      {/* Top Bar */}
      <div className="p-4 border-b border-slate-800 bg-slate-900/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
          <h2 className="text-base font-bold text-white flex items-center gap-2">
            <Users className="w-4 h-4 text-sky-400" />
            प्राप्तकर्ता सूची (Multiple Campaign Recipients)
          </h2>
          <p className="text-xs text-slate-400">
            {campaign.recipients.length} recipients queued for staggered 5-minute dispatch
          </p>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-2">
          {/* Main Excel Upload Button */}
          <button
            onClick={() => setShowExcelModal(true)}
            className="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-950/40 transition"
            title="Upload Excel (.xlsx, .xls) or CSV — auto-generates all mail variables immediately"
          >
            <FileSpreadsheet className="w-4 h-4" />
            <span>Upload Excel (.xlsx / .csv)</span>
          </button>

          <button
            onClick={() => setShowAddModal(true)}
            className="flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold rounded-xl shadow transition"
          >
            <UserPlus className="w-3.5 h-3.5" />
            <span>Add Single</span>
          </button>

          <button
            onClick={() => setShowBulkModal(true)}
            className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-750 text-xs font-medium rounded-xl transition"
          >
            <span>Paste Text</span>
          </button>

          <button
            onClick={downloadSampleExcelTemplate}
            className="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-750 text-emerald-400 hover:text-emerald-300 border border-slate-750 text-xs font-medium rounded-xl transition flex items-center gap-1"
            title="Download blank sample Excel file"
          >
            <Download className="w-3 h-3" />
            <span>Excel Format</span>
          </button>

          <button
            onClick={handleLoadSample}
            className="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-750 text-slate-400 hover:text-slate-200 border border-slate-750 text-xs font-medium rounded-xl transition"
            title="Load standard festival demo list"
          >
            Sample Data
          </button>

          {campaign.recipients.length > 0 && (
            <>
              <button
                onClick={handleExportCsv}
                className="p-1.5 bg-slate-800 hover:bg-slate-750 text-slate-400 hover:text-slate-200 border border-slate-750 rounded-xl transition"
                title="Export Recipients CSV"
              >
                <Download className="w-4 h-4" />
              </button>
              <button
                onClick={handleClearAll}
                className="p-1.5 bg-slate-800 hover:bg-rose-950 text-slate-400 hover:text-rose-400 border border-slate-750 rounded-xl transition"
                title="Clear All Recipients"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </>
          )}
        </div>
      </div>

      {/* AUTO-GENERATED VARIABLES BANNER */}
      {allActiveVariables.length > 0 && (
        <div className="px-4 py-2.5 bg-emerald-950/40 border-b border-emerald-500/30 flex flex-wrap items-center justify-between gap-2 text-xs">
          <div className="flex flex-wrap items-center gap-1.5">
            <span className="text-emerald-300 font-bold flex items-center gap-1">
              <Sparkles className="w-3.5 h-3.5 text-emerald-400" />
              एक्सेल से स्वतः जनरेट हुए मेल वेरिएबल्स ({allActiveVariables.length} Auto-Generated):
            </span>
            {allActiveVariables.map((v) => (
              <button
                key={v}
                type="button"
                onClick={() => handleCopyTag(v)}
                className="px-2 py-0.5 rounded bg-slate-900 hover:bg-slate-800 text-emerald-300 border border-emerald-500/40 font-mono text-[11px] font-semibold transition flex items-center gap-1"
                title="Click to copy tag"
              >
                <span>{'{{' + v + '}}'}</span>
                {copiedVariable === v ? (
                  <Check className="w-2.5 h-2.5 text-emerald-400" />
                ) : (
                  <Copy className="w-2.5 h-2.5 text-slate-500" />
                )}
              </button>
            ))}
          </div>
          <span className="text-[11px] text-slate-400">
            टैग पर क्लिक करके कॉपी करें या ईमेल टेम्पलेट में सीधे लिखें
          </span>
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="px-4 py-2.5 bg-slate-850 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div className="relative flex-1 min-w-[200px] max-w-sm">
          <Search className="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search by name, email, or variable..."
            className="w-full pl-8 pr-3 py-1.5 bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-sky-500 text-xs"
          />
        </div>

        <div className="flex items-center gap-1.5">
          <span className="text-slate-400 text-[11px]">Filter:</span>
          {(['all', 'pending', 'delivered', 'failed'] as const).map((st) => (
            <button
              key={st}
              onClick={() => setStatusFilter(st)}
              className={`px-2.5 py-1 rounded-md text-[11px] font-medium capitalize transition ${
                statusFilter === st
                  ? 'bg-sky-500/20 text-sky-300 border border-sky-500/40'
                  : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              {st}
            </button>
          ))}
        </div>
      </div>

      {/* Table Container */}
      <div className="overflow-x-auto max-h-[380px] divide-y divide-slate-800">
        <table className="w-full text-left border-collapse text-xs">
          <thead>
            <tr className="bg-slate-900/90 text-slate-400 sticky top-0 z-10 text-[11px] uppercase tracking-wider font-semibold">
              <th className="py-2.5 px-4">#</th>
              <th className="py-2.5 px-4">Name</th>
              <th className="py-2.5 px-4">Email</th>
              <th className="py-2.5 px-4">Auto-Generated Variables (कस्टम डेटा)</th>
              <th className="py-2.5 px-4">Delivery Status</th>
              <th className="py-2.5 px-4">Dispatched Time</th>
              <th className="py-2.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-850 bg-slate-950/40">
            {filteredRecipients.length === 0 ? (
              <tr>
                <td colSpan={7} className="py-10 text-center text-slate-500">
                  <div className="flex flex-col items-center justify-center gap-3">
                    <FileSpreadsheet className="w-9 h-9 text-slate-600" />
                    <div>
                      <p className="text-sm font-semibold text-slate-300">
                        {campaign.recipients.length === 0
                          ? 'प्राप्तकर्ताओं की सूची खाली है (No recipients yet)'
                          : 'कोई प्राप्तकर्ता फ़िल्टर से मेल नहीं खाता।'}
                      </p>
                      <p className="text-xs text-slate-500 mt-1">
                        एक्सेल फाइल अपलोड करें — सभी कॉलम्स के वेरिएबल्स तुरंत जनरेट होंगे
                      </p>
                    </div>
                    {campaign.recipients.length === 0 && (
                      <div className="flex items-center gap-2 mt-1">
                        <button
                          onClick={() => setShowExcelModal(true)}
                          className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow transition flex items-center gap-2"
                        >
                          <FileSpreadsheet className="w-4 h-4" />
                          <span>Upload Excel Spreadsheet</span>
                        </button>
                        <button
                          onClick={handleLoadSample}
                          className="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-xl border border-slate-750 transition"
                        >
                          Load Sample Data
                        </button>
                      </div>
                    )}
                  </div>
                </td>
              </tr>
            ) : (
              filteredRecipients.map((recipient, idx) => {
                const customEntries = recipient.customData
                  ? Object.entries(recipient.customData).filter(
                      ([k]) => k === k.toLowerCase() || !recipient.customData![k.toLowerCase()]
                    )
                  : [];

                return (
                  <tr
                    key={recipient.id}
                    className="hover:bg-slate-850/60 transition group"
                  >
                    <td className="py-2.5 px-4 text-slate-400 font-mono text-[11px]">
                      {idx + 1}
                    </td>
                    <td className="py-2.5 px-4 font-semibold text-slate-200">
                      {recipient.name}
                    </td>
                    <td className="py-2.5 px-4 font-mono text-slate-300">
                      {recipient.email}
                    </td>
                    <td className="py-2.5 px-4">
                      {customEntries.length > 0 ? (
                        <div className="flex flex-wrap gap-1 max-w-xs">
                          {customEntries.slice(0, 3).map(([k, v]) => (
                            <span
                              key={k}
                              className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-800 border border-slate-750 text-[10px] text-slate-300 font-mono"
                              title={`{{${k}}}: ${v}`}
                            >
                              <span className="text-amber-400 font-semibold">{k}:</span>
                              <span className="text-slate-200 max-w-[80px] truncate">{v}</span>
                            </span>
                          ))}
                          {customEntries.length > 3 && (
                            <span className="text-[10px] text-slate-500 self-center">
                              +{customEntries.length - 3} more
                            </span>
                          )}
                        </div>
                      ) : (
                        <span className="text-slate-600 text-[11px]">None</span>
                      )}
                    </td>
                    <td className="py-2.5 px-4">
                      {getStatusBadge(recipient.status)}
                    </td>
                    <td className="py-2.5 px-4 text-slate-400 text-[11px] font-mono">
                      {recipient.sentAt ? new Date(recipient.sentAt).toLocaleTimeString() : '—'}
                    </td>
                    <td className="py-2.5 px-4 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <button
                          onClick={() => openEditModal(recipient)}
                          className="p-1 text-slate-400 hover:text-sky-300 hover:bg-sky-500/10 rounded transition"
                          title="Edit recipient & custom variables"
                        >
                          <Edit3 className="w-3.5 h-3.5" />
                        </button>
                        {recipient.status === 'failed' && (
                          <button
                            onClick={() => onRetryRecipient(recipient.id)}
                            className="p-1 text-amber-400 hover:bg-amber-500/10 rounded transition"
                            title="Retry sending"
                          >
                            <RotateCw className="w-3.5 h-3.5" />
                          </button>
                        )}
                        <button
                          onClick={() => handleRemoveSingle(recipient.id)}
                          className="p-1 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded transition"
                          title="Remove recipient"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Single Add Modal (With Custom Variables Support) */}
      {showAddModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
          <div className="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-2xl text-slate-200">
            <h3 className="text-sm font-bold text-white mb-3 flex items-center gap-2">
              <UserPlus className="w-4 h-4 text-sky-400" />
              प्राप्तकर्ता जोड़ें (Add Single Recipient)
            </h3>
            <form onSubmit={handleAddSingle} className="space-y-3">
              <div>
                <label className="block text-xs text-slate-400 mb-1">
                  Name (नाम) <span className="text-[10px] text-slate-500">(टैग: {'{{name}}'})</span>
                </label>
                <input
                  type="text"
                  value={singleName}
                  onChange={(e) => setSingleName(e.target.value)}
                  placeholder="e.g. Aarav Sharma"
                  className="w-full px-3 py-1.5 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-sky-500"
                />
              </div>

              <div>
                <label className="block text-xs text-slate-400 mb-1">
                  Email Address (ईमेल) <span className="text-rose-400 font-bold">*</span>
                </label>
                <input
                  type="email"
                  value={singleEmail}
                  onChange={(e) => setSingleEmail(e.target.value)}
                  placeholder="e.g. aarav@company.com"
                  required
                  className="w-full px-3 py-1.5 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-sky-500 font-mono"
                />
              </div>

              {/* Custom Variables Section */}
              <div className="p-3 bg-slate-950 rounded-xl border border-slate-800">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[11px] font-bold text-amber-400 flex items-center gap-1">
                    <Tag className="w-3 h-3" />
                    अतिरिक्त मेल वेरिएबल्स (Custom Mail Variables)
                  </span>
                  <button
                    type="button"
                    onClick={() => setSingleCustomVars([...singleCustomVars, { key: '', value: '' }])}
                    className="text-[11px] text-sky-400 hover:text-sky-300 flex items-center gap-0.5"
                  >
                    <Plus className="w-3 h-3" /> Add Variable
                  </button>
                </div>
                <p className="text-[10px] text-slate-500 mb-2">
                  उदाहरण: Key: <code>city</code>, Value: <code>Mumbai</code> → ईमेल में <code>{'{{city}}'}</code> बनेगा
                </p>

                <div className="space-y-2 max-h-36 overflow-y-auto">
                  {singleCustomVars.map((v, i) => (
                    <div key={i} className="flex items-center gap-2">
                      <input
                        type="text"
                        placeholder="Tag (e.g. city, gift)"
                        value={v.key}
                        onChange={(e) => {
                          const updated = [...singleCustomVars];
                          updated[i].key = e.target.value;
                          setSingleCustomVars(updated);
                        }}
                        className="w-1/2 px-2.5 py-1 text-xs bg-slate-900 border border-slate-750 rounded text-amber-300 font-mono focus:outline-none focus:border-amber-500"
                      />
                      <input
                        type="text"
                        placeholder="Value (e.g. Delhi)"
                        value={v.value}
                        onChange={(e) => {
                          const updated = [...singleCustomVars];
                          updated[i].value = e.target.value;
                          setSingleCustomVars(updated);
                        }}
                        className="w-1/2 px-2.5 py-1 text-xs bg-slate-900 border border-slate-750 rounded text-white focus:outline-none focus:border-sky-500"
                      />
                      {singleCustomVars.length > 1 && (
                        <button
                          type="button"
                          onClick={() => setSingleCustomVars(singleCustomVars.filter((_, idx) => idx !== i))}
                          className="text-slate-500 hover:text-rose-400 p-1"
                        >
                          <Trash2 className="w-3 h-3" />
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <div className="pt-2 flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-3 py-1.5 text-xs text-slate-400 hover:text-white"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white font-medium text-xs rounded-xl shadow transition"
                >
                  Add Recipient
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit Recipient Modal */}
      {editingRecipient && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
          <div className="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-2xl text-slate-200">
            <h3 className="text-sm font-bold text-white mb-3 flex items-center gap-2">
              <Edit3 className="w-4 h-4 text-sky-400" />
              प्राप्तकर्ता व वेरिएबल्स संपादित करें (Edit Recipient & Variables)
            </h3>
            <form onSubmit={handleSaveEdit} className="space-y-3">
              <div>
                <label className="block text-xs text-slate-400 mb-1">Name (नाम)</label>
                <input
                  type="text"
                  value={editName}
                  onChange={(e) => setEditName(e.target.value)}
                  className="w-full px-3 py-1.5 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-sky-500"
                />
              </div>

              <div>
                <label className="block text-xs text-slate-400 mb-1">Email Address (ईमेल)</label>
                <input
                  type="email"
                  value={editEmail}
                  onChange={(e) => setEditEmail(e.target.value)}
                  required
                  className="w-full px-3 py-1.5 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-sky-500 font-mono"
                />
              </div>

              {/* Custom Variables Section */}
              <div className="p-3 bg-slate-950 rounded-xl border border-slate-800">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[11px] font-bold text-amber-400 flex items-center gap-1">
                    <Tag className="w-3 h-3" />
                    इस यूजर के वेरिएबल्स (Custom Mail Variables)
                  </span>
                  <button
                    type="button"
                    onClick={() => setEditCustomVars([...editCustomVars, { key: '', value: '' }])}
                    className="text-[11px] text-sky-400 hover:text-sky-300 flex items-center gap-0.5"
                  >
                    <Plus className="w-3 h-3" /> Add Variable
                  </button>
                </div>

                <div className="space-y-2 max-h-36 overflow-y-auto">
                  {editCustomVars.map((v, i) => (
                    <div key={i} className="flex items-center gap-2">
                      <input
                        type="text"
                        placeholder="Tag (e.g. city, order_id)"
                        value={v.key}
                        onChange={(e) => {
                          const updated = [...editCustomVars];
                          updated[i].key = e.target.value;
                          setEditCustomVars(updated);
                        }}
                        className="w-1/2 px-2.5 py-1 text-xs bg-slate-900 border border-slate-750 rounded text-amber-300 font-mono focus:outline-none focus:border-amber-500"
                      />
                      <input
                        type="text"
                        placeholder="Value (e.g. Mumbai)"
                        value={v.value}
                        onChange={(e) => {
                          const updated = [...editCustomVars];
                          updated[i].value = e.target.value;
                          setEditCustomVars(updated);
                        }}
                        className="w-1/2 px-2.5 py-1 text-xs bg-slate-900 border border-slate-750 rounded text-white focus:outline-none focus:border-sky-500"
                      />
                      <button
                        type="button"
                        onClick={() => setEditCustomVars(editCustomVars.filter((_, idx) => idx !== i))}
                        className="text-slate-500 hover:text-rose-400 p-1"
                      >
                        <Trash2 className="w-3 h-3" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              <div className="pt-2 flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setEditingRecipient(null)}
                  className="px-3 py-1.5 text-xs text-slate-400 hover:text-white"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white font-medium text-xs rounded-xl shadow transition"
                >
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Bulk Paste Modal */}
      {showBulkModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
          <div className="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-2xl text-slate-200">
            <h3 className="text-sm font-bold text-white mb-1">Bulk Import Recipients (टेक्स्ट पेस्ट)</h3>
            <p className="text-xs text-slate-400 mb-3">
              Paste rows with &quot;Name, Email&quot; or list of emails (one per line):
            </p>
            <form onSubmit={handleAddBulk} className="space-y-3">
              <textarea
                value={bulkInput}
                onChange={(e) => setBulkInput(e.target.value)}
                placeholder={`Aarav Sharma, aarav@example.com\nPriya Patel, priya@acme.com\nrohit@company.org`}
                rows={6}
                required
                className="w-full p-3 font-mono text-xs bg-slate-950 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-sky-500"
              />
              <div className="pt-2 flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowBulkModal(false)}
                  className="px-3 py-1.5 text-xs text-slate-400 hover:text-white"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs rounded-xl shadow transition"
                >
                  Import Contacts
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Excel & CSV Upload Modal */}
      <ExcelUploadModal
        isOpen={showExcelModal}
        onClose={() => setShowExcelModal(false)}
        currentCount={campaign.recipients.length}
        onImportRecipients={handleImportExcelRecipients}
      />

    </div>
  );
};
