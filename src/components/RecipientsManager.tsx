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
  ExternalLink,
  ClipboardPaste,
  Database,
} from 'lucide-react';
import { Recipient, CampaignState } from '../types';
import { parseRecipientsInput, exportToCsv } from '../utils/campaignHelper';
import { INITIAL_SAMPLE_RECIPIENTS } from '../data/sampleRecipients';
import { ExcelUploadModal } from './ExcelUploadModal';
import { ActionMenu } from './ActionMenu';
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
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-brand-50 text-brand-700 border border-brand-200">
            <CheckCircle2 className="w-3 h-3" /> Delivered
          </span>
        );
      case 'sending':
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 animate-pulse">
            <RefreshCw className="w-3 h-3 animate-spin" /> Dispatching...
          </span>
        );
      case 'failed':
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
            <AlertCircle className="w-3 h-3" /> Failed
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
            <Clock className="w-3 h-3 text-slate-400" /> Pending (Queued)
          </span>
        );
    }
  };

  return (
    <div className="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col">

      {/* Top Bar */}
      <div className="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
              <Users className="w-5 h-5" />
            </div>
          <div>
          <h2 className="text-base font-semibold tracking-tight text-slate-900">
            Campaign Recipients
          </h2>
          <p className="text-sm text-slate-500">
            {campaign.recipients.length} recipients queued for staggered 5-minute dispatch
          </p>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={() => setShowAddModal(true)}
            className="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 text-xs font-medium rounded-lg shadow-xs transition"
          >
            <UserPlus className="w-3.5 h-3.5 text-slate-500" />
            <span>Add recipient</span>
          </button>

          <button
            onClick={() => setShowExcelModal(true)}
            className="inline-flex items-center gap-1.5 h-9 px-3.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition shadow-button"
            title="Upload Excel (.xlsx, .xls) or CSV — every column becomes a mail variable"
          >
            <FileSpreadsheet className="w-4 h-4" />
            <span>Upload Excel</span>
          </button>

          <ActionMenu
            items={[
              { label: 'Paste as text', icon: ClipboardPaste, onClick: () => setShowBulkModal(true) },
              { label: 'Download Excel format', icon: Download, onClick: downloadSampleExcelTemplate },
              { label: 'Load sample data', icon: Database, onClick: handleLoadSample },
              { label: 'Export as CSV', icon: Download, onClick: handleExportCsv, hidden: campaign.recipients.length === 0, separated: true },
              { label: 'Clear all recipients', icon: Trash2, onClick: handleClearAll, danger: true, hidden: campaign.recipients.length === 0, separated: true },
            ]}
          />
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="px-5 sm:px-6 py-3 bg-surface border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div className="relative flex-1 min-w-[200px] max-w-sm">
          <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Search by name, email, or variable..."
            className="w-full h-9 pl-9 pr-3 bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-sm"
          />
        </div>

        <div className="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
          {(['all', 'pending', 'delivered', 'failed'] as const).map((st) => {
            const count = st === 'all' ? campaign.recipients.length : campaign.recipients.filter((r) => r.status === st).length;
            return (
              <button
                key={st}
                onClick={() => setStatusFilter(st)}
                className={`inline-flex items-center gap-1.5 h-7 px-3 rounded-md text-xs font-medium capitalize transition ${
                  statusFilter === st ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                }`}
              >
                {st}
                <span className={`min-w-[18px] px-1 rounded text-[10px] font-semibold tabular-nums ${statusFilter === st ? 'bg-slate-100 text-slate-700' : 'bg-slate-200/70 text-slate-500'}`}>
                  {count}
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Table Container */}
      <div className="overflow-x-auto max-h-[480px]">
        <table className="w-full text-left border-collapse text-xs">
          <thead>
            <tr className="bg-slate-50 text-slate-500 sticky top-0 z-10 text-[11px] uppercase tracking-wider font-semibold border-b border-slate-200/80">
              <th className="py-3 px-5 whitespace-nowrap">#</th>
              <th className="py-3 px-5 whitespace-nowrap">Name</th>
              <th className="py-3 px-5 whitespace-nowrap">Email</th>
              <th className="py-3 px-5 whitespace-nowrap">Custom Variables</th>
              <th className="py-3 px-5 whitespace-nowrap">Delivery Status</th>
              <th className="py-3 px-5 whitespace-nowrap">Dispatched Time</th>
              <th className="py-3 px-5 whitespace-nowrap text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 bg-surface">
            {filteredRecipients.length === 0 ? (
              <tr>
                <td colSpan={7} className="py-14 text-center text-slate-500">
                  <div className="flex flex-col items-center justify-center gap-3">
                    <div className="w-12 h-12 rounded-2xl bg-slate-50 ring-1 ring-slate-200 flex items-center justify-center"><FileSpreadsheet className="w-5 h-5 text-slate-400" /></div>
                    <div>
                      <p className="text-sm font-semibold text-slate-700">
                        {campaign.recipients.length === 0
                          ? 'No recipients yet'
                          : 'No recipients match this filter.'}
                      </p>
                      <p className="text-xs text-slate-500 mt-1">
                        Upload an Excel file and every column will become a variable instantly
                      </p>
                    </div>
                    {campaign.recipients.length === 0 && (
                      <div className="flex items-center gap-2 mt-1">
                        <button
                          onClick={() => setShowExcelModal(true)}
                          className="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold rounded-lg transition flex items-center gap-2 shadow-button"
                        >
                          <FileSpreadsheet className="w-4 h-4" />
                          <span>Upload Excel Spreadsheet</span>
                        </button>
                        <button
                          onClick={handleLoadSample}
                          className="px-3.5 py-2 bg-surface hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-300 hover:border-slate-400 shadow-xs transition"
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
                    className="hover:bg-slate-50 transition group"
                  >
                    <td className="py-3 px-5 text-slate-400 font-mono text-[11px] tabular-nums">
                      {idx + 1}
                    </td>
                    <td className="py-3 px-5">
                      <div className="flex items-center gap-2.5">
                        <span className="w-7 h-7 shrink-0 rounded-full bg-brand-50 ring-1 ring-inset ring-brand-100 text-brand-700 text-[11px] font-semibold flex items-center justify-center">
                          {(recipient.name || recipient.email || '?').trim().split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase()}
                        </span>
                        <span className="font-semibold text-slate-900 whitespace-nowrap">{recipient.name}</span>
                      </div>
                    </td>
                    <td className="py-3 px-5 font-mono text-slate-600">
                      {recipient.email}
                    </td>
                    <td className="py-3 px-5">
                      {customEntries.length > 0 ? (
                        <div className="flex flex-wrap gap-1 max-w-xs">
                          {customEntries.slice(0, 3).map(([k, v]) => (
                            <span
                              key={k}
                              className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-100 text-[10.5px] text-slate-600 font-mono"
                              title={`{{${k}}}: ${v}`}
                            >
                              <span className="text-slate-400">{k}:</span>
                              <span className="text-slate-700 max-w-[80px] truncate">{v}</span>
                            </span>
                          ))}
                          {customEntries.length > 3 && (
                            <span className="text-[10px] text-slate-500 self-center">
                              +{customEntries.length - 3} more
                            </span>
                          )}
                        </div>
                      ) : (
                        <span className="text-slate-300">—</span>
                      )}
                    </td>
                    <td className="py-3 px-5">
                      {getStatusBadge(recipient.status)}
                    </td>
                    <td className="py-3 px-5 text-slate-500 text-[11px] font-mono tabular-nums">
                      {recipient.sentAt ? new Date(recipient.sentAt).toLocaleTimeString() : '—'}
                    </td>
                    <td className="py-3 px-5 text-right">
                      <div className="flex items-center justify-end gap-0.5 opacity-60 group-hover:opacity-100 transition">
                        <button
                          onClick={() => openEditModal(recipient)}
                          className="p-1.5 text-slate-500 hover:text-brand-800 hover:bg-brand-50 rounded-md transition"
                          title="Edit recipient & custom variables"
                        >
                          <Edit3 className="w-3.5 h-3.5" />
                        </button>
                        {recipient.status === 'failed' && (
                          <button
                            onClick={() => onRetryRecipient(recipient.id)}
                            className="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md transition"
                            title="Retry sending"
                          >
                            <RotateCw className="w-3.5 h-3.5" />
                          </button>
                        )}
                        <button
                          onClick={() => handleRemoveSingle(recipient.id)}
                          className="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-md transition"
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in">
          <div className="w-full max-w-md bg-surface border border-slate-200 rounded-2xl p-5 shadow-modal text-slate-700">
            <h3 className="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
              <UserPlus className="w-4 h-4 text-brand-700" />
              Add Recipient
            </h3>
            <form onSubmit={handleAddSingle} className="space-y-3">
              <div>
                <label className="block text-xs text-slate-500 mb-1">
                  Name <span className="text-[10px] text-slate-400">(tag: {'{{name}}'})</span>
                </label>
                <input
                  type="text"
                  value={singleName}
                  onChange={(e) => setSingleName(e.target.value)}
                  placeholder="e.g. Aarav Sharma"
                  className="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
                />
              </div>

              <div>
                <label className="block text-xs text-slate-500 mb-1">
                  Email Address <span className="text-red-600 font-bold">*</span>
                </label>
                <input
                  type="email"
                  value={singleEmail}
                  onChange={(e) => setSingleEmail(e.target.value)}
                  placeholder="e.g. aarav@company.com"
                  required
                  className="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
                />
              </div>

              {/* Custom Variables Section */}
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                    <Tag className="w-3 h-3" />
                    Custom Variables
                  </span>
                  <button
                    type="button"
                    onClick={() => setSingleCustomVars([...singleCustomVars, { key: '', value: '' }])}
                    className="text-[11px] text-brand-700 hover:text-brand-800 flex items-center gap-0.5"
                  >
                    <Plus className="w-3 h-3" /> Add Variable
                  </button>
                </div>
                <p className="text-[10px] text-slate-500 mb-2">
                  Example: Key <code>city</code>, Value <code>Mumbai</code> → use <code>{'{{city}}'}</code> in the email
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
                        className="w-1/2 px-2.5 py-1 text-xs bg-surface border border-slate-300 rounded text-amber-700 font-mono placeholder:text-slate-400 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
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
                        className="w-1/2 px-2.5 py-1 text-xs bg-surface border border-slate-300 rounded text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
                      />
                      {singleCustomVars.length > 1 && (
                        <button
                          type="button"
                          onClick={() => setSingleCustomVars(singleCustomVars.filter((_, idx) => idx !== i))}
                          className="text-slate-400 hover:text-red-600 p-1"
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
                  className="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-medium text-xs rounded-lg transition shadow-button"
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in">
          <div className="w-full max-w-md bg-surface border border-slate-200 rounded-2xl p-5 shadow-modal text-slate-700">
            <h3 className="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
              <Edit3 className="w-4 h-4 text-brand-700" />
              Edit Recipient
            </h3>
            <form onSubmit={handleSaveEdit} className="space-y-3">
              <div>
                <label className="block text-xs text-slate-500 mb-1">Name</label>
                <input
                  type="text"
                  value={editName}
                  onChange={(e) => setEditName(e.target.value)}
                  className="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
                />
              </div>

              <div>
                <label className="block text-xs text-slate-500 mb-1">Email Address</label>
                <input
                  type="email"
                  value={editEmail}
                  onChange={(e) => setEditEmail(e.target.value)}
                  required
                  className="w-full px-3 py-1.5 text-xs bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 font-mono"
                />
              </div>

              {/* Custom Variables Section */}
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                    <Tag className="w-3 h-3" />
                    Custom Variables for this Recipient
                  </span>
                  <button
                    type="button"
                    onClick={() => setEditCustomVars([...editCustomVars, { key: '', value: '' }])}
                    className="text-[11px] text-brand-700 hover:text-brand-800 flex items-center gap-0.5"
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
                        className="w-1/2 px-2.5 py-1 text-xs bg-surface border border-slate-300 rounded text-amber-700 font-mono placeholder:text-slate-400 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
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
                        className="w-1/2 px-2.5 py-1 text-xs bg-surface border border-slate-300 rounded text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
                      />
                      <button
                        type="button"
                        onClick={() => setEditCustomVars(editCustomVars.filter((_, idx) => idx !== i))}
                        className="text-slate-400 hover:text-red-600 p-1"
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
                  className="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-medium text-xs rounded-lg transition shadow-button"
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
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in">
          <div className="w-full max-w-lg bg-surface border border-slate-200 rounded-2xl p-5 shadow-modal text-slate-700">
            <h3 className="text-sm font-semibold text-slate-900 mb-1">Bulk Import Recipients (Paste Text)</h3>
            <p className="text-xs text-slate-500 mb-3">
              Paste rows with &quot;Name, Email&quot; or list of emails (one per line):
            </p>
            <form onSubmit={handleAddBulk} className="space-y-3">
              <textarea
                value={bulkInput}
                onChange={(e) => setBulkInput(e.target.value)}
                placeholder={`Aarav Sharma, aarav@example.com\nPriya Patel, priya@acme.com\nrohit@company.org`}
                rows={6}
                required
                className="w-full p-3 font-mono text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-800 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10"
              />
              <div className="pt-2 flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowBulkModal(false)}
                  className="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-900"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-white font-medium text-xs rounded-lg transition shadow-button"
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
