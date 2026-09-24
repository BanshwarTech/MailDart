export type FestivalType =
  | 'diwali'
  | 'eid'
  | 'christmas'
  | 'newyear'
  | 'holi'
  | 'thanksgiving'
  | 'blackfriday'
  | 'newsletter'
  | 'product_launch'
  | 'followup_reminder'
  | 'welcome_onboarding'
  | 'event_invite'
  | 'custom';

export type RecipientStatus = 'pending' | 'sending' | 'delivered' | 'failed' | 'skipped';

export interface Recipient {
  id: string;
  name: string;
  email: string;
  customData?: Record<string, string>;
  status: RecipientStatus;
  sentAt?: string;
  messageId?: string;
  errorMessage?: string;
  retryCount?: number;
}

export interface SmtpConfig {
  enabled: boolean;
  host: string;
  port: number;
  secure: boolean;
  username: string;
  password: string;
  fromName: string;
  fromEmail: string;
  replyTo?: string;
}

export interface DispatchLog {
  id: string;
  timestamp: string;
  senderEmail?: string;
  recipientEmail: string;
  recipientName: string;
  subject: string;
  status: 'delivered' | 'failed' | 'simulated';
  messageId?: string;
  detail?: string;
  mode: 'smtp' | 'simulated';
}

export interface FestivalPreset {
  id: string;
  name: string;
  festival: FestivalType;
  description: string;
  subject: string;
  previewImage?: string;
  badgeColor: string;
  html: string;
  defaultDiscount: string;
}

export interface CampaignState {
  id: string;
  name: string;
  companyName: string;
  festival: FestivalType;
  subject: string;
  htmlTemplate: string;
  discountCode: string;
  intervalMinutes: number; // default: 5
  intervalSeconds: number; // converted for ticker
  status: 'idle' | 'running' | 'paused' | 'completed';
  currentIndex: number;
  remainingSeconds: number;
  recipients: Recipient[];
  logs: DispatchLog[];
  customVariables?: string[];
}

export type ViewSection = 'home' | 'login' | 'register' | 'overview' | 'settings' | 'recipients' | 'editor' | 'dispatch' | 'logs' | 'guide';
