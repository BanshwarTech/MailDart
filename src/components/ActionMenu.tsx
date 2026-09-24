import React, { useEffect, useRef, useState } from 'react';
import { MoreHorizontal } from 'lucide-react';

export interface ActionMenuItem {
  label: string;
  icon?: React.ElementType;
  onClick: () => void;
  danger?: boolean;
  hidden?: boolean;
  /** Draw a divider above this item */
  separated?: boolean;
}

interface ActionMenuProps {
  items: ActionMenuItem[];
  label?: string;
}

export const ActionMenu: React.FC<ActionMenuProps> = ({ items, label = 'More actions' }) => {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('mousedown', onClick);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onClick);
      document.removeEventListener('keydown', onKey);
    };
  }, [open]);

  const visible = items.filter((i) => !i.hidden);
  if (visible.length === 0) return null;

  return (
    <div ref={rootRef} className="relative">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={label}
        title={label}
        className={`inline-flex items-center justify-center h-9 w-9 rounded-lg border shadow-xs transition ${
          open ? 'bg-slate-100 border-slate-400 text-slate-900' : 'bg-surface border-slate-300 text-slate-600 hover:bg-slate-50 hover:border-slate-400'
        }`}
      >
        <MoreHorizontal className="w-4 h-4" />
      </button>

      {open && (
        <div role="menu" className="absolute right-0 top-full mt-1.5 z-50 w-56 bg-surface rounded-xl ring-1 ring-slate-200/80 shadow-popover p-1 animate-fade-in">
          {visible.map((item) => {
            const Icon = item.icon;
            return (
              <React.Fragment key={item.label}>
                {item.separated && <div className="my-1 h-px bg-slate-100" />}
                <button
                  role="menuitem"
                  type="button"
                  onClick={() => {
                    setOpen(false);
                    item.onClick();
                  }}
                  className={`w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm text-left transition ${
                    item.danger ? 'text-red-600 hover:bg-red-50' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'
                  }`}
                >
                  {Icon && <Icon className={`w-4 h-4 ${item.danger ? 'text-red-500' : 'text-slate-400'}`} />}
                  {item.label}
                </button>
              </React.Fragment>
            );
          })}
        </div>
      )}
    </div>
  );
};
