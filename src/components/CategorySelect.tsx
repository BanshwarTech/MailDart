import React, { useEffect, useRef, useState } from 'react';
import { Check, ChevronDown } from 'lucide-react';
import { FestivalType } from '../types';

interface CategoryOption {
  value: FestivalType;
  emoji: string;
  label: string;
  hint: string;
}

const GROUPS: { title: string; options: CategoryOption[] }[] = [
  {
    title: 'Festivals & greetings',
    options: [
      { value: 'diwali', emoji: '🪔', label: 'Diwali Festival', hint: 'Festival of lights wishes & offers' },
      { value: 'eid', emoji: '🌙', label: 'Eid Mubarak', hint: 'Warm Eid greetings' },
      { value: 'christmas', emoji: '🎄', label: 'Christmas & Holiday', hint: 'Season’s greetings' },
      { value: 'newyear', emoji: '🎉', label: 'New Year Greeting', hint: 'Welcome the new year' },
      { value: 'holi', emoji: '🎨', label: 'Holi Festival', hint: 'Festival of colours' },
    ],
  },
  {
    title: 'Business campaigns',
    options: [
      { value: 'newsletter', emoji: '📰', label: 'Company Newsletter', hint: 'Monthly updates & digests' },
      { value: 'product_launch', emoji: '🚀', label: 'New Product Launch', hint: 'Announce a feature or product' },
      { value: 'followup_reminder', emoji: '⏰', label: 'Follow-up & Reminder', hint: 'Payments, renewals, nudges' },
      { value: 'welcome_onboarding', emoji: '👋', label: 'Welcome & Onboarding', hint: 'Greet new customers' },
      { value: 'custom', emoji: '🎯', label: 'General / Custom Campaign', hint: 'Anything else' },
    ],
  },
];

const ALL = GROUPS.flatMap((g) => g.options);

interface CategorySelectProps {
  value: FestivalType;
  onChange: (value: FestivalType) => void;
}

export const CategorySelect: React.FC<CategorySelectProps> = ({ value, onChange }) => {
  const [open, setOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(0);
  const rootRef = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLDivElement>(null);

  const current = ALL.find((o) => o.value === value) ?? {
    value,
    emoji: '🎯',
    label: value.replace(/_/g, ' '),
    hint: '',
  };

  // Close on outside click
  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, [open]);

  // Start keyboard focus on the selected option
  useEffect(() => {
    if (open) {
      setActiveIndex(Math.max(0, ALL.findIndex((o) => o.value === value)));
      listRef.current?.focus();
    }
  }, [open, value]);

  const choose = (v: FestivalType) => {
    onChange(v);
    setOpen(false);
  };

  const onKeyDown = (e: React.KeyboardEvent) => {
    if (!open) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setOpen(true);
      }
      return;
    }
    if (e.key === 'Escape') {
      e.preventDefault();
      setOpen(false);
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      setActiveIndex((i) => (i + 1) % ALL.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((i) => (i - 1 + ALL.length) % ALL.length);
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      choose(ALL[activeIndex].value);
    }
  };

  return (
    <div ref={rootRef} className="relative" onKeyDown={onKeyDown}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        aria-haspopup="listbox"
        aria-expanded={open}
        title="Change campaign category"
        className={`inline-flex items-center gap-1.5 h-8 pl-2.5 pr-2 rounded-lg text-xs font-semibold ring-1 ring-inset transition ${
          open
            ? 'bg-brand-600 text-white ring-brand-600 shadow-button'
            : 'bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100'
        }`}
      >
        <span className="text-sm leading-none">{current.emoji}</span>
        <span className="capitalize">{current.label}</span>
        <ChevronDown className={`w-3.5 h-3.5 transition-transform ${open ? 'rotate-180' : ''}`} />
      </button>

      {open && (
        <div
          ref={listRef}
          role="listbox"
          tabIndex={-1}
          aria-activedescendant={`cat-${ALL[activeIndex]?.value}`}
          className="absolute left-0 top-full mt-2 z-40 w-[560px] max-w-[calc(100vw-2rem)] bg-surface rounded-xl ring-1 ring-slate-200/80 shadow-popover p-1.5 max-h-[min(70vh,520px)] overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-x-1 animate-fade-in focus:outline-none"
        >
          {GROUPS.map((group, gi) => (
            <div key={group.title} className={gi > 0 ? 'max-sm:mt-1 max-sm:pt-1 max-sm:border-t sm:border-l border-slate-100 sm:pl-1' : ''}>
              <p className="px-2.5 pt-1.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">{group.title}</p>
              {group.options.map((opt) => {
                const index = ALL.indexOf(opt);
                const selected = opt.value === value;
                const active = index === activeIndex;
                return (
                  <div
                    key={opt.value}
                    id={`cat-${opt.value}`}
                    role="option"
                    aria-selected={selected}
                    onMouseEnter={() => setActiveIndex(index)}
                    onClick={() => choose(opt.value)}
                    className={`flex items-center gap-2.5 px-2 py-1.5 rounded-lg cursor-pointer transition ${
                      active ? 'bg-slate-100' : ''
                    }`}
                  >
                    <span
                      className={`w-7 h-7 shrink-0 rounded-md flex items-center justify-center text-sm ${
                        selected ? 'bg-brand-50 ring-1 ring-inset ring-brand-200' : 'bg-slate-50 ring-1 ring-inset ring-slate-200/80'
                      }`}
                    >
                      {opt.emoji}
                    </span>
                    <span className="flex-1 min-w-0">
                      <span className={`block text-[13px] leading-tight truncate ${selected ? 'font-semibold text-brand-700' : 'font-medium text-slate-800'}`}>
                        {opt.label}
                      </span>
                      <span className="block text-[11px] leading-tight text-slate-500 truncate mt-0.5">{opt.hint}</span>
                    </span>
                    {selected && <Check className="w-4 h-4 shrink-0 text-brand-700" />}
                  </div>
                );
              })}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
