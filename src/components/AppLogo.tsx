import React from 'react';

interface AppLogoProps {
  size?: 'sm' | 'md' | 'lg';
  showTagline?: boolean;
}

export const AppLogo: React.FC<AppLogoProps> = ({ size = 'md', showTagline = true }) => {
  const isSm = size === 'sm';
  const isLg = size === 'lg';

  const boxSize = isSm ? 'w-8 h-8' : isLg ? 'w-12 h-12' : 'w-10 h-10';
  const iconSize = isSm ? 'w-5 h-5' : isLg ? 'w-7 h-7' : 'w-6 h-6';

  return (
    <div className="flex items-center gap-2.5 select-none">
      {/* Precision Geometric Monogram (Supersonic Dart + Letter M) */}
      <div className="relative group shrink-0">
        <div className="absolute -inset-1 bg-gradient-to-r from-cyan-500 via-blue-600 to-indigo-500 rounded-xl blur-sm opacity-70 group-hover:opacity-100 transition duration-300 animate-pulse" />
        
        <div
          className={`relative flex items-center justify-center ${boxSize} rounded-xl bg-gradient-to-br from-slate-900 via-slate-950 to-blue-950 border border-cyan-500/40 shadow-xl overflow-hidden`}
        >
          {/* Custom SVG Monogram: Modern Mail Dart */}
          <svg
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={`${iconSize} text-white drop-shadow-[0_2px_10px_rgba(6,182,212,0.6)]`}
          >
            {/* Speed trajectory ring */}
            <path
              d="M4 20C4 13.3726 9.37258 8 16 8"
              stroke="#06b6d4"
              strokeWidth="1.5"
              strokeLinecap="round"
              strokeDasharray="2 3"
              opacity="0.6"
            />
            {/* Aerodynamic Dart Envelope Body */}
            <path
              d="M7 16L27 5L20 27L15 18L7 16Z"
              fill="url(#maildart-grad)"
              stroke="#ffffff"
              strokeWidth="1.5"
              strokeLinejoin="round"
            />
            {/* Fold & Target Intersection */}
            <path
              d="M27 5L15 18"
              stroke="#e0f2fe"
              strokeWidth="1.5"
              strokeLinecap="round"
            />
            <path
              d="M15 18L20 27"
              stroke="rgba(255,255,255,0.6)"
              strokeWidth="1.2"
              strokeLinecap="round"
            />
            {/* Target Beacon Dot */}
            <circle cx="27" cy="5" r="2" fill="#38bdf8" />
            <circle cx="27" cy="5" r="1" fill="#ffffff" />
            
            <defs>
              <linearGradient
                id="maildart-grad"
                x1="7"
                y1="5"
                x2="27"
                y2="27"
                gradientUnits="userSpaceOnUse"
              >
                <stop stopColor="#06b6d4" />
                <stop offset="0.5" stopColor="#2563eb" />
                <stop offset="1" stopColor="#4f46e5" />
              </linearGradient>
            </defs>
          </svg>
        </div>
      </div>

      {/* Typography: MailDart PRO */}
      <div>
        <div className="flex items-center gap-2">
          <span
            className={`${
              isSm ? 'text-base' : isLg ? 'text-xl' : 'text-lg'
            } font-extrabold tracking-tight text-white flex items-center font-sans`}
          >
            Mail<span className="bg-gradient-to-r from-cyan-400 via-sky-300 to-indigo-300 bg-clip-text text-transparent">Dart</span>
          </span>
          <span className="text-[9px] font-mono font-bold tracking-wider uppercase bg-cyan-950/70 text-cyan-300 border border-cyan-500/40 px-2 py-0.5 rounded-full shadow-sm">
            PRO ENGINE
          </span>
        </div>
        {showTagline && (
          <p className="text-[11px] text-slate-400 font-medium leading-tight mt-0.5">
            Smart 5-Min Email Stagger & Campaign Dispatcher
          </p>
        )}
      </div>
    </div>
  );
};
