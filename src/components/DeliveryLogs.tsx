import React, { useState } from 'react';
import {
  Activity,
  Download,
  Trash2,
  CheckCircle2,
  AlertCircle,
  ShieldCheck,
  Search,
  ExternalLink
} from 'lucide-react';
import { DispatchLog } from '../types';
import { exportToCsv } from '../utils/campaignHelper';

interface DeliveryLogsProps {
  logs: DispatchLog[];
  onClearLogs: () => void;
}

export const DeliveryLogs: React.FC<DeliveryLogsProps> = ({ logs, onClearLogs }) => {
  const [search, setSearch] = useState('');

  const filteredLogs = logs.filter(
    (log) =>
      log.recipientEmail.toLowerCase().includes(search.toLowerCase()) ||
      log.recipientName.toLowerCase().includes(search.toLowerCase()) ||
      log.subject.toLowerCase().includes(search.toLowerCase())
  );

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

  return (
    <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl flex flex-col">
      
      {/* Top Bar */}
      <div className="p-4 border-b border-slate-800 bg-slate-900/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div>
          <h2 className="text-base font-bold text-white flex items-center gap-2">
            <Activity className="w-4 h-4 text-emerald-400" />
            डिस्पैच ऑडिट लॉग्स (Real-Time Delivery Logs)
          </h2>
          <p className="text-xs text-slate-400">
            {logs.length} logged email dispatches with message IDs and timestamps
          </p>
        </div>

        <div className="flex items-center gap-2">
          {logs.length > 0 && (
            <>
              <button
                onClick={handleExport}
                className="flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 text-xs font-medium rounded-xl transition"
                title="Download CSV log"
              >
                <Download className="w-3.5 h-3.5 text-emerald-400" />
                <span>Export CSV</span>
              </button>
              <button
                onClick={onClearLogs}
                className="p-1.5 bg-slate-800 hover:bg-rose-950 text-slate-400 hover:text-rose-400 border border-slate-700 rounded-xl transition"
                title="Clear Logs"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </>
          )}
        </div>
      </div>

      {/* Search */}
      <div className="px-4 py-2 bg-slate-850 border-b border-slate-800 flex items-center justify-between gap-3 text-xs">
        <div className="relative w-full max-w-sm">
          <Search className="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search logs by email, name or subject..."
            className="w-full pl-8 pr-3 py-1.5 bg-slate-900 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500 text-xs"
          />
        </div>
        <span className="text-slate-400 text-[11px] font-mono">
          Showing {filteredLogs.length} entries
        </span>
      </div>

      {/* Log list / Table */}
      <div className="overflow-x-auto max-h-[300px] divide-y divide-slate-800">
        <table className="w-full text-left border-collapse text-xs">
          <thead>
            <tr className="bg-slate-900/90 text-slate-400 sticky top-0 z-10 text-[11px] uppercase tracking-wider font-semibold">
              <th className="py-2.5 px-4">Time</th>
              <th className="py-2.5 px-4">Sender (From)</th>
              <th className="py-2.5 px-4">Recipient</th>
              <th className="py-2.5 px-4">Subject</th>
              <th className="py-2.5 px-4">Status & Mode</th>
              <th className="py-2.5 px-4">Message ID</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800 bg-slate-950/40">
            {filteredLogs.length === 0 ? (
              <tr>
                <td colSpan={6} className="py-8 text-center text-slate-500">
                  No delivery logs recorded yet. Start campaign or send a test email to see real-time logs here!
                </td>
              </tr>
            ) : (
              filteredLogs.map((log) => (
                <tr key={log.id} className="hover:bg-slate-850/60 transition">
                  <td className="py-2.5 px-4 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                    {new Date(log.timestamp).toLocaleTimeString()}
                  </td>
                  <td className="py-2.5 px-4 font-mono text-[11px] text-emerald-400 max-w-[150px] truncate" title={log.senderEmail || 'My Account'}>
                    {log.senderEmail || 'My Account'}
                  </td>
                  <td className="py-2.5 px-4">
                    <div className="font-semibold text-slate-200">{log.recipientName}</div>
                    <div className="text-slate-400 font-mono text-[11px]">{log.recipientEmail}</div>
                  </td>
                  <td className="py-2.5 px-4 text-slate-300 max-w-[240px] truncate" title={log.subject}>
                    {log.subject}
                  </td>
                  <td className="py-2.5 px-4 whitespace-nowrap">
                    {log.status === 'delivered' ? (
                      <span className="inline-flex items-center gap-1 text-emerald-400 font-semibold text-[11px]">
                        <CheckCircle2 className="w-3 h-3" />
                        {log.mode === 'smtp' ? 'SMTP Sent' : 'Delivered (Sandbox)'}
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 text-rose-400 font-semibold text-[11px]">
                        <AlertCircle className="w-3 h-3" /> Failed
                      </span>
                    )}
                  </td>
                  <td className="py-2.5 px-4 font-mono text-[10px] text-slate-400 max-w-[200px] truncate" title={log.messageId}>
                    {log.messageId || '—'}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

    </div>
  );
};
