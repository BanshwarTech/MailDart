import React, { useState, useRef } from 'react';
import {
  Upload,
  FileSpreadsheet,
  CheckCircle2,
  AlertTriangle,
  Download,
  X,
  FileText,
  Check,
  Sparkles,
  Info,
  ArrowRight,
  FileCheck,
  Tag,
  Copy
} from 'lucide-react';
import { Recipient } from '../types';
import {
  downloadSampleExcelTemplate,
  parseExcelOrCsvFile,
  ParseExcelResult
} from '../utils/excelHelper';

interface ExcelUploadModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentCount: number;
  onImportRecipients: (
    recipients: Recipient[],
    replace: boolean,
    discoveredVariables: string[],
    autoInsertTemplateBlock?: boolean
  ) => void;
}

export const ExcelUploadModal: React.FC<ExcelUploadModalProps> = ({
  isOpen,
  onClose,
  currentCount,
  onImportRecipients,
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fileName, setFileName] = useState<string | null>(null);
  const [parseResult, setParseResult] = useState<ParseExcelResult | null>(null);
  const [importMode, setImportMode] = useState<'append' | 'replace'>('append');
  const [autoInsertBlock, setAutoInsertBlock] = useState<boolean>(true);
  const [copiedTag, setCopiedTag] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  if (!isOpen) return null;

  const handleFileProcess = async (file: File) => {
    if (!file) return;

    // Check file extension
    const validExts = ['.xlsx', '.xls', '.csv'];
    const lowerName = file.name.toLowerCase();
    const isValid = validExts.some((ext) => lowerName.endsWith(ext));

    if (!isValid) {
      setError('Please upload only a .xlsx, .xls or .csv file.');
      return;
    }

    setIsLoading(true);
    setError(null);
    setFileName(file.name);

    try {
      const result = await parseExcelOrCsvFile(file);
      setParseResult(result);
    } catch (err: any) {
      setError(err.message || 'Could not read the Excel file. Please check the file and try again.');
      setParseResult(null);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileProcess(e.dataTransfer.files[0]);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFileProcess(e.target.files[0]);
    }
  };

  const handleConfirmImport = () => {
    if (!parseResult || parseResult.recipients.length === 0) return;
    onImportRecipients(
      parseResult.recipients,
      importMode === 'replace',
      parseResult.discoveredVariables,
      autoInsertBlock
    );
    onClose();
  };

  const handleCopyTag = (tag: string) => {
    navigator.clipboard.writeText(`{{${tag}}}`);
    setCopiedTag(tag);
    setTimeout(() => setCopiedTag(null), 2000);
  };

  const handleReset = () => {
    setParseResult(null);
    setFileName(null);
    setError(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-[3px] animate-fade-in overflow-y-auto">
      <div className="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto bg-surface border border-slate-200 rounded-2xl shadow-modal text-slate-700 p-6 md:p-8 my-4">

        {/* Close Button */}
        <button
          onClick={onClose}
          className="absolute top-5 right-5 text-slate-500 hover:text-slate-900 p-1.5 rounded-lg hover:bg-slate-100 transition"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Modal Header */}
        <div className="flex items-center gap-3 mb-4">
          <div className="p-3 rounded-xl bg-brand-50 border border-brand-200 text-brand-700">
            <FileSpreadsheet className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-lg font-semibold text-slate-900">
              Import Recipients from Excel
            </h2>
            <p className="text-xs text-slate-500">
              Every column in your sheet automatically becomes a template variable
            </p>
          </div>
        </div>

        {/* Requirements Banner */}
        <div className="mb-5 p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
          <div className="flex items-center justify-between">
            <span className="font-semibold text-slate-900 flex items-center gap-1.5">
              <Info className="w-4 h-4 text-brand-700" />
              Expected columns in your sheet:
            </span>
            <button
              onClick={downloadSampleExcelTemplate}
              className="text-[11px] text-brand-700 hover:text-brand-800 font-semibold flex items-center gap-1 underline underline-offset-2"
            >
              <Download className="w-3 h-3" />
              Download Sample Sheet (.xlsx)
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-slate-600 pt-1">
            <div className="p-2 rounded bg-surface border border-slate-200">
              <span className="font-bold text-brand-700 block">⭐️ Email (Required):</span>
              <span className="text-slate-500">Column: Email, Mail or Receiver</span>
            </div>
            <div className="p-2 rounded bg-surface border border-slate-200">
              <span className="font-bold text-brand-700 block">⭐️ Name:</span>
              <span className="text-slate-500">Column: Name or Full Name (tag: {'{{name}}'})</span>
            </div>
          </div>
          <p className="text-[11px] text-amber-800 pt-0.5">
            💡 <strong>Any extra column works:</strong> columns like <code>City</code>, <code>Gift Item</code>, <code>Order ID</code> or <code>Phone</code> automatically become <code>{'{{city}}'}</code>, <code>{'{{gift_item}}'}</code> and <code>{'{{order_id}}'}</code> as soon as you upload!
          </p>
        </div>

        {/* Upload Dropzone */}
        {!parseResult && (
          <div
            onDragOver={(e) => {
              e.preventDefault();
              setIsDragging(true);
            }}
            onDragLeave={() => setIsDragging(false)}
            onDrop={handleDrop}
            onClick={() => fileInputRef.current?.click()}
            className={`border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all flex flex-col items-center justify-center gap-3 ${ isDragging ? 'border-brand-500 bg-brand-50'
                : 'border-slate-300 hover:border-brand-400 bg-slate-50 hover:bg-brand-50/40'
            }`}
          >
            <input
              ref={fileInputRef}
              type="file"
              accept=".xlsx, .xls, .csv"
              onChange={handleFileSelect}
              className="hidden"
            />

            <div className="p-4 rounded-full bg-brand-50 border border-brand-200 text-brand-700">
              <Upload className="w-8 h-8" />
            </div>

            <div>
              <p className="text-sm font-semibold text-slate-900">
                Drag and drop your Excel file here, or click to browse
              </p>
              <p className="text-xs text-slate-500 mt-1">
                Supported formats: <strong>.xlsx, .xls, .csv</strong> (variables are created as soon as the file is uploaded)
              </p>
            </div>

            {isLoading && (
              <div className="flex items-center gap-2 text-xs text-brand-700 mt-2">
                <span className="w-3.5 h-3.5 border-2 border-brand-600 border-t-transparent rounded-full animate-spin" />
                <span>Reading your file and creating variables...</span>
              </div>
            )}
          </div>
        )}

        {/* Error message */}
        {error && (
          <div className="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {/* Parsed Result & Auto-Generated Variables Banner */}
        {parseResult && (
          <div className="space-y-4">
            
            {/* File info card */}
            <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
              <div className="flex items-center gap-2.5">
                <FileCheck className="w-5 h-5 text-brand-700" />
                <div>
                  <p className="text-xs font-semibold text-slate-900 font-mono">{fileName}</p>
                  <p className="text-[11px] text-slate-500">
                    Total rows: {parseResult.totalRows} | Valid emails: <strong className="text-brand-700">{parseResult.validCount}</strong>
                    {parseResult.duplicatesCount > 0 && ` | Duplicates: ${parseResult.duplicatesCount}`}
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={handleReset}
                className="text-xs text-slate-500 hover:text-slate-900 underline self-start sm:self-auto"
              >
                Change File
              </button>
            </div>

            {/* INSTANT AUTO-GENERATED VARIABLES BANNER */}
            <div className="p-3.5 rounded-xl bg-brand-50 border border-brand-200 space-y-2 shadow-sm">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Sparkles className="w-4 h-4 text-brand-700" />
                  <span className="text-xs font-semibold text-slate-900">
                    ⚡ {parseResult.discoveredVariables.length} variables created from your sheet:
                  </span>
                </div>
                <span className="text-[10px] text-brand-700 font-mono bg-brand-100 px-2 py-0.5 rounded-full border border-brand-200">
                  Ready to use instantly
                </span>
              </div>
              <p className="text-[11px] text-slate-600">
                No extra effort needed! These variables were created from the columns in your sheet. Wherever a tag appears in the template, each recipient's own data will be filled in:
              </p>

              {/* Badges of all generated variables */}
              <div className="flex flex-wrap gap-1.5 pt-1">
                {parseResult.discoveredVariables.map((v) => (
                  <button
                    key={v}
                    type="button"
                    onClick={() => handleCopyTag(v)}
                    className="px-2.5 py-1 rounded-lg bg-surface hover:bg-brand-100 border border-brand-300 text-brand-700 font-mono text-xs font-semibold shadow-sm transition flex items-center gap-1 group"
                    title="Click to copy tag"
                  >
                    <span>{'{{' + v + '}}'}</span>
                    {copiedTag === v ? (
                      <Check className="w-3 h-3 text-brand-700" />
                    ) : (
                      <Copy className="w-3 h-3 text-slate-400 group-hover:text-brand-700" />
                    )}
                  </button>
                ))}
              </div>

              {/* Auto-Insert into template checkbox */}
              <div className="pt-2 border-t border-brand-200 flex items-center justify-between text-xs">
                <label className="flex items-center gap-2 text-slate-700 cursor-pointer font-medium select-none">
                  <input
                    type="checkbox"
                    checked={autoInsertBlock}
                    onChange={(e) => setAutoInsertBlock(e.target.checked)}
                    className="rounded focus:ring-0 accent-brand-600"
                  />
                  <span>
                    Also add a block with these variables to the email template
                  </span>
                </label>
              </div>
            </div>

            {/* Preview Table */}
            <div className="border border-slate-200 rounded-xl overflow-hidden max-h-44 overflow-y-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead className="bg-slate-50 text-slate-500 sticky top-0 text-[10px] uppercase font-semibold">
                  <tr>
                    <th className="py-2 px-3">#</th>
                    <th className="py-2 px-3">Name</th>
                    <th className="py-2 px-3">Email</th>
                    <th className="py-2 px-3">Auto-Generated Variables Data</th>
                    <th className="py-2 px-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 bg-surface font-mono text-[11px]">
                  {parseResult.preview.map((row, idx) => (
                    <tr key={idx} className={row.isValid ? 'hover:bg-slate-50' : 'bg-red-50 text-red-700'}>
                      <td className="py-1.5 px-3 text-slate-500">{idx + 1}</td>
                      <td className="py-1.5 px-3 font-sans text-slate-700">{row.name}</td>
                      <td className="py-1.5 px-3 text-brand-700">{row.email}</td>
                      <td className="py-1.5 px-3 text-slate-600">
                        {Object.keys(row.customFields).length > 0 ? (
                          <div className="flex flex-wrap gap-1">
                            {Object.entries(row.customFields).slice(0, 3).map(([k, v]) => (
                              <span key={k} className="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] text-slate-600">
                                {k}: <strong className="text-amber-700">{v}</strong>
                              </span>
                            ))}
                          </div>
                        ) : (
                          <span className="text-slate-400">—</span>
                        )}
                      </td>
                      <td className="py-1.5 px-3">
                        {row.isValid ? (
                          <span className="text-[10px] text-brand-700 flex items-center gap-1">
                            <Check className="w-3 h-3" /> Valid
                          </span>
                        ) : (
                          <span className="text-[10px] text-red-600 flex items-center gap-1">
                            <AlertTriangle className="w-3 h-3" /> Invalid Email
                          </span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Import Mode Options (Append vs Replace) */}
            <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
              <span className="text-slate-700 font-medium">Import mode:</span>
              <div className="flex items-center gap-3">
                <label className="flex items-center gap-1.5 cursor-pointer text-slate-700">
                  <input
                    type="radio"
                    name="importMode"
                    value="append"
                    checked={importMode === 'append'}
                    onChange={() => setImportMode('append')}
                    className="focus:ring-0 accent-brand-600"
                  />
                  <span>Add to existing list (+{parseResult.validCount} to {currentCount})</span>
                </label>
                <label className="flex items-center gap-1.5 cursor-pointer text-slate-700">
                  <input
                    type="radio"
                    name="importMode"
                    value="replace"
                    checked={importMode === 'replace'}
                    onChange={() => setImportMode('replace')}
                    className="focus:ring-0 accent-brand-600"
                  />
                  <span>Replace entire list</span>
                </label>
              </div>
            </div>
          </div>
        )}

        {/* Modal Footer */}
        <div className="flex items-center justify-between gap-3 pt-5 border-t border-slate-200 mt-5">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-xs text-slate-500 hover:text-slate-900 transition"
          >
            Cancel
          </button>

          {parseResult ? (
            <button
              type="button"
              onClick={handleConfirmImport}
              disabled={parseResult.validCount === 0}
              className="px-5 py-2.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-xs rounded-lg transition flex items-center gap-2 shadow-button"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>
                Import {parseResult.validCount} Recipients
              </span>
            </button>
          ) : (
            <button
              type="button"
              onClick={downloadSampleExcelTemplate}
              className="px-4 py-2 bg-surface hover:bg-slate-50 text-slate-700 border border-slate-300 hover:border-slate-400 text-xs font-semibold rounded-lg shadow-xs transition flex items-center gap-1.5"
            >
              <Download className="w-3.5 h-3.5 text-brand-700" />
              <span>Download Blank Template</span>
            </button>
          )}
        </div>

      </div>
    </div>
  );
};
