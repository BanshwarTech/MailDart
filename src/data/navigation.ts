import type { ElementType } from 'react';
import { LayoutDashboard, Server, Users, FileCode, Timer, ScrollText, BookOpen } from 'lucide-react';
import { ViewSection } from '../types';

export interface NavItem {
  id: ViewSection;
  label: string;
  description: string;
  icon: ElementType;
  /** Position in the campaign workflow (1-5); undefined for non-step pages */
  step?: number;
}

/** Single source of truth for pages, in the order a campaign is built. */
export const NAV_ITEMS: NavItem[] = [
  { id: 'overview', label: 'Overview', description: 'Your campaign progress at a glance', icon: LayoutDashboard },
  { id: 'settings', step: 1, label: 'SMTP Settings', description: 'Connect the email account that sends your campaign', icon: Server },
  { id: 'recipients', step: 2, label: 'Recipients', description: 'Add contacts or import them from Excel', icon: Users },
  { id: 'editor', step: 3, label: 'Template Studio', description: 'Campaign details, email design & live preview', icon: FileCode },
  { id: 'dispatch', step: 4, label: 'Dispatcher', description: 'Start staggered sending with a live countdown', icon: Timer },
  { id: 'logs', step: 5, label: 'Delivery Logs', description: 'Track every dispatched email', icon: ScrollText },
  { id: 'guide', label: 'Guide', description: 'How to set up and run a successful campaign', icon: BookOpen },
];

export const STEP_ITEMS = NAV_ITEMS.filter((n) => n.step !== undefined);
export const TOTAL_STEPS = STEP_ITEMS.length;

export const findNavItem = (id: ViewSection) => NAV_ITEMS.find((n) => n.id === id) ?? NAV_ITEMS[0];
