import React, { useState } from 'react';
import {
  setPersistence,
  browserLocalPersistence,
  browserSessionPersistence,
  sendPasswordResetEmail,
} from 'firebase/auth';
import { Eye, EyeOff, AlertCircle, CheckCircle2, Wand2, Check, Timer, FileSpreadsheet, ShieldCheck } from 'lucide-react';
import { auth } from '../firebase';
import { useAuth } from '../context/AuthContext';
import { generateStrongPassword, checkPasswordRules, passwordStrength } from '../utils/password';

type Mode = 'login' | 'register';

interface AuthPageProps {
  mode: Mode;
  onSwitchMode: (mode: Mode) => void;
  onBackHome: () => void;
}

const inputClass =
  'w-full h-[42px] px-3.5 text-sm bg-surface border border-slate-300 rounded-xl text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition';
const labelClass = 'block text-sm font-medium text-slate-800 mb-2';
const Required = () => <span className="text-red-500">*</span>;

const friendlyAuthError = (err: unknown, mode: Mode): string => {
  const e = err as { code?: string; message?: string };
  switch (e.code) {
    case 'auth/invalid-credential':
    case 'auth/wrong-password':
    case 'auth/user-not-found':
      return 'Incorrect email or password. Please check and try again.';
    case 'auth/email-already-in-use':
      return 'This email is already registered. Please sign in instead.';
    case 'auth/invalid-email':
      return 'Please enter a valid email address.';
    case 'auth/weak-password':
      return 'Please choose a stronger password.';
    case 'auth/too-many-requests':
      return 'Too many attempts. Please wait a moment and try again.';
    case 'auth/network-request-failed':
      return 'Network error. Please check your internet connection.';
    default:
      return e.message || (mode === 'login' ? 'Sign in failed. Please try again.' : 'Sign up failed. Please try again.');
  }
};

export const AuthPage: React.FC<AuthPageProps> = ({ mode, onSwitchMode, onBackHome }) => {
  const { loginWithEmail, registerWithEmail, loginWithGoogle } = useAuth();
  const isLogin = mode === 'login';

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [keepLoggedIn, setKeepLoggedIn] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [generated, setGenerated] = useState(false);

  const rules = checkPasswordRules(password);
  const strength = passwordStrength(password);

  const switchMode = (m: Mode) => {
    setError(null);
    setNotice(null);
    setPassword('');
    setConfirm('');
    onSwitchMode(m);
  };

  const handleGenerate = async () => {
    const pw = generateStrongPassword();
    setPassword(pw);
    setConfirm(pw);
    setShowPassword(true);
    try {
      await navigator.clipboard.writeText(pw);
      setGenerated(true);
      setTimeout(() => setGenerated(false), 2500);
    } catch {
      // Clipboard unavailable: password is still filled in and visible
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setNotice(null);

    if (!email.trim() || !password) return setError('Please enter your email and password.');
    if (!isLogin) {
      if (!name.trim()) return setError('Please enter your full name.');
      if (!Object.values(rules).every(Boolean)) {
        return setError('Password must be at least 8 characters, with uppercase, lowercase, a number and a symbol.');
      }
      if (password !== confirm) return setError('Passwords do not match.');
    }

    setLoading(true);
    try {
      await setPersistence(auth, keepLoggedIn ? browserLocalPersistence : browserSessionPersistence);
      if (isLogin) await loginWithEmail(email, password);
      else await registerWithEmail(email, password, name);
    } catch (err) {
      console.error('Auth error', err);
      setError(friendlyAuthError(err, mode));
    } finally {
      setLoading(false);
    }
  };

  const handleGoogle = async () => {
    setError(null);
    setLoading(true);
    try {
      await loginWithGoogle();
    } catch (err) {
      const e = err as { code?: string };
      if (e.code !== 'auth/popup-closed-by-user') setError(friendlyAuthError(err, mode));
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = async () => {
    setError(null);
    setNotice(null);
    if (!email.trim()) return setError('Enter your email above first, then click “Forgot password?”.');
    try {
      await sendPasswordResetEmail(auth, email.trim());
      setNotice(`A password reset link has been sent to ${email.trim()}.`);
    } catch (err) {
      setError(friendlyAuthError(err, mode));
    }
  };

  return (
    <div className="min-h-screen bg-page grid lg:grid-cols-2">
      {/* ---------- Form side ---------- */}
      <div className="flex flex-col px-5 sm:px-10">
        <div className="flex-1 flex items-center justify-center py-12">
          <div className="w-full max-w-[460px] animate-fade-in" key={mode}>
            <h1 className="text-4xl font-bold tracking-tight text-slate-900">{isLogin ? 'Sign In' : 'Sign Up'}</h1>
            <p className="mt-2 text-slate-500">
              {isLogin ? 'Enter your email and password to sign in!' : 'Enter your details to create your MailDart account!'}
            </p>

            <button
              type="button"
              onClick={handleGoogle}
              disabled={loading}
              className="mt-7 w-full h-[42px] inline-flex items-center justify-center gap-3 rounded-xl bg-slate-100 hover:bg-slate-200/80 ring-1 ring-inset ring-slate-300 text-sm font-medium text-slate-900 transition disabled:opacity-60"
            >
              <svg viewBox="0 0 48 48" className="w-5 h-5" aria-hidden>
                <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z" />
                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
                <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z" />
                <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.6l6.2 5.2C36.9 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z" />
              </svg>
              {isLogin ? 'Sign in with Google' : 'Sign up with Google'}
            </button>

            <div className="my-6 flex items-center gap-4">
              <div className="h-px flex-1 bg-slate-200" />
              <span className="text-xs text-slate-400">Or</span>
              <div className="h-px flex-1 bg-slate-200" />
            </div>

            {error && (
              <div className="mb-5 flex items-start gap-2.5 rounded-xl bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-sm text-red-700">
                <AlertCircle className="w-4 h-4 mt-0.5 shrink-0" />
                {error}
              </div>
            )}
            {notice && (
              <div className="mb-5 flex items-start gap-2.5 rounded-xl bg-sky-50 ring-1 ring-inset ring-sky-200 px-4 py-3 text-sm text-sky-700">
                <CheckCircle2 className="w-4 h-4 mt-0.5 shrink-0" />
                {notice}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5" noValidate>
              {isLogin ? (
                <div>
                  <label className={labelClass} htmlFor="auth-email">
                    Email <Required />
                  </label>
                  <input id="auth-email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="yourname@gmail.com" className={inputClass} />
                </div>
              ) : (
                <div className="grid sm:grid-cols-2 gap-4">
                  <div>
                    <label className={labelClass} htmlFor="auth-name">
                      Full Name <Required />
                    </label>
                    <input id="auth-name" type="text" autoComplete="name" value={name} onChange={(e) => setName(e.target.value)} placeholder="Enter your name" className={inputClass} />
                  </div>
                  <div>
                    <label className={labelClass} htmlFor="auth-email">
                      Email <Required />
                    </label>
                    <input id="auth-email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="yourname@gmail.com" className={inputClass} />
                  </div>
                </div>
              )}

              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="block text-sm font-medium text-slate-800" htmlFor="auth-password">
                    Password <Required />
                  </label>
                  {!isLogin && (
                    <button type="button" onClick={handleGenerate} className="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:text-brand-800 transition">
                      {generated ? <Check className="w-3.5 h-3.5" /> : <Wand2 className="w-3.5 h-3.5" />}
                      {generated ? 'Generated & copied' : 'Generate Password'}
                    </button>
                  )}
                </div>
                <div className="relative">
                  <input
                    id="auth-password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete={isLogin ? 'current-password' : 'new-password'}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Enter your password"
                    className={`${inputClass} pr-12 ${showPassword && password ? 'font-mono tracking-wide' : ''}`}
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((v) => !v)}
                    className="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition"
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    title={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
                {!isLogin && (
                  <>
                    {password ? (
                      <div className="mt-2.5 flex items-center gap-3">
                        <div className="flex-1 grid grid-cols-3 gap-1.5">
                          {[1, 2, 3].map((n) => (
                            <span
                              key={n}
                              className={`h-1.5 rounded-full ${
                                n <= strength.level ? (strength.level === 1 ? 'bg-red-500' : strength.level === 2 ? 'bg-amber-500' : 'bg-[#A1B2C4]') : 'bg-slate-200'
                              }`}
                            />
                          ))}
                        </div>
                        <span className="text-xs font-semibold text-slate-600">{strength.label}</span>
                      </div>
                    ) : null}
                    <p className="mt-2 text-xs text-slate-400">Must be at least 8 characters, with uppercase, lowercase, a number and a symbol.</p>
                  </>
                )}
              </div>

              {!isLogin && (
                <div>
                  <label className={labelClass} htmlFor="auth-confirm">
                    Confirm Password <Required />
                  </label>
                  <input
                    id="auth-confirm"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="new-password"
                    value={confirm}
                    onChange={(e) => setConfirm(e.target.value)}
                    placeholder="Confirm your password"
                    className={`${inputClass} ${confirm && confirm !== password ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : ''}`}
                  />
                  {confirm && confirm !== password && <p className="mt-1.5 text-xs text-red-600">Passwords do not match.</p>}
                </div>
              )}

              {isLogin && (
                <div className="flex items-center justify-between">
                  <label className="inline-flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer select-none">
                    <input type="checkbox" checked={keepLoggedIn} onChange={(e) => setKeepLoggedIn(e.target.checked)} className="w-4 h-4 rounded accent-[#557392]" />
                    Keep me logged in
                  </label>
                  <button type="button" onClick={handleForgotPassword} className="text-sm font-medium text-brand-700 hover:text-brand-800 transition">
                    Forgot password?
                  </button>
                </div>
              )}

              <button
                type="submit"
                disabled={loading}
                className="w-full h-[42px] inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-sm font-semibold text-white shadow-button transition disabled:opacity-60"
              >
                {loading && <span className="w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin" />}
                {isLogin ? 'Sign In' : 'Create Account'}
              </button>
            </form>

            <p className="mt-6 text-sm text-slate-500">
              {isLogin ? 'Don’t have an account?' : 'Already have an account?'}{' '}
              <button onClick={() => switchMode(isLogin ? 'register' : 'login')} className="font-semibold text-brand-700 hover:text-brand-800 transition">
                {isLogin ? 'Sign Up' : 'Sign In'}
              </button>
            </p>
          </div>
        </div>
      </div>

      {/* ---------- Brand side ---------- */}
      <div className="hidden lg:flex lg:sticky lg:top-0 lg:h-screen relative overflow-hidden items-center justify-center bg-gradient-to-br from-[#112A46] via-[#0E2440] to-[#1B3B5A] border-l border-slate-200/80">
        {/* Grid of squares with a few lit tiles */}
        <div className="absolute inset-0 [background-image:linear-gradient(rgb(122_140_166/0.14)_1px,transparent_1px),linear-gradient(90deg,rgb(122_140_166/0.14)_1px,transparent_1px)] [background-size:64px_64px] [mask-image:radial-gradient(ellipse_at_center,transparent_22%,black_75%)]" aria-hidden />
        {[
          'top-[64px] right-[128px]',
          'top-[128px] right-[192px]',
          'bottom-[128px] left-[128px]',
          'bottom-[64px] left-[64px]',
          'top-[256px] left-[64px]',
        ].map((pos) => (
          <span key={pos} className={`absolute ${pos} w-16 h-16 bg-[#34506D]/35`} aria-hidden />
        ))}
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[520px] h-[520px] rounded-full bg-[radial-gradient(closest-side,rgb(85_115_146/0.35),transparent)]" aria-hidden />

        <div className="relative max-w-md px-8 text-center">
          <div className="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-[#6A87A6] to-[#34506D] flex items-center justify-center shadow-[0_20px_50px_-15px_rgb(0_0_0/0.6)] ring-8 ring-[#557392]/15">
            <svg viewBox="0 0 24 24" fill="none" className="w-9 h-9" aria-hidden>
              <path d="M2.5 15.5h3.5M3.5 19h4.5" stroke="white" strokeOpacity="0.6" strokeWidth="1.6" strokeLinecap="round" />
              <path d="M21 3 9.6 21l-2.4-7.8L21 3Z" fill="white" />
              <path d="M21 3 7.2 13.2 2.8 11.4 21 3Z" fill="white" fillOpacity="0.8" />
            </svg>
          </div>
          <h2 className="mt-7 text-3xl font-bold tracking-tight text-[#F2F6F8]">MailDart Pro</h2>
          <p className="mt-3 text-[#A1B2C4] leading-relaxed">
            Smart, staggered email campaigns that reach the inbox, sent from your own email account and personalised for every recipient.
          </p>
          <ul className="mt-8 space-y-3 text-left inline-block">
            {[
              { icon: Timer, text: 'One email at a time, anti-spam sending' },
              { icon: FileSpreadsheet, text: 'Import recipients from Excel in seconds' },
              { icon: ShieldCheck, text: 'Practise safely in Sandbox mode' },
            ].map(({ icon: Icon, text }) => (
              <li key={text} className="flex items-center gap-3 text-sm text-[#E1E8F0]">
                <span className="w-8 h-8 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 flex items-center justify-center">
                  <Icon className="w-4 h-4 text-[#C4D2E1]" />
                </span>
                {text}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
};
