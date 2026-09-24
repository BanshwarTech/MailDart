import React from 'react';
import { ArrowLeft, ArrowRight, CheckCircle2 } from 'lucide-react';
import { ViewSection } from '../types';
import { STEP_ITEMS, TOTAL_STEPS } from '../data/navigation';

interface StepNavProps {
  current: ViewSection;
  stepDone: Partial<Record<ViewSection, boolean>>;
  onNavigate: (section: ViewSection) => void;
}

/** Previous / next navigation shown at the bottom of every workflow step. */
export const StepNav: React.FC<StepNavProps> = ({ current, stepDone, onNavigate }) => {
  const index = STEP_ITEMS.findIndex((s) => s.id === current);
  if (index < 0) return null;
  const prev = STEP_ITEMS[index - 1];
  const next = STEP_ITEMS[index + 1];
  const done = !!stepDone[current];

  return (
    <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 rounded-2xl bg-surface border border-slate-200/80 shadow-card px-5 py-4">
      <div className="flex items-center gap-3">
        <span
          className={`w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-sm font-semibold ${
            done ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-300'
          }`}
        >
          {done ? <CheckCircle2 className="w-4 h-4" /> : index + 1}
        </span>
        <div>
          <p className="text-sm font-semibold text-slate-900">
            Step {index + 1} of {TOTAL_STEPS} · {STEP_ITEMS[index].label}
          </p>
          <p className="text-xs text-slate-500">{done ? 'Completed — you can move on.' : 'Complete this step, then continue.'}</p>
        </div>
      </div>

      <div className="flex items-center gap-2">
        {prev && (
          <button
            onClick={() => onNavigate(prev.id)}
            className="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-medium text-slate-700 bg-surface hover:bg-slate-100 border border-slate-300 rounded-lg transition"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            {prev.label}
          </button>
        )}
        {next ? (
          <button
            onClick={() => onNavigate(next.id)}
            className="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
          >
            Next: {next.label}
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        ) : (
          <button
            onClick={() => onNavigate('overview')}
            className="inline-flex items-center gap-1.5 h-9 px-4 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
          >
            Back to Overview
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        )}
      </div>
    </div>
  );
};
