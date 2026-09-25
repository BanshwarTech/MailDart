<?php
/**
 * Excel/CSV helper (was src/utils/excelHelper.ts), written in pure PHP with NO third-party libraries.
 *
 * Reading:
 *   - .xlsx  -> ZipArchive + SimpleXML: workbook.xml + workbook.xml.rels resolve the FIRST worksheet's real
 *               part name, sharedStrings.xml (plain + rich-text runs) resolves shared string cells, inline
 *               strings (t="inlineStr") and numeric/boolean cells are read directly. Header = row 1, blank
 *               rows are skipped, missing cells default to '' (mirrors SheetJS's `sheet_to_json(ws, {defval:''})`).
 *   - .csv   -> fgetcsv() with the delimiter (`,`, `;` or tab) auto-detected from the header line and a
 *               leading UTF-8 BOM stripped first.
 *   - .xls   -> legacy binary Excel cannot be parsed without a library. We accept the upload (so the error is
 *               friendly) and throw a clear ExcelParseException asking the user to re-save as .xlsx or .csv.
 *               This is a deliberate deviation from the TSX (which used SheetJS and could read real .xls files);
 *               see the conversion report.
 *
 * Writing: excel_build_sample_template() builds a minimal valid SpreadsheetML .xlsx (inline strings, no
 * sharedStrings.xml / styles.xml needed) with ZipArchive, matching downloadSampleExcelTemplate() in the TSX.
 */

declare(strict_types=1);

require_once __DIR__ . '/campaign.php'; // new_recipient()

class ExcelParseException extends RuntimeException
{
}

/* ---------------------------------------------------------------------------
 * Column detection (was findMatchingColumn / the regex lists in excelHelper.ts)
 * ------------------------------------------------------------------------- */

const EXCEL_EMAIL_PATTERNS = [
    '/^email/iu', '/e-mail/iu', '/mail/iu', '/ईमेल/iu',
    '/email\s*address/iu', '/contact\s*email/iu', '/recipient\s*email/iu',
];
const EXCEL_NAME_PATTERNS = [
    '/^name/iu', '/full\s*name/iu', '/fullname/iu', '/contact\s*name/iu',
    '/customer\s*name/iu', '/client\s*name/iu', '/नाम/iu', '/first\s*name/iu',
];
const EXCEL_COMPANY_PATTERNS = ['/^company/iu', '/organization/iu', '/business/iu', '/firm/iu', '/कंपनी/iu'];
const EXCEL_DISCOUNT_PATTERNS = ['/^discount/iu', '/coupon/iu', '/promo/iu', '/offer/iu', '/code/iu', '/कूपन/iu'];
const EXCEL_PHONE_PATTERNS = ['/^phone/iu', '/mobile/iu', '/contact\s*no/iu', '/फोन/iu'];

/** Detects a matching column name based on flexible aliases (case-insensitive, trimmed). */
function excel_find_matching_column(array $columns, array $patterns): ?string
{
    foreach ($columns as $col) {
        $clean = mb_strtolower(trim((string) $col));
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return $col;
            }
        }
    }
    return null;
}

/** Normalise a column header into a template-tag-friendly key, e.g. "Gift Item" -> "gift_item". */
function excel_sanitize_variable_key(string $col): string
{
    $s = mb_strtolower(trim($col));
    $s = preg_replace('/[^\w\s-]/', '', $s) ?? '';
    $s = preg_replace('/[\s-]+/', '_', $s) ?? '';
    return $s;
}

/* ---------------------------------------------------------------------------
 * Shared row -> ParseExcelResult logic (was the body of parseExcelOrCsvFile)
 * ------------------------------------------------------------------------- */

/**
 * Turns raw rows (list of assoc arrays keyed by header name, values already stringified) into the same
 * shape as the TSX's ParseExcelResult: recipients, totalRows, validCount, invalidCount, duplicatesCount,
 * warnings, detectedColumns, discoveredVariables, preview.
 */
function excel_rows_to_result(array $rawRows): array
{
    if (!$rawRows) {
        throw new ExcelParseException('The sheet is empty. Please choose an Excel file that has data.');
    }
    $allColumns = array_keys($rawRows[0]);

    $emailCol = excel_find_matching_column($allColumns, EXCEL_EMAIL_PATTERNS);
    $nameCol = excel_find_matching_column($allColumns, EXCEL_NAME_PATTERNS);
    $companyCol = excel_find_matching_column($allColumns, EXCEL_COMPANY_PATTERNS);
    $discountCol = excel_find_matching_column($allColumns, EXCEL_DISCOUNT_PATTERNS);
    $phoneCol = excel_find_matching_column($allColumns, EXCEL_PHONE_PATTERNS);

    $customCols = array_values(array_filter($allColumns, fn ($c) => $c !== $emailCol && $c !== $nameCol));

    $warnings = [];
    if (!$emailCol) {
        $warnings[] = 'Could not find an Email column automatically, so we tried to detect emails from the first values.';
    }

    $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    $recipients = [];
    $preview = [];
    $seenEmails = [];
    $discovered = [];
    $duplicatesCount = 0;
    $invalidCount = 0;

    $discovered['name'] = true;
    $discovered['email'] = true;
    if ($companyCol) {
        $discovered['company'] = true;
    }
    if ($discountCol) {
        $discovered['discount'] = true;
    }
    if ($phoneCol) {
        $discovered['phone'] = true;
    }
    foreach ($customCols as $col) {
        $sanitized = excel_sanitize_variable_key($col);
        if ($sanitized !== '') {
            $discovered[$sanitized] = true;
        }
        $cleanLower = mb_strtolower(trim($col));
        if ($cleanLower !== '') {
            $discovered[$cleanLower] = true;
        }
    }

    foreach (array_values($rawRows) as $idx => $row) {
        // 1. Resolve email
        $email = '';
        if ($emailCol !== null && (string) ($row[$emailCol] ?? '') !== '') {
            $email = trim((string) $row[$emailCol]);
        } else {
            foreach ($row as $val) {
                $strVal = trim((string) $val);
                if ($strVal !== '' && preg_match($emailRegex, $strVal)) {
                    $email = $strVal;
                    break;
                }
            }
        }

        // 2. Resolve name
        if ($nameCol !== null && (string) ($row[$nameCol] ?? '') !== '') {
            $name = trim((string) $row[$nameCol]);
        } elseif ($email !== '' && str_contains($email, '@')) {
            $userPart = str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]);
            $name = mb_strtoupper(mb_substr($userPart, 0, 1)) . mb_substr($userPart, 1);
        } else {
            $name = 'Valued Customer';
        }

        // 3. Resolve all other columns into customData
        $customData = [];
        if ($companyCol !== null && (string) ($row[$companyCol] ?? '') !== '') {
            $customData['company'] = trim((string) $row[$companyCol]);
        }
        if ($discountCol !== null && (string) ($row[$discountCol] ?? '') !== '') {
            $customData['discount'] = trim((string) $row[$discountCol]);
        }
        if ($phoneCol !== null && (string) ($row[$phoneCol] ?? '') !== '') {
            $customData['phone'] = trim((string) $row[$phoneCol]);
        }
        foreach ($customCols as $col) {
            $val = $row[$col] ?? null;
            if ($val !== null && $val !== '') {
                $strVal = trim((string) $val);
                $rawKey = trim($col);
                $lowerKey = mb_strtolower($rawKey);
                $sanitizedKey = excel_sanitize_variable_key($rawKey);
                $customData[$rawKey] = $strVal;
                $customData[$lowerKey] = $strVal;
                if ($sanitizedKey !== '' && $sanitizedKey !== $lowerKey) {
                    $customData[$sanitizedKey] = $strVal;
                }
            }
        }

        $isValid = $email !== '' && preg_match($emailRegex, $email) === 1;

        if (count($preview) < 50) {
            $preview[] = [
                'name' => $name !== '' ? $name : 'Valued Customer',
                'email' => $email,
                'customFields' => $customData,
                'isValid' => $isValid,
            ];
        }

        if (!$isValid) {
            $invalidCount++;
            continue;
        }

        $normalizedEmail = mb_strtolower($email);
        if (isset($seenEmails[$normalizedEmail])) {
            $duplicatesCount++;
            continue;
        }
        $seenEmails[$normalizedEmail] = true;

        $recipients[] = new_recipient($name !== '' ? $name : 'Valued Customer', $email, $customData ?: null, $idx);
    }

    return [
        'recipients' => $recipients,
        'totalRows' => count($rawRows),
        'validCount' => count($recipients),
        'invalidCount' => $invalidCount,
        'duplicatesCount' => $duplicatesCount,
        'warnings' => $warnings,
        'detectedColumns' => [
            'email' => $emailCol,
            'name' => $nameCol,
            'company' => $companyCol,
            'discount' => $discountCol,
            'phone' => $phoneCol,
            'customCols' => $customCols,
        ],
        'discoveredVariables' => array_keys($discovered),
        'preview' => $preview,
    ];
}

/* ---------------------------------------------------------------------------
 * Spreadsheet column-letter helpers (A, B, ..., Z, AA, AB, ...)
 * ------------------------------------------------------------------------- */

function excel_col_letters_to_num(string $letters): int
{
    $n = 0;
    foreach (str_split(strtoupper($letters)) as $ch) {
        $n = $n * 26 + (ord($ch) - 64);
    }
    return $n;
}

function excel_num_to_col_letters(int $n): string
{
    $s = '';
    while ($n > 0) {
        $n--;
        $s = chr(65 + ($n % 26)) . $s;
        $n = intdiv($n, 26);
    }
    return $s;
}

function excel_cell_col_letters(string $ref): string
{
    return preg_match('/^([A-Za-z]+)\d+$/', $ref, $m) ? strtoupper($m[1]) : 'A';
}

/** Number stored in a spreadsheet cell -> string, the way SheetJS's default (non-cellDates) numeric value would stringify. */
function excel_num_to_string_raw(string $raw): string
{
    if ($raw === '' || !is_numeric($raw)) {
        return $raw;
    }
    $f = (float) $raw;
    if (fmod($f, 1.0) === 0.0 && abs($f) < 1.0e15) {
        return (string) (int) $f;
    }
    $s = sprintf('%.10F', $f);
    $s = rtrim($s, '0');
    $s = rtrim($s, '.');
    return $s;
}

/* ---------------------------------------------------------------------------
 * .xlsx reader (ZipArchive + SimpleXML)
 * ------------------------------------------------------------------------- */

/** Concatenated text of a <si> shared-string entry (plain <t> or rich-text <r><t>...</t></r> runs). */
function excel_si_text(SimpleXMLElement $si): string
{
    if (isset($si->t)) {
        return (string) $si->t;
    }
    $text = '';
    foreach ($si->r as $run) {
        $text .= (string) $run->t;
    }
    return $text;
}

function excel_cell_value(SimpleXMLElement $c, string $type, array $shared): string
{
    if ($type === 'inlineStr') {
        return isset($c->is) ? excel_si_text($c->is) : '';
    }
    if (!isset($c->v)) {
        return '';
    }
    $v = (string) $c->v;
    return match ($type) {
        's' => $shared[(int) $v] ?? '',
        'b' => $v === '1' ? 'true' : 'false',
        'str', 'e' => $v,
        default => excel_num_to_string_raw($v),
    };
}

/**
 * Reads the FIRST worksheet of an .xlsx file into a list of associative rows keyed by the header row (row 1).
 * Blank rows (every cell empty) are skipped. Missing cells default to '' (like SheetJS's defval:'').
 */
function excel_read_xlsx_rows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new ExcelParseException('This does not look like a valid .xlsx file. Please re-save it from Excel or Google Sheets and try again.');
    }

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    if ($workbookXml === false) {
        $zip->close();
        throw new ExcelParseException('This does not look like a valid .xlsx file (missing workbook.xml).');
    }
    $wb = @simplexml_load_string($workbookXml);
    if (!$wb) {
        $zip->close();
        throw new ExcelParseException('Could not read the workbook structure in this .xlsx file.');
    }
    $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $sheets = $wb->xpath('//m:sheets/m:sheet');
    if (!$sheets) {
        $zip->close();
        throw new ExcelParseException('No sheets were found in this Excel file.');
    }
    $firstSheet = $sheets[0];
    $rAttrs = $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $rid = (string) ($rAttrs['id'] ?? '');

    $target = 'worksheets/sheet1.xml';
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($relsXml !== false && $rid !== '') {
        $rels = @simplexml_load_string($relsXml);
        if ($rels) {
            foreach ($rels->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $target = (string) $rel['Target'];
                    break;
                }
            }
        }
    }
    $target = ltrim($target, '/');
    if (!str_starts_with($target, 'xl/')) {
        $target = 'xl/' . $target;
    }

    $sheetXml = $zip->getFromName($target);
    if ($sheetXml === false) {
        $zip->close();
        throw new ExcelParseException('Could not find the first worksheet inside this .xlsx file.');
    }

    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sst = @simplexml_load_string($sharedXml);
        if ($sst) {
            foreach ($sst->si as $si) {
                $shared[] = excel_si_text($si);
            }
        }
    }
    $zip->close();

    $sheet = @simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData)) {
        throw new ExcelParseException('Could not read the worksheet contents in this .xlsx file.');
    }

    $matrix = [];
    foreach ($sheet->sheetData->row as $rowXml) {
        $cells = [];
        $seq = 0;
        foreach ($rowXml->c as $c) {
            $ref = (string) $c['r'];
            if ($ref !== '') {
                $col = excel_cell_col_letters($ref);
                $seq = excel_col_letters_to_num($col);
            } else {
                $seq++;
                $col = excel_num_to_col_letters($seq);
            }
            $type = (string) $c['t'];
            $cells[$col] = excel_cell_value($c, $type, $shared);
        }
        if ($cells === []) {
            continue; // fully empty <row/> element
        }
        $matrix[] = $cells;
    }

    if (!$matrix) {
        throw new ExcelParseException('The sheet is empty. Please choose an Excel file that has data.');
    }

    // Header = first row; order remaining columns left-to-right by real column index.
    $headerCells = array_shift($matrix);
    $cols = array_keys($headerCells);
    usort($cols, fn ($a, $b) => excel_col_letters_to_num($a) <=> excel_col_letters_to_num($b));
    $headerByCol = [];
    foreach ($cols as $col) {
        $name = trim($headerCells[$col]);
        if ($name !== '') {
            $headerByCol[$col] = $name;
        }
    }
    if (!$headerByCol) {
        throw new ExcelParseException('The sheet is empty. Please choose an Excel file that has data.');
    }

    $rows = [];
    foreach ($matrix as $cells) {
        $row = [];
        $blank = true;
        foreach ($headerByCol as $col => $name) {
            $v = $cells[$col] ?? '';
            if ($v !== '') {
                $blank = false;
            }
            $row[$name] = $v;
        }
        if ($blank) {
            continue; // empty rows are skipped
        }
        $rows[] = $row;
    }
    if (!$rows) {
        throw new ExcelParseException('The sheet is empty. Please choose an Excel file that has data.');
    }
    return $rows;
}

/* ---------------------------------------------------------------------------
 * .csv reader (fgetcsv, auto-detected delimiter, BOM-safe)
 * ------------------------------------------------------------------------- */

function excel_read_csv_rows(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ExcelParseException('Could not read this CSV file.');
    }
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }

    $firstLine = substr($raw, 0, strcspn($raw, "\r\n"));
    $counts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
    arsort($counts);
    $delimiter = array_key_first($counts);
    if (($counts[$delimiter] ?? 0) === 0) {
        $delimiter = ',';
    }

    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    if ($lines && end($lines) === '') {
        array_pop($lines);
    }

    $fh = fopen('php://temp', 'r+b');
    fwrite($fh, implode("\n", $lines));
    rewind($fh);

    $header = fgetcsv($fh, 0, $delimiter, '"', '');
    if ($header === false || $header === null) {
        fclose($fh);
        throw new ExcelParseException('The sheet is empty. Please choose a CSV file that has data.');
    }
    $header = array_map(fn ($h) => trim((string) $h), $header);

    $rows = [];
    while (($data = fgetcsv($fh, 0, $delimiter, '"', '')) !== false) {
        if ($data === null || $data === [null]) {
            continue;
        }
        $row = [];
        $blank = true;
        foreach ($header as $i => $name) {
            if ($name === '') {
                continue;
            }
            $v = trim((string) ($data[$i] ?? ''));
            if ($v !== '') {
                $blank = false;
            }
            $row[$name] = $v;
        }
        if ($blank || !$row) {
            continue; // empty rows are skipped
        }
        $rows[] = $row;
    }
    fclose($fh);

    if (!$rows) {
        throw new ExcelParseException('The sheet is empty. Please choose a CSV file that has data.');
    }
    return $rows;
}

/* ---------------------------------------------------------------------------
 * Entry point used by actions/recipients.php
 * ------------------------------------------------------------------------- */

/**
 * Parses an uploaded .xlsx, .xls or .csv file (by its tmp path + original name) into a ParseExcelResult array.
 * Throws ExcelParseException with a user-friendly message on any failure (empty sheet, corrupt file, legacy .xls).
 */
function excel_parse_uploaded_file(string $tmpPath, string $originalName): array
{
    $lower = strtolower(trim($originalName));
    if (str_ends_with($lower, '.xlsx')) {
        $rows = excel_read_xlsx_rows($tmpPath);
    } elseif (str_ends_with($lower, '.xls')) {
        // Deviation from the TSX (which used SheetJS and could read real .xls binaries): parsing the legacy
        // OLE2/BIFF binary format needs a library. We accept the upload but ask for a supported format.
        throw new ExcelParseException('Legacy .xls files cannot be read without extra libraries. Please open this file in Excel or Google Sheets, save it as .xlsx or .csv, and upload it again.');
    } elseif (str_ends_with($lower, '.csv')) {
        $rows = excel_read_csv_rows($tmpPath);
    } else {
        throw new ExcelParseException('Please upload only a .xlsx, .xls or .csv file.');
    }
    return excel_rows_to_result($rows);
}

/* ---------------------------------------------------------------------------
 * .xlsx writer: the sample template (was downloadSampleExcelTemplate in excelHelper.ts)
 * ------------------------------------------------------------------------- */

const EXCEL_SAMPLE_TEMPLATE_FILENAME = 'festival_email_recipients_template.xlsx';

/** Builds a minimal-but-valid SpreadsheetML .xlsx (inline strings; no sharedStrings.xml/styles.xml needed) and returns its bytes. */
function excel_build_sample_template(): string
{
    $columns = ['Email (Required)', 'Name (Full Name)', 'Company Name', 'Discount Code', 'City', 'Gift Item', 'Phone'];
    $widths = [30, 22, 22, 18, 16, 24, 18];
    $sampleRows = [
        ['aarav.sharma@example.com', 'Aarav Sharma', 'Apex Retailers', 'FESTIVE50', 'Mumbai', 'Royal Sweets Hamper', '+91 9876543210'],
        ['priya.patel@acmeglobal.com', 'Priya Patel', 'Global Traders', 'DIWALI40', 'Ahmedabad', 'Handcrafted Diya Set', '+91 9822334455'],
        ['vikram.singh@enterprise.in', 'Vikram Singh', 'Singh Logistics', 'FESTIVEGIFT', 'New Delhi', 'Dry Fruits Box', '+91 9711223344'],
        ['ananya.deshmukh@crafts.com', 'Ananya Deshmukh', 'Deshmukh & Co', 'GOLDEN30', 'Pune', 'Silver Coin Voucher', '+91 9988776655'],
    ];

    $esc = fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $writeRow = function (int $rowNum, array $values) use ($esc): string {
        $out = '<row r="' . $rowNum . '">';
        foreach ($values as $i => $val) {
            $ref = excel_num_to_col_letters($i + 1) . $rowNum;
            $out .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $esc((string) $val) . '</t></is></c>';
        }
        return $out . '</row>';
    };

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols>';
    foreach ($widths as $i => $w) {
        $col = $i + 1;
        $sheetXml .= '<col min="' . $col . '" max="' . $col . '" width="' . $w . '" customWidth="1"/>';
    }
    $sheetXml .= '</cols><sheetData>' . $writeRow(1, $columns);
    foreach ($sampleRows as $i => $row) {
        $sheetXml .= $writeRow($i + 2, $row);
    }
    $sheetXml .= '</sheetData></worksheet>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '</Types>';
    $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Recipients" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '</Relationships>';

    $tmp = tempnam(sys_get_temp_dir(), 'mdxlsx');
    if ($tmp === false) {
        throw new RuntimeException('Could not create a temporary file to build the sample workbook.');
    }
    @unlink($tmp);
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not create the sample workbook archive.');
    }
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rootRels);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    $bytes = file_get_contents($tmp);
    @unlink($tmp);
    if ($bytes === false) {
        throw new RuntimeException('Could not read the generated sample workbook.');
    }
    return $bytes;
}

/** Streams the sample template as a download and stops (GET handler helper, like send_csv()). */
function excel_send_sample_template(): never
{
    $bytes = excel_build_sample_template();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . EXCEL_SAMPLE_TEMPLATE_FILENAME . '"');
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    exit;
}
