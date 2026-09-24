import React from 'react';

interface AppLogoProps {
  size?: 'sm' | 'md' | 'lg';
  showTagline?: boolean;
}

export const AppLogo: React.FC<AppLogoProps> = ({ size = 'md', showTagline = true }) => {
  const isSm = size === 'sm';
  const isLg = size === 'lg';

  const boxSize = isSm ? 'w-8 h-8 rounded-[10px]' : isLg ? 'w-12 h-12 rounded-2xl' : 'w-10 h-10 rounded-xl';
  const iconSize = isSm ? 'w-[21px] h-[21px]' : isLg ? 'w-8 h-8' : 'w-[26px] h-[26px]';
  const wordSize = isSm ? 'text-[17px]' : isLg ? 'text-2xl' : 'text-lg';

  return (
    <div className="flex items-center gap-2.5 select-none">
      {/* Monogram: mail dart on a brand gradient tile */}
      <div
        className={`relative shrink-0 flex items-center justify-center ${boxSize} bg-gradient-to-br from-brand-500 via-brand-600 to-accent-700 shadow-button overflow-hidden`}
      >
        {/* Soft top highlight */}
        <span className="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/25 to-transparent" />
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className={`relative ${iconSize}`}>
          {/* Motion trail */}
          <path d="M2.5 15.5h3.5M3.5 19h4.5" stroke="white" strokeOpacity="0.55" strokeWidth="1.6" strokeLinecap="round" />
          {/* Dart body */}
          <path d="M21 3 9.6 21l-2.4-7.8L21 3Z" fill="white" />
          <path d="M21 3 7.2 13.2 2.8 11.4 21 3Z" fill="white" fillOpacity="0.8" />
          {/* Fold line */}
          <path d="M21 3 7.2 13.2" style={{ stroke: 'var(--color-brand-600)' }} strokeWidth="1.2" strokeLinecap="round" />
        </svg>
      </div>

      {/* Wordmark */}
      <div className="leading-none">
        <div className="flex items-center gap-1.5">
          <span className={`${wordSize} font-bold tracking-tight text-slate-900 font-sans`}>
            Mail<span className="text-brand-700">Dart</span>
          </span>
          <span className="text-[9px] font-bold tracking-[0.12em] uppercase text-brand-700 bg-brand-50 ring-1 ring-inset ring-brand-200 px-1.5 py-[3px] rounded-md">
            Pro
          </span>
        </div>
        {showTagline && (
          <p className="text-[11px] text-slate-500 font-medium mt-1">
            Smart 5-Min Email Stagger & Campaign Dispatcher
          </p>
        )}
      </div>
    </div>
  );
};
