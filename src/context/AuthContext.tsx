import React, { createContext, useContext, useEffect, useState } from 'react';
import {
  type User,
  signInWithEmailAndPassword,
  createUserWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  signInWithPopup,
  updateProfile,
} from 'firebase/auth';
import { doc, getDoc, setDoc } from 'firebase/firestore';
import { auth, db, googleProvider, syncUserProfile, testFirestoreConnection } from '../firebase';
import type { SmtpConfig } from '../types';

interface AuthContextType {
  currentUser: User | null;
  loading: boolean;
  isAuthModalOpen: boolean;
  openAuthModal: (mode?: 'login' | 'register') => void;
  closeAuthModal: () => void;
  authModalMode: 'login' | 'register';
  setAuthModalMode: (mode: 'login' | 'register') => void;
  loginWithEmail: (email: string, pass: string) => Promise<void>;
  registerWithEmail: (email: string, pass: string, name?: string) => Promise<void>;
  loginWithGoogle: () => Promise<void>;
  logout: () => Promise<void>;
  loadUserSmtp: () => Promise<SmtpConfig | null>;
  saveUserSmtp: (config: SmtpConfig) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [authModalMode, setAuthModalMode] = useState<'login' | 'register'>('login');

  useEffect(() => {
    testFirestoreConnection();

    const unsubscribe = onAuthStateChanged(auth, async (user) => {
      setCurrentUser(user);
      if (user) {
        await syncUserProfile(user);
      }
      setLoading(false);
    });

    return () => unsubscribe();
  }, []);

  const openAuthModal = (mode: 'login' | 'register' = 'login') => {
    setAuthModalMode(mode);
    setIsAuthModalOpen(true);
  };

  const closeAuthModal = () => {
    setIsAuthModalOpen(false);
  };

  const loginWithEmail = async (email: string, pass: string) => {
    const cred = await signInWithEmailAndPassword(auth, email.trim(), pass);
    await syncUserProfile(cred.user);
    closeAuthModal();
  };

  const registerWithEmail = async (email: string, pass: string, name?: string) => {
    const cred = await createUserWithEmailAndPassword(auth, email.trim(), pass);
    if (name && cred.user) {
      await updateProfile(cred.user, { displayName: name.trim() });
    }
    await syncUserProfile(cred.user);
    closeAuthModal();
  };

  const loginWithGoogle = async () => {
    const cred = await signInWithPopup(auth, googleProvider);
    if (cred.user) {
      await syncUserProfile(cred.user);
    }
    closeAuthModal();
  };

  const logout = async () => {
    await signOut(auth);
  };

  // Load private SMTP credentials for the logged-in user
  const loadUserSmtp = async (): Promise<SmtpConfig | null> => {
    if (!auth.currentUser) return null;
    try {
      const ref = doc(db, 'users', auth.currentUser.uid, 'settings', 'smtp');
      const snap = await getDoc(ref);
      if (snap.exists()) {
        return snap.data() as SmtpConfig;
      }
    } catch (e) {
      console.warn('Could not load SMTP from Firestore', e);
    }
    return null;
  };

  // Save private SMTP credentials for the logged-in user
  const saveUserSmtp = async (config: SmtpConfig): Promise<void> => {
    if (!auth.currentUser) return;
    try {
      const ref = doc(db, 'users', auth.currentUser.uid, 'settings', 'smtp');
      await setDoc(ref, {
        ...config,
        updatedAt: new Date().toISOString(),
      });
    } catch (e) {
      console.error('Failed to save SMTP to Firestore', e);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        currentUser,
        loading,
        isAuthModalOpen,
        openAuthModal,
        closeAuthModal,
        authModalMode,
        setAuthModalMode,
        loginWithEmail,
        registerWithEmail,
        loginWithGoogle,
        logout,
        loadUserSmtp,
        saveUserSmtp,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
