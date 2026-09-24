import React, { useState } from 'react';
import {
  Activity,
  Download,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Search,
  Send,
  Percent,
} from 'lucide-react';
import { DispatchLog } from '../types';
import { exportToCsv } from '../utils/campaignHelper';
import { ActionMenu } from './ActionMenu';

interface DeliveryLogsProps {
  logs: DispatchLog[];
  onClearLogs: () => void;
}

type LogFilter = 'all' | 'delivered' | 'failed';

export const DeliveryLogs: React.FC<DeliveryLogsProps> = ({ logs, onClearLogs }) => {
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState<LogFilter>('all');

  const deliveredCount = logs.filter((l) => l.status !== 'failed').length;
  const failedCount = logs.filter((l) => l.status === 'failed').length;
  const successRate = logs.length > 0 ? Math.round((deliveredCount / logs.length) * 100) : 0;

  const q = search.toLowerCase();
  const filteredLogs = logs.filter((log) => {
    const matchesFilter =
      filter === 'all' || (filter === 'failed' ? log.status === 'failed' : log.status !== 'failed');
    const matchesSearch =
      log.recipientEmail.toLowerCase().includes(q) ||
      log.recipientName.toLowerCase().includes(q) ||
      log.subject.toLowerCase().includes(q);
    return matchesFilter && matchesSearch;
  });

  const handleExport = () => {
    const rows = [
      ['Timestamp', 'Sender (From)', 'Recipient Name', 'Recipient Email', 'Status', 'Mode', 'Message ID', 'Subject', 'Detail'],
      ...logs.map((l) => [
        l.timestamp,
        l.senderEmail || '',
        l.recipientName,
        l.recipientEmail,
        l.status,
        l.mode,
        l.messageId || '',
        l.subject,
        l.detail || '',
      ]),
    ];
    exportToCsv(`festival-email-dispatch-logs-${Date.now()}.csv`, rows);
  };

  const stats = [
    { label: 'Total dispatched', value: logs.length, icon: Send, tone: 'text-slate-900' },
    { label: 'Delivered', value: deliveredCount, icon: CheckCircle2, tone: 'text-brand-700' },
    { label: 'Failed', value: failedCount, icon: AlertCircle, tone: failedCount > 0 ? 'text-red-600' : 'text-slate-900' },
    { label: 'Success rate', value: logs.length ? `${successRate}%` : '—', icon: Percent, tone: 'text-slate-900' },
  ];

  const filters: { id: LogFilter; label: string; count: number }[] = [
    { id: 'all', label: 'All', count: logs.length },
    { id: 'delivered', label: 'Delivered', count: deliveredCount },
    { id: 'failed', label: 'Failed', count: failedCount },
  ];

  return (
    <div className="bg-surface border border-slate-200/80 rounded-2xl overflow-hidden shadow-card flex flex-col">

      {/* Header */}
      <div className="px-5 sm:px-6 py-5 border-b border-slate-200/80 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-br from-brand-500 to-accent-600 text-white flex items-center justify-center shadow-button">
            <Activity className="w-5 h-5" />
          </div>
          <div>
            <h2 className="text-base font-semibold tracking-tight text-slate-900">Delivery Logs</h2>
            <p className="text-sm text-slate-500">Every dispatched email with its status, message ID and timestamp</p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={handleExport}
            disabled={logs.length === 0}
            className="inline-flex items-center gap-1.5 h-9 px-3 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 text-xs font-medium rounded-lg shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-surface"
            title="Download the logs as a CSV report"
          >
            <Download className="w-3.5 h-3.5 text-slate-500" />
            <span>Export CSV</span>
          </button>
          <ActionMenu
            items={[
              { label: 'Export as CSV', icon: Download, onClick: handleExport, hidden: logs.length === 0 },
              { label: 'Clear all logs', icon: Trash2, onClick: onClearLogs, danger: true, hidden: logs.length === 0, separated: true },
            ]}
          />
        </div>
      </div>

      {/* Summary */}
      <div className="px-5 sm:px-6 py-4 border-b border-slate-200/80 grid grid-cols-2 lg:grid-cols-4 gap-3">
        {stats.map((s) => {
          const Icon = s.icon;
          return (
            <div key={s.label} className="flex items-center gap-3 rounded-xl bg-slate-50 ring-1 ring-inset ring-slate-200/80 px-4 py-3">
              <div className="w-9 h-9 shrink-0 rounded-lg bg-surface ring-1 ring-slate-200 flex items-center justify-center">
                <Icon className="w-4 h-4 text-slate-400" />
              </div>
              <div className="min-w-0">
                <p className="text-[11px] font-medium text-slate-500 truncate">{s.label}</p>
                <p className={`text-lg font-bold tabular-nums leading-tight ${s.tone}`}>{s.value}</p>
              </div>
            </div>
          );
        })}
      </div>

      {/* Toolbar */}
      <div className="px-5 sm:px-6 py-3 bg-surface border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div className="relative flex-1 min-w-[200px] max-w-sm">
          <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by email, name or subject..."
            className="w-full h-9 pl-9 pr-3 bg-surface border border-slate-300 rounded-lg text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 text-sm"
          />
        </div>

        <div className="inline-flex items-center gap-0.5 bg-slate-100 p-1 rounded-lg">
          {filters.map((f) => (
            <button
              key={f.id}
              onClick={() => setFilter(f.id)}
              className={`inline-flex items-center gap-1.5 h-7 px-3 rounded-md text-xs font-medium transition ${
                filter === f.id ? 'bg-slate-300 text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
              }`}
            >
              {f.label}
              <span
                className={`min-w-[18px] px-1 rounded text-[10px] font-semibold tabular-nums ${
                  filter === f.id ? 'bg-slate-100 text-slate-700' : 'bg-slate-200/70 text-slate-500'
                }`}
              >
                {f.count}
              </span>
            </button>
          ))}
        </div>
      </div>

      {/* Table */}
      <div className="overflow-x-auto max-h-[480px]">
        <table className="w-full text-left border-collapse text-xs">
          <thead>
            <tr className="bg-slate-50 text-slate-500 sticky top-0 z-10 text-[11px] uppercase tracking-wider font-semibold border-b border-slate-200/80">
              <th className="py-3 px-5 whitespace-nowrap">Time</th>
              <th className="py-3 px-5 whitespace-nowrap">Recipient</th>
              <th className="py-3 px-5 whitespace-nowrap">Subject</th>
              <th className="py-3 px-5 whitespace-nowrap">Status</th>
              <th className="py-3 px-5 whitespace-nowrap">Sender (From)</th>
              <th className="py-3 px-5 whitespace-nowrap">Message ID</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 bg-surface">
            {filteredLogs.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-14 text-center">
                  <div className="mx-auto w-12 h-12 rounded-2xl bg-slate-50 ring-1 ring-slate-200 flex items-center justify-center mb-3">
                    <Activity className="w-5 h-5 text-slate-400" />
                  </div>
                  <p className="text-sm font-semibold text-slate-900">
                    {logs.length === 0 ? 'No delivery logs yet' : 'No logs match your search'}
                  </p>
                  <p className="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                    {logs.length === 0
                      ? 'Start a campaign or send a test email, and every dispatch will appear here in real time.'
                      : 'Try a different search term or filter.'}
                  </p>
                </td>
              </tr>
            ) : (
              filteredLogs.map((log) => {
                const failed = log.status === 'failed';
                const time = new Date(log.timestamp);
                return (
                  <tr key={log.id} className="hover:bg-slate-50 transition">
                    <td className="py-3 px-5 whitespace-nowrap">
                      <div className="font-mono text-[11px] text-slate-700 tabular-nums">{time.toLocaleTimeString()}</div>
                      <div className="text-[11px] text-slate-400">{time.toLocaleDateString()}</div>
                    </td>
                    <td className="py-3 px-5">
                      <div className="font-semibold text-slate-900 whitespace-nowrap">{log.recipientName}</div>
                      <div className="text-slate-500 font-mono text-[11px]">{log.recipientEmail}</div>
                    </td>
                    <td className="py-3 px-5 text-slate-700 max-w-[260px] truncate" title={log.subject}>
                      {log.subject}
                    </td>
                    <td className="py-3 px-5 whitespace-nowrap">
                      <span
                        className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset ${
                          failed ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-brand-50 text-brand-700 ring-brand-200'
                        }`}
                        title={log.detail}
                      >
                        {failed ? <AlertCircle className="w-3 h-3" /> : <CheckCircle2 className="w-3 h-3" />}
                        {failed ? 'Failed' : 'Delivered'}
                      </span>
                      <div className="mt-1 text-[10.5px] text-slate-400">{log.mode === 'smtp' ? 'via SMTP' : 'Sandbox (simulated)'}</div>
                    </td>
                    <td className="py-3 px-5 font-mono text-[11px] text-slate-600 max-w-[180px] truncate" title={log.senderEmail || 'My Account'}>
                      {log.senderEmail || 'My Account'}
                    </td>
                    <td className="py-3 px-5 font-mono text-[10.5px] text-slate-400 max-w-[200px] truncate" title={log.messageId}>
                      {log.messageId || '—'}
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
