import React, { useState, useRef, useEffect } from 'react';
import { Mail, Settings, HelpCircle, Pause, CheckCircle2, User as UserIcon, LogOut, ShieldCheck, ChevronDown } from 'lucide-react';
import { CampaignState, SmtpConfig } from '../types';
import { AppLogo } from './AppLogo';
import { useAuth } from '../context/AuthContext';

interface HeaderProps {
  campaign: CampaignState;
  smtpConfig: SmtpConfig;
  onOpenGuide: () => void;
  onOpenSmtpSettings: () => void;
  onOpenTestEmail: () => void;
}

export const Header: React.FC<HeaderProps> = ({
  campaign,
  smtpConfig,
  onOpenGuide,
  onOpenSmtpSettings,
  onOpenTestEmail,
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

  const getStatusBadge = () => {
    switch (campaign.status) {
      case 'running':
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-semibold rounded-full animate-pulse">
            <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
            Running 5m Dispatch
          </span>
        );
      case 'paused':
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-amber-500/15 border border-amber-500/30 text-amber-400 text-xs font-semibold rounded-full">
            <Pause className="w-3 h-3" />
            Dispatch Paused
          </span>
        );
      case 'completed':
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-purple-500/15 border border-purple-500/30 text-purple-400 text-xs font-semibold rounded-full">
            <CheckCircle2 className="w-3 h-3" />
            Campaign Completed
          </span>
        );
      default:
        return (
          <span className="flex items-center gap-1.5 px-3 py-1 bg-slate-800 border border-slate-700 text-slate-400 text-xs font-medium rounded-full">
            Draft / Ready
          </span>
        );
    }
  };

  return (
    <header className="sticky top-0 z-40 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 px-4 lg:px-8 py-3.5 transition-all">
      <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        
        {/* Brand & All-Purpose Logo */}
        <div className="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
          <AppLogo />

          <div className="sm:hidden">
            {getStatusBadge()}
          </div>
        </div>

        {/* Center / Status */}
        <div className="hidden sm:flex items-center gap-3">
          {getStatusBadge()}
          
          <div className="flex items-center gap-1.5 text-xs text-slate-400 bg-slate-850 px-2.5 py-1 rounded-lg border border-slate-800">
            <span className="text-slate-500">Company:</span>
            <span className="text-white font-medium truncate max-w-[140px]">
              {campaign.companyName || 'Not Set'}
            </span>
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
          
          <button
            onClick={onOpenGuide}
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-750 border border-slate-700 rounded-xl transition"
            title="Read Guide in Hindi / English"
          >
            <HelpCircle className="w-3.5 h-3.5 text-amber-400" />
            <span className="hidden md:inline">गाइड (Guide)</span>
          </button>

          <button
            onClick={onOpenTestEmail}
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-200 bg-slate-800 hover:bg-slate-750 border border-slate-700 rounded-xl transition"
            title="Send a single test email immediately"
          >
            <Mail className="w-3.5 h-3.5 text-sky-400" />
            <span className="hidden md:inline">Test Send</span>
          </button>

          <button
            onClick={onOpenSmtpSettings}
            className={`flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-xl border transition ${
              smtpConfig.enabled && (smtpConfig.fromEmail || smtpConfig.username)
                ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/50 hover:bg-emerald-500/20'
                : 'bg-amber-500/10 text-amber-300 border-amber-500/50 hover:bg-amber-500/20'
            }`}
            title="Configure User SMTP Delivery Account"
          >
            <Settings className="w-3.5 h-3.5" />
            <div className="flex flex-col text-left">
              <span className="text-[9px] uppercase tracking-wider text-slate-400">SMTP:</span>
              <span className="font-mono text-[11px] max-w-[140px] truncate font-bold text-white">
                {smtpConfig.enabled
                  ? (smtpConfig.fromEmail || smtpConfig.username || 'Real SMTP Active')
                  : 'Sandbox'}
              </span>
            </div>
          </button>

          {/* User Auth Section */}
          {currentUser ? (
            <div className="relative" ref={dropdownRef}>
              <button
                type="button"
                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                className="flex items-center gap-2 px-2.5 py-1.5 rounded-xl bg-slate-800/90 hover:bg-slate-750 border border-slate-700 text-xs font-medium text-slate-200 transition"
              >
                {currentUser.photoURL ? (
                  <img
                    src={currentUser.photoURL}
                    alt={currentUser.displayName || 'User'}
                    className="w-5 h-5 rounded-full object-cover border border-cyan-400/50"
                  />
                ) : (
                  <div className="w-5 h-5 rounded-full bg-gradient-to-tr from-cyan-500 to-blue-600 text-white text-[10px] font-bold flex items-center justify-center">
                    {(currentUser.displayName || currentUser.email || 'U')[0].toUpperCase()}
                  </div>
                )}
                <span className="max-w-[100px] truncate font-semibold text-white">
                  {currentUser.displayName || currentUser.email?.split('@')[0]}
                </span>
                <ChevronDown className="w-3 h-3 text-slate-400" />
              </button>

              {userDropdownOpen && (
                <div className="absolute right-0 mt-2 w-64 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-3 space-y-3 z-50 animate-fade-in">
                  <div className="px-1 py-1 border-b border-slate-800/80">
                    <p className="text-xs font-semibold text-white truncate">
                      {currentUser.displayName || 'User'}
                    </p>
                    <p className="text-[11px] text-slate-400 truncate">
                      {currentUser.email}
                    </p>
                    <div className="mt-1 flex items-center gap-1 text-[10px] text-emerald-400">
                      <ShieldCheck className="w-3 h-3 text-emerald-400" />
                      <span>Cloud Sync Active (Firestore)</span>
                    </div>
                  </div>

                  <button
                    onClick={() => {
                      setUserDropdownOpen(false);
                      onOpenSmtpSettings();
                    }}
                    className="w-full text-left px-2.5 py-1.5 text-xs text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg flex items-center gap-2 transition"
                  >
                    <Settings className="w-3.5 h-3.5 text-cyan-400" />
                    <span>My SMTP & API Settings</span>
                  </button>

                  <button
                    onClick={async () => {
                      setUserDropdownOpen(false);
                      await logout();
                    }}
                    className="w-full text-left px-2.5 py-1.5 text-xs text-rose-300 hover:text-rose-200 hover:bg-rose-950/40 rounded-lg flex items-center gap-2 transition"
                  >
                    <LogOut className="w-3.5 h-3.5 text-rose-400" />
                    <span>लॉगआउट करें (Sign Out)</span>
                  </button>
                </div>
              )}
            </div>
          ) : (
            <div className="flex items-center gap-1.5">
              <button
                type="button"
                onClick={() => openAuthModal('login')}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 rounded-xl transition shadow-md shadow-cyan-500/20"
              >
                <UserIcon className="w-3.5 h-3.5" />
                <span>लॉगिन / साइन-अप</span>
              </button>
            </div>
          )}

        </div>

      </div>
    </header>
  );
};
