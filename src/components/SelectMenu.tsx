import React, { useEffect, useRef, useState } from 'react';
import { Check, ChevronDown } from 'lucide-react';

export interface SelectMenuOption {
  value: string;
  label: string;
  description?: string;
  badge?: string;
}

interface SelectMenuProps {
  value: string;
  options: SelectMenuOption[];
  onChange: (value: string) => void;
  placeholder?: string;
  /** Monospace description line (e.g. model ids, emails) */
  monoDescription?: boolean;
  className?: string;
  menuClassName?: string;
  'aria-label'?: string;
}

export const SelectMenu: React.FC<SelectMenuProps> = ({
  value,
  options,
  onChange,
  placeholder = 'Select…',
  monoDescription = false,
  className = '',
  menuClassName = '',
  'aria-label': ariaLabel,
}) => {
  const [open, setOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(0);
  const rootRef = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLDivElement>(null);

  const current = options.find((o) => o.value === value);

  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, [open]);

  useEffect(() => {
    if (open) {
      setActiveIndex(Math.max(0, options.findIndex((o) => o.value === value)));
      listRef.current?.focus();
    }
  }, [open, value, options]);

  // Keep the highlighted option visible while using the keyboard
  useEffect(() => {
    if (!open) return;
    listRef.current?.querySelector<HTMLElement>(`[data-index="${activeIndex}"]`)?.scrollIntoView({ block: 'nearest' });
  }, [activeIndex, open]);

  const choose = (v: string) => {
    onChange(v);
    setOpen(false);
  };

  const onKeyDown = (e: React.KeyboardEvent) => {
    if (!options.length) return;
    if (!open) {
      if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
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
      setActiveIndex((i) => (i + 1) % options.length);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setActiveIndex((i) => (i - 1 + options.length) % options.length);
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      choose(options[activeIndex].value);
    } else if (e.key === 'Tab') {
      setOpen(false);
    }
  };

  return (
    <div ref={rootRef} className={`relative ${className}`} onKeyDown={onKeyDown}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-label={ariaLabel}
        className={`w-full h-9 flex items-center gap-2 pl-3 pr-2.5 bg-surface rounded-lg text-left text-sm ring-1 ring-inset transition ${
          open ? 'ring-2 ring-brand-500 outline-4 outline-brand-500/10' : 'ring-slate-300 hover:ring-slate-400'
        }`}
      >
        <span className={`flex-1 min-w-0 truncate ${current ? 'text-slate-900 font-medium' : 'text-slate-400'}`}>
          {current ? current.label : placeholder}
        </span>
        {current?.badge && (
          <span className="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
            {current.badge}
          </span>
        )}
        <ChevronDown className={`w-4 h-4 shrink-0 text-slate-400 transition-transform ${open ? 'rotate-180' : ''}`} />
      </button>

      {open && (
        <div
          ref={listRef}
          role="listbox"
          tabIndex={-1}
          className={`absolute left-0 right-0 top-full mt-1.5 z-50 min-w-[220px] max-h-72 overflow-y-auto bg-surface rounded-xl ring-1 ring-slate-200/80 shadow-popover p-1 animate-fade-in focus:outline-none ${menuClassName}`}
        >
          {options.length === 0 && <p className="px-3 py-2.5 text-sm text-slate-500">No options available</p>}
          {options.map((opt, index) => {
            const selected = opt.value === value;
            const active = index === activeIndex;
            return (
              <div
                key={opt.value}
                data-index={index}
                role="option"
                aria-selected={selected}
                onMouseEnter={() => setActiveIndex(index)}
                onClick={() => choose(opt.value)}
                className={`flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition ${active ? 'bg-slate-100' : ''}`}
              >
                <span className="flex-1 min-w-0">
                  <span className={`flex items-center gap-2 text-sm ${selected ? 'font-semibold text-brand-700' : 'font-medium text-slate-800'}`}>
                    <span className="truncate">{opt.label}</span>
                    {opt.badge && (
                      <span className="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200">
                        {opt.badge}
                      </span>
                    )}
                  </span>
                  {opt.description && (
                    <span className={`block truncate text-slate-500 mt-0.5 ${monoDescription ? 'font-mono text-[10.5px]' : 'text-[11px]'}`}>
                      {opt.description}
                    </span>
                  )}
                </span>
                {selected && <Check className="w-4 h-4 shrink-0 text-brand-700" />}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};
