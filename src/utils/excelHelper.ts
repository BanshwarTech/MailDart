import * as XLSX from 'xlsx';
import { Recipient } from '../types';

export interface ParseExcelResult {
  recipients: Recipient[];
  totalRows: number;
  validCount: number;
  invalidCount: number;
  duplicatesCount: number;
  warnings: string[];
  detectedColumns: {
    email?: string;
    name?: string;
    company?: string;
    discount?: string;
    phone?: string;
    customCols: string[];
  };
  discoveredVariables: string[];
  preview: Array<{
    name: string;
    email: string;
    customFields: Record<string, string>;
    isValid: boolean;
  }>;
}

/**
 * Downloads a pre-formatted sample Excel (.xlsx) template with required and optional custom variable columns
 */
export function downloadSampleExcelTemplate() {
  const sampleData = [
    {
      'Email (Required)': 'aarav.sharma@example.com',
      'Name (Full Name)': 'Aarav Sharma',
      'Company Name': 'Apex Retailers',
      'Discount Code': 'FESTIVE50',
      'City': 'Mumbai',
      'Gift Item': 'Royal Sweets Hamper',
      'Phone': '+91 9876543210',
    },
    {
      'Email (Required)': 'priya.patel@acmeglobal.com',
      'Name (Full Name)': 'Priya Patel',
      'Company Name': 'Global Traders',
      'Discount Code': 'DIWALI40',
      'City': 'Ahmedabad',
      'Gift Item': 'Handcrafted Diya Set',
      'Phone': '+91 9822334455',
    },
    {
      'Email (Required)': 'vikram.singh@enterprise.in',
      'Name (Full Name)': 'Vikram Singh',
      'Company Name': 'Singh Logistics',
      'Discount Code': 'FESTIVEGIFT',
      'City': 'New Delhi',
      'Gift Item': 'Dry Fruits Box',
      'Phone': '+91 9711223344',
    },
    {
      'Email (Required)': 'ananya.deshmukh@crafts.com',
      'Name (Full Name)': 'Ananya Deshmukh',
      'Company Name': 'Deshmukh & Co',
      'Discount Code': 'GOLDEN30',
      'City': 'Pune',
      'Gift Item': 'Silver Coin Voucher',
      'Phone': '+91 9988776655',
    },
  ];

  const worksheet = XLSX.utils.json_to_sheet(sampleData);

  // Set column widths for clean viewing in Microsoft Excel / Google Sheets
  worksheet['!cols'] = [
    { wch: 30 }, // Email
    { wch: 22 }, // Name
    { wch: 22 }, // Company
    { wch: 18 }, // Discount
    { wch: 16 }, // City
    { wch: 24 }, // Gift Item
    { wch: 18 }, // Phone
  ];

  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Recipients');

  XLSX.writeFile(workbook, 'festival_email_recipients_template.xlsx');
}

/**
 * Detects matching column names based on flexible aliases
 */
function findMatchingColumn(columns: string[], patterns: RegExp[]): string | undefined {
  for (const col of columns) {
    const cleanCol = col.trim().toLowerCase();
    for (const pattern of patterns) {
      if (pattern.test(cleanCol)) {
        return col;
      }
    }
  }
  return undefined;
}

/**
 * Helper to normalize column header into template tag friendly format
 * e.g. "Gift Item" -> ["gift_item", "gift item", "giftitem"]
 */
export function sanitizeVariableKey(col: string): string {
  return col
    .trim()
    .toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s-]+/g, '_');
}

/**
 * Parses any uploaded Excel (.xlsx, .xls) or CSV file and extracts recipients with validation and arbitrary custom columns
 */
export async function parseExcelOrCsvFile(file: File): Promise<ParseExcelResult> {
  const arrayBuffer = await file.arrayBuffer();
  const workbook = XLSX.read(arrayBuffer, { type: 'array' });

  // Read the first worksheet
  const firstSheetName = workbook.SheetNames[0];
  if (!firstSheetName) {
    throw new Error('No sheets were found in this Excel file.');
  }

  const worksheet = workbook.Sheets[firstSheetName];
  const rawRows: Record<string, any>[] = XLSX.utils.sheet_to_json(worksheet, {
    defval: '',
  });

  if (!rawRows || rawRows.length === 0) {
    throw new Error('The sheet is empty. Please choose an Excel file that has data.');
  }

  // Get all column names from first row
  const allColumns = Object.keys(rawRows[0] || {});

  // Intelligent column detection
  const emailCol = findMatchingColumn(allColumns, [
    /^email/i,
    /e-mail/i,
    /mail/i,
    /ईमेल/i,
    /email\s*address/i,
    /contact\s*email/i,
    /recipient\s*email/i,
  ]);

  const nameCol = findMatchingColumn(allColumns, [
    /^name/i,
    /full\s*name/i,
    /fullname/i,
    /contact\s*name/i,
    /customer\s*name/i,
    /client\s*name/i,
    /नाम/i,
    /first\s*name/i,
  ]);

  const companyCol = findMatchingColumn(allColumns, [
    /^company/i,
    /organization/i,
    /business/i,
    /firm/i,
    /कंपनी/i,
  ]);

  const discountCol = findMatchingColumn(allColumns, [
    /^discount/i,
    /coupon/i,
    /promo/i,
    /offer/i,
    /code/i,
    /कूपन/i,
  ]);

  const phoneCol = findMatchingColumn(allColumns, [
    /^phone/i,
    /mobile/i,
    /contact\s*no/i,
    /फोन/i,
  ]);

  // Find all remaining custom columns
  const customCols = allColumns.filter((c) => c !== emailCol && c !== nameCol);

  const warnings: string[] = [];
  if (!emailCol) {
    warnings.push('Could not find an Email column automatically, so we tried to detect emails from the first values.');
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const recipients: Recipient[] = [];
  const preview: ParseExcelResult['preview'] = [];
  const seenEmails = new Set<string>();
  const discoveredVariablesSet = new Set<string>();
  let duplicatesCount = 0;
  let invalidCount = 0;

  // Add primary tags
  discoveredVariablesSet.add('name');
  discoveredVariablesSet.add('email');
  if (companyCol) discoveredVariablesSet.add('company');
  if (discountCol) discoveredVariablesSet.add('discount');
  if (phoneCol) discoveredVariablesSet.add('phone');

  // Add all other custom columns to discovered variables
  customCols.forEach((col) => {
    const sanitized = sanitizeVariableKey(col);
    if (sanitized) {
      discoveredVariablesSet.add(sanitized);
    }
    const cleanLower = col.trim().toLowerCase();
    if (cleanLower) {
      discoveredVariablesSet.add(cleanLower);
    }
  });

  rawRows.forEach((row, idx) => {
    // 1. Resolve Email
    let email = '';
    if (emailCol && row[emailCol]) {
      email = String(row[emailCol]).trim();
    } else {
      // Fallback: search all fields in the row for an email address
      for (const val of Object.values(row)) {
        const strVal = String(val).trim();
        if (emailRegex.test(strVal)) {
          email = strVal;
          break;
        }
      }
    }

    // 2. Resolve Name
    let name = '';
    if (nameCol && row[nameCol]) {
      name = String(row[nameCol]).trim();
    } else {
      // Guess from email username if missing
      if (email && email.includes('@')) {
        const userPart = email.split('@')[0].replace(/[._-]/g, ' ');
        name = userPart.charAt(0).toUpperCase() + userPart.slice(1);
      } else {
        name = 'Valued Customer';
      }
    }

    // 3. Resolve ALL other columns into customData
    const customData: Record<string, string> = {};

    // Standard mappings
    if (companyCol && row[companyCol] !== undefined && row[companyCol] !== '') {
      customData['company'] = String(row[companyCol]).trim();
    }
    if (discountCol && row[discountCol] !== undefined && row[discountCol] !== '') {
      customData['discount'] = String(row[discountCol]).trim();
    }
    if (phoneCol && row[phoneCol] !== undefined && row[phoneCol] !== '') {
      customData['phone'] = String(row[phoneCol]).trim();
    }

    // Dynamic extraction of ANY other custom columns present in the file
    for (const col of customCols) {
      const val = row[col];
      if (val !== undefined && val !== null && val !== '') {
        const strVal = String(val).trim();
        const rawKey = col.trim();
        const sanitizedKey = sanitizeVariableKey(rawKey);

        // Store under both raw and sanitized keys for maximum flexibility
        customData[rawKey] = strVal;
        customData[rawKey.toLowerCase()] = strVal;
        if (sanitizedKey && sanitizedKey !== rawKey.toLowerCase()) {
          customData[sanitizedKey] = strVal;
        }
      }
    }

    const isValid = Boolean(email && emailRegex.test(email));

    // Record for preview
    if (preview.length < 50) {
      preview.push({
        name: name || 'Valued Customer',
        email,
        customFields: customData,
        isValid,
      });
    }

    if (!isValid) {
      invalidCount++;
      return;
    }

    const normalizedEmail = email.toLowerCase();
    if (seenEmails.has(normalizedEmail)) {
      duplicatesCount++;
      return; // Skip duplicate
    }
    seenEmails.add(normalizedEmail);

    recipients.push({
      id: `rec-xl-${Date.now()}-${idx}-${Math.random().toString(36).substring(2, 6)}`,
      name: name || 'Valued Customer',
      email,
      customData: Object.keys(customData).length > 0 ? customData : undefined,
      status: 'pending',
    });
  });

  return {
    recipients,
    totalRows: rawRows.length,
    validCount: recipients.length,
    invalidCount,
    duplicatesCount,
    warnings,
    detectedColumns: {
      email: emailCol,
      name: nameCol,
      company: companyCol,
      discount: discountCol,
      phone: phoneCol,
      customCols,
    },
    discoveredVariables: Array.from(discoveredVariablesSet),
    preview,
  };
}
