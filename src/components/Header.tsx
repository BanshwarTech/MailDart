import React, { useState, useRef, useEffect } from 'react';
import { Settings, Pause, CheckCircle2, User as UserIcon, LogOut, ShieldCheck, ChevronDown, Menu } from 'lucide-react';
import { CampaignState, SmtpConfig } from '../types';
import { getSmtpStatus } from '../utils/campaignHelper';
import { AppLogo } from './AppLogo';
import { useAuth } from '../context/AuthContext';

interface HeaderProps {
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
  title: string;
  subtitle?: string;
  onOpenMenu: () => void;
  onOpenHome: () => void;
  onOpenSmtpSettings: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  campaign,
  smtpConfig,
  title,
  subtitle,
  onOpenMenu,
  onOpenHome,
  onOpenSmtpSettings,
}) => {
  const { currentUser, openAuthModal, logout } = useAuth();
  const [userDropdownOpen, setUserDropdownOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setUserDropdownOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const smtpStatus = getSmtpStatus(smtpConfig);
  const smtpLabel =
    smtpStatus === 'live'
      ? smtpConfig.fromEmail || smtpConfig.username
      : smtpStatus === 'incomplete'
      ? 'SMTP not configured'
      : 'Sandbox mode';

  const getStatusBadge = () => {
    switch (campaign.status) {
      case 'running':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-brand-50 ring-1 ring-inset ring-brand-200 text-brand-700 text-xs font-semibold rounded-full">
            <span className="relative flex w-2 h-2">
              <span className="absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75 animate-ping" />
              <span className="relative inline-flex w-2 h-2 rounded-full bg-brand-500" />
            </span>
            Running 5m Dispatch
          </span>
        );
      case 'paused':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 ring-1 ring-inset ring-amber-200 text-amber-800 text-xs font-semibold rounded-full">
            <Pause className="w-3 h-3" />
            Dispatch Paused
          </span>
        );
      case 'completed':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-brand-50 ring-1 ring-inset ring-brand-200 text-brand-700 text-xs font-semibold rounded-full">
            <CheckCircle2 className="w-3 h-3" />
            Campaign Completed
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 ring-1 ring-inset ring-slate-200 text-slate-600 text-xs font-medium rounded-full">
            <span className="w-1.5 h-1.5 rounded-full bg-slate-400" />
            Draft / Ready
          </span>
        );
    }
  };

  const iconButton =
    'items-center gap-1.5 h-9 px-3 text-xs font-medium text-slate-700 bg-surface hover:bg-slate-50 ring-1 ring-inset ring-slate-200 hover:ring-slate-300 rounded-lg shadow-xs transition';

  return (
    <header className="sticky top-0 z-20 bg-surface/75 backdrop-blur-xl border-b border-slate-200/80">
      <div className="h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
        {/* Left: mobile menu + page title */}
        <div className="flex items-center gap-3 min-w-0">
          <button
            onClick={onOpenMenu}
            className="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
            aria-label="Open menu"
          >
            <Menu className="w-5 h-5" />
          </button>
          <button onClick={onOpenHome} className="lg:hidden" title="MailDart home">
            <AppLogo size="sm" showTagline={false} />
          </button>
          <div className="hidden lg:block min-w-0">
            <h1 className="text-[15px] font-semibold text-slate-900 leading-tight truncate">{title}</h1>
            {subtitle && <p className="text-xs text-slate-500 truncate">{subtitle}</p>}
          </div>
        </div>

        {/* Right: status + actions */}
        <div className="flex items-center gap-2">
          <button
            onClick={onOpenSmtpSettings}
            className={`${iconButton} inline-flex max-w-[220px]`}
            title="Configure User SMTP Delivery Account"
          >
            <span className={`w-2 h-2 rounded-full shrink-0 ${smtpStatus === 'live' ? 'bg-brand-500' : 'bg-amber-500'}`} />
            <Settings className="w-4 h-4 text-slate-400" />
            <span className={`hidden sm:inline text-[11px] truncate ${smtpStatus === 'live' ? 'font-mono text-slate-900' : 'font-medium text-amber-700'}`}>
              {smtpLabel}
            </span>
          </button>

          {/* User Auth Section */}
          {currentUser ? (
            <div className="relative" ref={dropdownRef}>
              <button
                type="button"
                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                className="flex items-center gap-2 h-9 pl-1 pr-2 rounded-full hover:bg-slate-100 transition"
              >
                {currentUser.photoURL ? (
                  <img
                    src={currentUser.photoURL}
                    alt={currentUser.displayName || 'User'}
                    className="w-7 h-7 rounded-full object-cover ring-2 ring-surface shadow-sm"
                  />
                ) : (
                  <div className="w-7 h-7 rounded-full bg-gradient-to-br from-brand-500 to-accent-600 text-white text-xs font-semibold flex items-center justify-center ring-2 ring-surface shadow-sm">
                    {(currentUser.displayName || currentUser.email || 'U')[0].toUpperCase()}
                  </div>
                )}
                <span className="hidden md:block max-w-[110px] truncate text-sm font-medium text-slate-700">
                  {currentUser.displayName || currentUser.email?.split('@')[0]}
                </span>
                <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
              </button>

              {userDropdownOpen && (
                <div className="absolute right-0 mt-2 w-64 bg-surface ring-1 ring-slate-200/80 rounded-xl shadow-popover p-1.5 z-50 animate-fade-in">
                  <div className="px-3 pt-2 pb-3 mb-1 border-b border-slate-100">
                    <p className="text-sm font-semibold text-slate-900 truncate">
                      {currentUser.displayName || 'User'}
                    </p>
                    <p className="text-xs text-slate-500 truncate">
                      {currentUser.email}
                    </p>
                    <div className="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-50 ring-1 ring-inset ring-brand-200 text-[11px] font-medium text-brand-700">
                      <ShieldCheck className="w-3 h-3" />
                      <span>Cloud Sync Active (Firestore)</span>
                    </div>
                  </div>

                  <button
                    onClick={() => {
                      setUserDropdownOpen(false);
                      onOpenSmtpSettings();
                    }}
                    className="w-full text-left px-3 py-2 text-sm text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg flex items-center gap-2.5 transition"
                  >
                    <Settings className="w-4 h-4 text-slate-400" />
                    <span>My SMTP & API Settings</span>
                  </button>

                  <button
                    onClick={async () => {
                      setUserDropdownOpen(false);
                      await logout();
                    }}
                    className="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg flex items-center gap-2.5 transition"
                  >
                    <LogOut className="w-4 h-4" />
                    <span>Log Out</span>
                  </button>
                </div>
              )}
            </div>
          ) : (
            <button
              type="button"
              onClick={() => openAuthModal('login')}
              className="inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-500 rounded-lg shadow-button transition"
            >
              <UserIcon className="w-4 h-4" />
              <span className="hidden sm:inline">Log In / Sign Up</span>
            </button>
          )}
        </div>
      </div>
    </header>
  );
};
