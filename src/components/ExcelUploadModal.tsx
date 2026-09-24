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
      setError('कृपया केवल .xlsx, .xls या .csv फाइल अपलोड करें। (Only Excel or CSV allowed)');
      return;
    }

    setIsLoading(true);
    setError(null);
    setFileName(file.name);

    try {
      const result = await parseExcelOrCsvFile(file);
      setParseResult(result);
    } catch (err: any) {
      setError(err.message || 'एक्सेल फाइल को पढ़ने में त्रुटि हुई।');
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
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-fade-in overflow-y-auto">
      <div className="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl text-slate-200 p-6 md:p-8 my-4">
        
        {/* Close Button */}
        <button
          onClick={onClose}
          className="absolute top-5 right-5 text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Modal Header */}
        <div className="flex items-center gap-3 mb-4">
          <div className="p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400">
            <FileSpreadsheet className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">
              एक्सेल फाइल से ऑटो-वेरिएबल्स इम्पोर्ट (Excel Auto-Variables Import)
            </h2>
            <p className="text-xs text-slate-400">
              एक्सेल अपलोड होते ही सभी कॉलम्स के वेरिएबल्स तुरंत अपने आप जनरेट हो जाएंगे
            </p>
          </div>
        </div>

        {/* Requirements Banner */}
        <div className="mb-5 p-3.5 bg-slate-850/80 border border-slate-800 rounded-xl text-xs space-y-2">
          <div className="flex items-center justify-between">
            <span className="font-semibold text-slate-200 flex items-center gap-1.5">
              <Info className="w-4 h-4 text-sky-400" />
              एक्सेल में अपेक्षित कॉलम्स (Defined Required Columns):
            </span>
            <button
              onClick={downloadSampleExcelTemplate}
              className="text-[11px] text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1 underline underline-offset-2"
            >
              <Download className="w-3 h-3" />
              रेडीमेड टेम्पलेट डाउनलोड करें (.xlsx)
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-slate-300 pt-1">
            <div className="p-2 rounded bg-slate-900/80 border border-slate-750">
              <span className="font-bold text-emerald-400 block">⭐️ Email (अनिवार्य / Required):</span>
              <span className="text-slate-400">कॉलम: Email, Mail, Receiver, ईमेल</span>
            </div>
            <div className="p-2 rounded bg-slate-900/80 border border-slate-750">
              <span className="font-bold text-sky-400 block">⭐️ Name (नाम):</span>
              <span className="text-slate-400">कॉलम: Name, Full Name (ऑटो टैग: {'{{name}}'})</span>
            </div>
          </div>
          <p className="text-[11px] text-amber-300/90 pt-0.5">
            💡 <strong>कस्टम कॉलम्स की आज़ादी:</strong> आपकी एक्सेल में जो भी अतिरिक्त कॉलम होंगे (जैसे <code>City</code>, <code>Gift Item</code>, <code>Order ID</code>, <code>Phone</code>), वे अपलोड होते ही स्वतः <code>{'{{city}}'}</code>, <code>{'{{gift_item}}'}</code>, <code>{'{{order_id}}'}</code> बन जाएंगे!
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
            className={`border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all flex flex-col items-center justify-center gap-3 ${
              isDragging
                ? 'border-emerald-500 bg-emerald-500/10'
                : 'border-slate-750 hover:border-emerald-500/50 bg-slate-950/50 hover:bg-slate-950'
            }`}
          >
            <input
              ref={fileInputRef}
              type="file"
              accept=".xlsx, .xls, .csv"
              onChange={handleFileSelect}
              className="hidden"
            />

            <div className="p-4 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
              <Upload className="w-8 h-8 animate-pulse" />
            </div>

            <div>
              <p className="text-sm font-semibold text-white">
                अपनी एक्सेल फाइल यहाँ ड्रैग करें या क्लिक करके चुनें
              </p>
              <p className="text-xs text-slate-400 mt-1">
                समर्थित फॉर्मेट्स: <strong>.xlsx, .xls, .csv</strong> (फाइल अपलोड होते ही वेरिएबल्स तुरंत जनरेट होंगे)
              </p>
            </div>

            {isLoading && (
              <div className="flex items-center gap-2 text-xs text-emerald-400 mt-2">
                <span className="w-3.5 h-3.5 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin" />
                <span>फाइल पढ़ी जा रही है व वेरिएबल्स जनरेट हो रहे हैं...</span>
              </div>
            )}
          </div>
        )}

        {/* Error message */}
        {error && (
          <div className="mt-4 p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {/* Parsed Result & Auto-Generated Variables Banner */}
        {parseResult && (
          <div className="space-y-4">
            
            {/* File info card */}
            <div className="p-3 bg-slate-850 rounded-xl border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
              <div className="flex items-center gap-2.5">
                <FileCheck className="w-5 h-5 text-emerald-400" />
                <div>
                  <p className="text-xs font-bold text-white font-mono">{fileName}</p>
                  <p className="text-[11px] text-slate-400">
                    कुल पंक्तियाँ: {parseResult.totalRows} | मान्य ईमेल: <strong className="text-emerald-400">{parseResult.validCount}</strong>
                    {parseResult.duplicatesCount > 0 && ` | डुप्लीकेट: ${parseResult.duplicatesCount}`}
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={handleReset}
                className="text-xs text-slate-400 hover:text-white underline self-start sm:self-auto"
              >
                दूसरी फाइल चुनें (Change File)
              </button>
            </div>

            {/* INSTANT AUTO-GENERATED VARIABLES BANNER */}
            <div className="p-3.5 rounded-xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-indigo-950/60 border border-emerald-500/40 space-y-2 shadow-lg">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Sparkles className="w-4 h-4 text-emerald-400 animate-spin" />
                  <span className="text-xs font-bold text-white">
                    ⚡ एक्सेल से स्वतः जनरेट हुए मेल वेरिएबल्स ({parseResult.discoveredVariables.length} Variables Auto-Generated):
                  </span>
                </div>
                <span className="text-[10px] text-emerald-300 font-mono bg-emerald-500/20 px-2 py-0.5 rounded-full border border-emerald-500/30">
                  Ready to use instantly
                </span>
              </div>
              <p className="text-[11px] text-slate-300">
                कोई अतिरिक्त मेहनत नहीं! आपकी एक्सेल फाइल के प्रत्येक कॉलम से ये वेरिएबल्स अपने आप बन गए हैं। टेम्पलेट में जहाँ भी यह टैग होगा, वहाँ हर यूजर का डेटा स्वतः लग जाएगा:
              </p>

              {/* Badges of all generated variables */}
              <div className="flex flex-wrap gap-1.5 pt-1">
                {parseResult.discoveredVariables.map((v) => (
                  <button
                    key={v}
                    type="button"
                    onClick={() => handleCopyTag(v)}
                    className="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-emerald-500/50 text-emerald-300 font-mono text-xs font-semibold shadow transition flex items-center gap-1 group"
                    title="Click to copy tag"
                  >
                    <span>{'{{' + v + '}}'}</span>
                    {copiedTag === v ? (
                      <Check className="w-3 h-3 text-emerald-400" />
                    ) : (
                      <Copy className="w-3 h-3 text-slate-500 group-hover:text-emerald-400" />
                    )}
                  </button>
                ))}
              </div>

              {/* Auto-Insert into template checkbox */}
              <div className="pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
                <label className="flex items-center gap-2 text-slate-200 cursor-pointer font-medium select-none">
                  <input
                    type="checkbox"
                    checked={autoInsertBlock}
                    onChange={(e) => setAutoInsertBlock(e.target.checked)}
                    className="rounded text-emerald-500 focus:ring-0 accent-emerald-500"
                  />
                  <span>
                    ईमेल टेम्पलेट में इन वेरिएबल्स का ग्रीटिंग कार्ड भी स्वतः जोड़ें (Auto-append variables block to template)
                  </span>
                </label>
              </div>
            </div>

            {/* Preview Table */}
            <div className="border border-slate-800 rounded-xl overflow-hidden max-h-44 overflow-y-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead className="bg-slate-950 text-slate-400 sticky top-0 text-[10px] uppercase font-semibold">
                  <tr>
                    <th className="py-2 px-3">#</th>
                    <th className="py-2 px-3">Name</th>
                    <th className="py-2 px-3">Email</th>
                    <th className="py-2 px-3">Auto-Generated Variables Data</th>
                    <th className="py-2 px-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-850 bg-slate-900/60 font-mono text-[11px]">
                  {parseResult.preview.map((row, idx) => (
                    <tr key={idx} className={row.isValid ? 'hover:bg-slate-800/40' : 'bg-rose-950/20 text-rose-300'}>
                      <td className="py-1.5 px-3 text-slate-500">{idx + 1}</td>
                      <td className="py-1.5 px-3 font-sans text-slate-200">{row.name}</td>
                      <td className="py-1.5 px-3 text-emerald-400">{row.email}</td>
                      <td className="py-1.5 px-3 text-slate-300">
                        {Object.keys(row.customFields).length > 0 ? (
                          <div className="flex flex-wrap gap-1">
                            {Object.entries(row.customFields).slice(0, 3).map(([k, v]) => (
                              <span key={k} className="px-1.5 py-0.5 rounded bg-slate-800 text-[10px] text-slate-300">
                                {k}: <strong className="text-amber-300">{v}</strong>
                              </span>
                            ))}
                          </div>
                        ) : (
                          <span className="text-slate-500">—</span>
                        )}
                      </td>
                      <td className="py-1.5 px-3">
                        {row.isValid ? (
                          <span className="text-[10px] text-emerald-400 flex items-center gap-1">
                            <Check className="w-3 h-3" /> Valid
                          </span>
                        ) : (
                          <span className="text-[10px] text-rose-400 flex items-center gap-1">
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
            <div className="p-3 rounded-xl bg-slate-850 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
              <span className="text-slate-300 font-medium">इम्पोर्ट का तरीका चुनें (Import Mode):</span>
              <div className="flex items-center gap-3">
                <label className="flex items-center gap-1.5 cursor-pointer text-slate-300">
                  <input
                    type="radio"
                    name="importMode"
                    value="append"
                    checked={importMode === 'append'}
                    onChange={() => setImportMode('append')}
                    className="text-emerald-500 focus:ring-0 accent-emerald-500"
                  />
                  <span>मौजूदा लिस्ट में जोड़ें (+{parseResult.validCount} to {currentCount})</span>
                </label>
                <label className="flex items-center gap-1.5 cursor-pointer text-slate-300">
                  <input
                    type="radio"
                    name="importMode"
                    value="replace"
                    checked={importMode === 'replace'}
                    onChange={() => setImportMode('replace')}
                    className="text-emerald-500 focus:ring-0 accent-emerald-500"
                  />
                  <span>पूरी लिस्ट बदलें (Replace All)</span>
                </label>
              </div>
            </div>
          </div>
        )}

        {/* Modal Footer */}
        <div className="flex items-center justify-between gap-3 pt-5 border-t border-slate-800 mt-5">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-xs text-slate-400 hover:text-white transition"
          >
            Cancel
          </button>

          {parseResult ? (
            <button
              type="button"
              onClick={handleConfirmImport}
              disabled={parseResult.validCount === 0}
              className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-semibold text-xs rounded-xl shadow-lg shadow-emerald-950/60 transition flex items-center gap-2"
            >
              <CheckCircle2 className="w-4 h-4" />
              <span>
                {parseResult.validCount} प्राप्तकर्ता व वेरिएबल्स इम्पोर्ट करें (Import With Variables)
              </span>
            </button>
          ) : (
            <button
              type="button"
              onClick={downloadSampleExcelTemplate}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-750 text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
            >
              <Download className="w-3.5 h-3.5 text-emerald-400" />
              <span>Download Blank Template</span>
            </button>
          )}
        </div>

      </div>
    </div>
  );
};
