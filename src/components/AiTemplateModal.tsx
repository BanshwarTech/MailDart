import React, { useState, useEffect } from 'react';
import {
  X,
  Sparkles,
  Loader2,
  Wand2,
  AlertCircle,
  Cpu,
  Zap,
  CheckCircle2,
  Info
} from 'lucide-react';

interface AiTemplateModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentFestival: string;
  currentCompany: string;
  onApplyHtml: (html: string) => void;
}

interface AiConfig {
  geminiConfigured: boolean;
  nvidiaConfigured: boolean;
  recommendedEngine: 'nvidia' | 'gemini';
  nvidiaModels: Array<{ id: string; name: string }>;
}

export const AiTemplateModal: React.FC<AiTemplateModalProps> = ({
  isOpen,
  onClose,
  currentFestival,
  currentCompany,
  onApplyHtml,
}) => {
  const [festival, setFestival] = useState(currentFestival || 'Diwali');
  const [companyName, setCompanyName] = useState(currentCompany || 'My Company');
  const [campaignOffer, setCampaignOffer] = useState('Special Festive 40% OFF with code FESTIVE40 + Free Gift Hamper');
  const [targetAudience, setTargetAudience] = useState('Premium Customers & Loyal Clients');
  const [tone, setTone] = useState('Celebratory, warm, prestigious and joyous');
  const [colorPalette, setColorPalette] = useState('Royal Gold, Deep Amber and Midnight Blue');
  
  // AI Engine State
  const [engine, setEngine] = useState<'nvidia' | 'gemini' | 'auto'>('auto');
  const [nvidiaModel, setNvidiaModel] = useState('meta/llama-3.3-70b-instruct');
  const [aiConfig, setAiConfig] = useState<AiConfig>({
    geminiConfigured: true,
    nvidiaConfigured: false,
    recommendedEngine: 'gemini',
    nvidiaModels: [
      { id: 'meta/llama-3.3-70b-instruct', name: 'Meta Llama 3.3 70B (Fast & High Quality)' },
      { id: 'nvidia/llama-3.1-nemotron-70b-instruct', name: 'NVIDIA Nemotron 70B' },
      { id: 'deepseek-ai/deepseek-r1', name: 'DeepSeek R1 (Reasoning)' },
      { id: 'mistralai/mistral-large-2-instruct', name: 'Mistral Large 2' },
    ],
  });

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Load AI configuration from server
  useEffect(() => {
    if (!isOpen) return;
    fetch('/api/ai-config')
      .then((res) => res.json())
      .then((data) => {
        if (data) {
          setAiConfig(data);
          if (data.nvidiaConfigured) {
            setEngine('nvidia');
          }
        }
      })
      .catch((err) => console.warn('Could not fetch AI config:', err));
  }, [isOpen]);

  if (!isOpen) return null;

  const handleGenerate = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const res = await fetch('/api/generate-template', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          festival,
          companyName,
          campaignOffer,
          targetAudience,
          tone,
          colorPalette,
          engine,
          nvidiaModel,
        }),
      });

      const data = await res.json();
      if (data.success && data.html) {
        onApplyHtml(data.html);
        onClose();
      } else {
        setError(data.error || 'Failed to generate template.');
      }
    } catch (err: any) {
      setError(err.message || 'Network error while contacting AI service.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm animate-fade-in overflow-y-auto">
      <div className="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl text-slate-200 p-6 md:p-7 my-6">
        
        <button
          onClick={onClose}
          className="absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition"
        >
          <X className="w-5 h-5" />
        </button>

        <div className="flex items-center gap-3 mb-5 border-b border-slate-800 pb-3">
          <div className="p-2.5 bg-gradient-to-tr from-emerald-500 via-teal-500 to-indigo-600 rounded-xl text-white shadow-lg shadow-emerald-500/20">
            <Sparkles className="w-5 h-5" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-lg font-bold text-white">AI Festival Email Designer</h2>
              <span className="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                NVIDIA NIM & Gemini
              </span>
            </div>
            <p className="text-xs text-slate-400">Generate responsive HTML email with personalized tags</p>
          </div>
        </div>

        <form onSubmit={handleGenerate} className="space-y-4">
          
          {/* AI Engine & Provider Selector */}
          <div className="bg-slate-950/70 border border-slate-800 rounded-xl p-3 space-y-2.5">
            <div className="flex items-center justify-between">
              <label className="text-xs font-semibold text-slate-300 flex items-center gap-1.5">
                <Cpu className="w-3.5 h-3.5 text-emerald-400" />
                Select AI Engine (प्रदाता चुनें)
              </label>
              <span className="text-[10px] text-slate-400">
                {aiConfig.nvidiaConfigured ? '🟢 NVIDIA Key Detected' : '🟣 Gemini 3.8 Flash Ready'}
              </span>
            </div>

            <div className="grid grid-cols-3 gap-2">
              <button
                type="button"
                onClick={() => setEngine('nvidia')}
                className={`py-2 px-2.5 rounded-xl border text-xs font-medium flex flex-col items-center gap-1 transition ${
                  engine === 'nvidia'
                    ? 'bg-emerald-950/60 border-emerald-500 text-emerald-300 shadow-md shadow-emerald-950/50'
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200'
                }`}
              >
                <div className="flex items-center gap-1">
                  <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                  <span className="font-bold">NVIDIA NIM</span>
                </div>
                <span className="text-[9px] text-slate-400 font-mono">Meta Llama / Nemotron</span>
              </button>

              <button
                type="button"
                onClick={() => setEngine('gemini')}
                className={`py-2 px-2.5 rounded-xl border text-xs font-medium flex flex-col items-center gap-1 transition ${
                  engine === 'gemini'
                    ? 'bg-purple-950/60 border-purple-500 text-purple-300 shadow-md shadow-purple-950/50'
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200'
                }`}
              >
                <div className="flex items-center gap-1">
                  <Sparkles className="w-3 h-3 text-purple-400" />
                  <span className="font-bold">Gemini AI</span>
                </div>
                <span className="text-[9px] text-slate-400 font-mono">Gemini 3.8 Flash</span>
              </button>

              <button
                type="button"
                onClick={() => setEngine('auto')}
                className={`py-2 px-2.5 rounded-xl border text-xs font-medium flex flex-col items-center gap-1 transition ${
                  engine === 'auto'
                    ? 'bg-blue-950/60 border-blue-500 text-blue-300 shadow-md shadow-blue-950/50'
                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-slate-200'
                }`}
              >
                <div className="flex items-center gap-1">
                  <Zap className="w-3 h-3 text-blue-400" />
                  <span className="font-bold">Auto (Smart)</span>
                </div>
                <span className="text-[9px] text-slate-400 font-mono">Best Available</span>
              </button>
            </div>

            {/* NVIDIA Model Dropdown when NVIDIA or Auto is selected */}
            {(engine === 'nvidia' || engine === 'auto') && (
              <div className="pt-2 border-t border-slate-850 flex items-center justify-between gap-2 text-xs">
                <span className="text-slate-400 text-[11px] shrink-0">NVIDIA Model:</span>
                <select
                  value={nvidiaModel}
                  onChange={(e) => setNvidiaModel(e.target.value)}
                  className="bg-slate-900 border border-slate-750 text-slate-200 rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-emerald-500 w-full"
                >
                  {aiConfig.nvidiaModels.map((m) => (
                    <option key={m.id} value={m.id}>
                      {m.name}
                    </option>
                  ))}
                </select>
              </div>
            )}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-slate-400 mb-1">Festival / Occasion</label>
              <input
                type="text"
                value={festival}
                onChange={(e) => setFestival(e.target.value)}
                placeholder="e.g. Diwali, Eid, Christmas"
                required
                className="w-full px-3 py-2 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
            <div>
              <label className="block text-xs text-slate-400 mb-1">Company / Brand Name</label>
              <input
                type="text"
                value={companyName}
                onChange={(e) => setCompanyName(e.target.value)}
                placeholder="e.g. Nexus Corp"
                required
                className="w-full px-3 py-2 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs text-slate-400 mb-1">Festival Offer / Discount Details</label>
            <input
              type="text"
              value={campaignOffer}
              onChange={(e) => setCampaignOffer(e.target.value)}
              placeholder="e.g. Flat 50% OFF on all items + Free Sweets Box with code DIWALI2026"
              required
              className="w-full px-3 py-2 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-slate-400 mb-1">Audience Type</label>
              <input
                type="text"
                value={targetAudience}
                onChange={(e) => setTargetAudience(e.target.value)}
                placeholder="e.g. B2B Partners, VIP Shoppers"
                className="w-full px-3 py-2 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
            <div>
              <label className="block text-xs text-slate-400 mb-1">Color Style / Vibes</label>
              <input
                type="text"
                value={colorPalette}
                onChange={(e) => setColorPalette(e.target.value)}
                placeholder="e.g. Royal Gold, Emerald Green"
                className="w-full px-3 py-2 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs text-slate-400 mb-1">Tone & Voice</label>
            <input
              type="text"
              value={tone}
              onChange={(e) => setTone(e.target.value)}
              placeholder="e.g. Warm, celebratory, festive, prestigious"
              className="w-full px-3 py-2 text-xs bg-slate-850 border border-slate-750 rounded-lg text-white focus:outline-none focus:border-emerald-500"
            />
          </div>

          {error && (
            <div className="p-3 rounded-xl bg-rose-950/70 border border-rose-800 text-xs text-rose-200 flex flex-col gap-2">
              <div className="flex items-start gap-2">
                <AlertCircle className="w-4 h-4 text-rose-400 shrink-0 mt-0.5" />
                <span className="break-words font-mono text-[11px]">{error}</span>
              </div>
              {error.includes('NVIDIA_API_KEY') && (
                <div className="flex items-center gap-2 mt-1">
                  <span className="text-[11px] text-slate-300">Switch to Gemini 3.8 Flash:</span>
                  <button
                    type="button"
                    onClick={() => {
                      setEngine('gemini');
                      setError(null);
                    }}
                    className="px-2.5 py-1 bg-purple-700 hover:bg-purple-600 text-white rounded-lg text-xs font-semibold transition"
                  >
                    Use Gemini 3.8 Flash
                  </button>
                </div>
              )}
            </div>
          )}

          <div className="pt-3 border-t border-slate-800 flex items-center justify-between">
            <span className="text-[11px] text-slate-400 flex items-center gap-1">
              <Info className="w-3.5 h-3.5 text-slate-400" />
              {'Includes {{name}}, {{company}}, {{discount}} tags'}
            </span>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={onClose}
                className="px-3.5 py-1.5 text-xs text-slate-400 hover:text-white"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isLoading}
                className="px-4 py-2 bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 disabled:opacity-50 text-white font-medium text-xs rounded-xl shadow-lg shadow-emerald-950/50 flex items-center gap-1.5 transition"
              >
                {isLoading ? (
                  <>
                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                    Generating with {engine === 'nvidia' ? 'NVIDIA NIM' : engine === 'gemini' ? 'Gemini' : 'AI'}...
                  </>
                ) : (
                  <>
                    <Wand2 className="w-3.5 h-3.5" />
                    Generate Festive HTML
                  </>
                )}
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  );
};
