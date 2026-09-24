/** Strong random password: upper, lower, digits and symbols, shuffled (crypto-secure). */
export function generateStrongPassword(length = 16): string {
  const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '!@#$%&*?-_+='];
  const all = sets.join('');
  const rand = (max: number) => {
    const buf = new Uint32Array(1);
    crypto.getRandomValues(buf);
    return buf[0] % max;
  };
  const chars = sets.map((set) => set[rand(set.length)]);
  while (chars.length < length) chars.push(all[rand(all.length)]);
  for (let i = chars.length - 1; i > 0; i--) {
    const j = rand(i + 1);
    [chars[i], chars[j]] = [chars[j], chars[i]];
  }
  return chars.join('');
}

export interface PasswordRules {
  length: boolean;
  upper: boolean;
  lower: boolean;
  number: boolean;
  symbol: boolean;
}

export function checkPasswordRules(pw: string): PasswordRules {
  return {
    length: pw.length >= 8,
    upper: /[A-Z]/.test(pw),
    lower: /[a-z]/.test(pw),
    number: /\d/.test(pw),
    symbol: /[^A-Za-z0-9]/.test(pw),
  };
}

export function passwordStrength(pw: string): { label: string; level: 1 | 2 | 3 } {
  const r = checkPasswordRules(pw);
  const score = Object.values(r).filter(Boolean).length + (pw.length >= 12 ? 1 : 0);
  if (score <= 3) return { label: 'Weak', level: 1 };
  if (score <= 4) return { label: 'Fair', level: 2 };
  return { label: 'Strong', level: 3 };
}
