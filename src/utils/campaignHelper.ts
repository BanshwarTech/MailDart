import { CampaignState, Recipient } from '../types';

export function replacePlaceholders(
  text: string,
  recipient: { name: string; email: string; customData?: Record<string, string> },
  campaign: Pick<CampaignState, 'companyName' | 'festival' | 'discountCode'>
): string {
  if (!text) return '';

  let result = text;
  result = result.replace(/{{\s*name\s*}}/gi, recipient.name || 'Valued Customer');
  result = result.replace(/{{\s*email\s*}}/gi, recipient.email || '');
  result = result.replace(/{{\s*festival\s*}}/gi, getFestivalTitle(campaign.festival));

  // Custom data overrides for company and discount if defined per recipient
  const effectiveCompany = recipient.customData?.company || recipient.customData?.['Company'] || campaign.companyName || 'Our Company';
  const effectiveDiscount = recipient.customData?.discount || recipient.customData?.['Discount'] || campaign.discountCode || 'FESTIVEGIFT';

  result = result.replace(/{{\s*company\s*}}/gi, effectiveCompany);
  result = result.replace(/{{\s*discount\s*}}/gi, effectiveDiscount);

  // Replace all other custom variables (e.g. {{city}}, {{phone}}, {{gift}}, {{orderId}}, etc.)
  if (recipient.customData) {
    // 1. Direct key replacements
    for (const [rawKey, value] of Object.entries(recipient.customData)) {
      if (value !== undefined && value !== null) {
        const cleanKey = rawKey.trim();
        const escapedKey = cleanKey.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const reg = new RegExp(`{{\\s*${escapedKey}\\s*}}`, 'gi');
        result = result.replace(reg, String(value));
      }
    }

    // 2. Normalized matching (e.g. {{gift_item}} or {{gift item}} or {{GiftItem}})
    result = result.replace(/{{\s*([\w\s-]+)\s*}}/gi, (match, varName) => {
      const normalizedTarget = varName.trim().toLowerCase().replace(/[\s_-]+/g, '');
      for (const [k, v] of Object.entries(recipient.customData!)) {
        const normalizedKey = k.trim().toLowerCase().replace(/[\s_-]+/g, '');
        if (normalizedKey === normalizedTarget && v !== undefined && v !== null) {
          return String(v);
        }
      }
      return match;
    });
  }

  return result;
}

export function getFestivalTitle(festival: string): string {
  switch (festival) {
    case 'diwali':
      return 'Diwali (Deepavali)';
    case 'eid':
      return 'Eid Mubarak';
    case 'christmas':
      return 'Christmas & New Year';
    case 'newyear':
      return 'Happy New Year';
    case 'holi':
      return 'Holi (Festival of Colors)';
    case 'thanksgiving':
      return 'Thanksgiving Celebration';
    case 'blackfriday':
      return 'Mega Sale & Offers';
    case 'newsletter':
      return 'Monthly Company Newsletter';
    case 'product_launch':
      return 'New Product Announcement';
    case 'followup_reminder':
      return 'Payment & Follow-up Reminder';
    case 'welcome_onboarding':
      return 'Welcome & Client Onboarding';
    case 'event_invite':
      return 'Event & Webinar Invitation';
    case 'custom':
      return 'General Campaign';
    default:
      return 'General Email Campaign';
  }
}

export function parseRecipientsInput(rawText: string): Recipient[] {
  if (!rawText.trim()) return [];

  const lines = rawText.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
  const recipients: Recipient[] = [];

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    // check if comma separated or tab separated or email only
    const parts = line.split(/[,;\t]/).map((p) => p.trim());
    let name = '';
    let email = '';

    if (parts.length >= 2) {
      // Could be Name, Email or Email, Name
      if (parts[0].includes('@')) {
        email = parts[0];
        name = parts[1];
      } else {
        name = parts[0];
        email = parts[1];
      }
    } else {
      // Just single string - check if email
      const matchedEmail = line.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
      if (matchedEmail) {
        email = matchedEmail[0];
        name = email.split('@')[0].replace(/[._-]/g, ' ');
        name = name.charAt(0).toUpperCase() + name.slice(1);
      }
    }

    if (email && email.includes('@')) {
      recipients.push({
        id: `rec-${Date.now()}-${i}-${Math.random().toString(36).substring(2, 6)}`,
        name: name || 'Valued Customer',
        email,
        status: 'pending',
      });
    }
  }

  return recipients;
}

export function formatTimeRemaining(seconds: number): string {
  if (seconds <= 0) return '00:00';
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

export function exportToCsv(filename: string, rows: (string | number)[][]) {
  const processRow = (row: (string | number)[]) => {
    let finalVal = '';
    for (let j = 0; j < row.length; j++) {
      let innerValue = row[j] === null || row[j] === undefined ? '' : row[j].toString();
      let result = innerValue.replace(/"/g, '""');
      if (result.search(/("|,|\n)/g) >= 0) result = `"${result}"`;
      if (j > 0) finalVal += ',';
      finalVal += result;
    }
    return finalVal + '\n';
  };

  let csvFile = '';
  for (let i = 0; i < rows.length; i++) {
    csvFile += processRow(rows[i]);
  }

  const blob = new Blob([csvFile], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  if (link.download !== undefined) {
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}

export type SmtpStatus = 'live' | 'incomplete' | 'sandbox';

/** Real delivery only happens when SMTP is enabled AND credentials are filled in. */
export function getSmtpStatus(config: { enabled: boolean; username?: string; password?: string }): SmtpStatus {
  if (!config.enabled) return 'sandbox';
  return config.username && config.password ? 'live' : 'incomplete';
}
