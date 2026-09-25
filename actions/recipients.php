<?php
/**
 * Recipients actions (was the handlers passed into <RecipientsManager> from App.tsx, plus ExcelUploadModal's
 * upload/confirm flow). Each handler receives the logged-in user row. $user['id'] identifies the campaign.
 */

declare(strict_types=1);

require_once MD_ROOT . '/includes/excel.php';

/** Read + zip parallel custom_key[]/custom_value[] arrays into a customData map, same rule as the TSX forms:
 *  cleanKey = trim(lowercase(key)); if cleanKey && value.trim(): store BOTH the lowercase key and the trimmed
 *  original-case key. */
function recipients_custom_data_from_post(): ?array
{
    $keys = $_POST['custom_key'] ?? [];
    $values = $_POST['custom_value'] ?? [];
    if (!is_array($keys) || !is_array($values)) {
        return null;
    }
    $customData = [];
    foreach ($keys as $i => $key) {
        $key = is_string($key) ? $key : '';
        $value = is_string($values[$i] ?? null) ? $values[$i] : '';
        $cleanKey = mb_strtolower(trim($key));
        if ($cleanKey !== '' && trim($value) !== '') {
            $customData[$cleanKey] = trim($value);
            $customData[trim($key)] = trim($value);
        }
    }
    return $customData ?: null;
}

/** Query params worth preserving across redirects that stay on the recipients list (search, filter, page). */
function recipients_list_params(): array
{
    return array_filter([
        'q' => $_GET['q'] ?? null,
        'status' => $_GET['status'] ?? null,
        'pg' => $_GET['pg'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');
}

return [
    /* ---------------------------------------------------------------- Single add / edit / delete */

    // Add Recipient modal (?modal=add) submit (was handleAddSingle in RecipientsManager.tsx)
    'addSingle' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $email = input('email');
        $name = input('name');
        if ($email === '' || !str_contains($email, '@')) {
            keep_old_input();
            flash('error', 'Please enter a valid email address.');
            redirect('recipients', ['modal' => 'add']);
        }

        $recipient = new_recipient($name !== '' ? $name : 'Valued Customer', $email, recipients_custom_data_from_post());
        recipients_append($campaign['id'], [$recipient]);
        redirect('recipients', recipients_list_params()); // closes the modal
    },

    // Bulk paste modal (?modal=bulk) submit (was handleAddBulk)
    'addBulk' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $raw = input_raw('bulk_input');
        $parsed = parse_recipients_input($raw);
        if (!$parsed) {
            keep_old_input();
            redirect('recipients', ['modal' => 'bulk']); // silent no-op, same as the TSX when nothing could be parsed
        }
        recipients_append($campaign['id'], $parsed);
        redirect('recipients', recipients_list_params()); // closes the modal
    },

    // Inline edit row (?edit=<id>) submit (was handleSaveEdit)
    'saveEdit' => function (array $user): void {
        $campaign = campaign_load((int) $user['id']);
        $id = input('id');
        $recipient = recipient_find($campaign, $id);
        $email = input('email');
        if (!$recipient || $email === '' || !str_contains($email, '@')) {
            keep_old_input();
            flash('error', 'Please enter a valid email address.');
            redirect('recipients', ['edit' => $id] + recipients_list_params());
        }

        $name = input('name');
        recipient_update($campaign['id'], $id, [
            'name' => $name !== '' ? $name : 'Valued Customer',
            'email' => $email,
            'customData' => recipients_custom_data_from_post(),
        ]);
        redirect('recipients', recipients_list_params()); // closes the inline edit row
    },

    // Remove one recipient (was handleRemoveSingle - no confirmation in the TSX)
    'delete' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        recipients_delete($campaign['id'], [input('id')]);
        redirect_back('recipients');
    },

    // Bulk-selected delete (checkboxes in the table form)
    'bulkDelete' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $ids = array_values(array_filter((array) ($_POST['ids'] ?? []), 'is_string'));
        if ($ids) {
            recipients_delete($campaign['id'], $ids);
            flash('success', count($ids) . ' recipient(s) removed.');
        }
        redirect_back('recipients');
    },

    // Retry a failed recipient right now (was onRetryRecipient)
    'retry' => function (array $user): void {
        $campaign = campaign_load((int) $user['id']);
        $recipient = recipient_find($campaign, input('id'));
        if ($recipient && $recipient['status'] === 'failed') {
            dispatch_recipient($campaign, $recipient, smtp_load((int) $user['id']));
        }
        redirect_back('recipients');
    },

    /* ---------------------------------------------------------------- Bulk list actions */

    // "Load sample data" (was handleLoadSample - replaces the whole list; the confirm prompt is attached to the
    // menu item / button that posts here, matching the TSX's window.confirm only when recipients already exist)
    'loadSample' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        recipients_replace($campaign['id'], initial_sample_recipients());
        redirect_back('recipients');
    },

    // "Clear all recipients" (was handleClearAll)
    'clearAll' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        recipients_replace($campaign['id'], []);
        redirect_back('recipients');
    },

    /* ---------------------------------------------------------------- Excel / CSV upload flow */

    // File chosen in the Excel Upload modal (was handleFileProcess -> parseExcelOrCsvFile)
    'uploadExcel' => function (array $user): void {
        campaign_load((int) $user['id'], false); // ensures the campaign row exists
        // A fresh upload attempt always replaces any earlier preview/error (was setError(null)/setParseResult(null)
        // at the top of handleFileProcess) - otherwise a failed re-upload would silently keep showing a stale
        // preview from a previous successful parse, since the modal partial re-flashes it to survive reloads.
        take_flash_data('excel_preview');
        take_flash_data('excel_upload_error');

        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash_data('excel_upload_error', 'Please choose a file to upload.');
            redirect('recipients', ['modal' => 'excel']);
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash_data('excel_upload_error', 'Could not upload the file. Please try again.');
            redirect('recipients', ['modal' => 'excel']);
        }

        $lowerName = strtolower($file['name']);
        $isValidExt = str_ends_with($lowerName, '.xlsx') || str_ends_with($lowerName, '.xls') || str_ends_with($lowerName, '.csv');
        if (!$isValidExt) {
            flash_data('excel_upload_error', 'Please upload only a .xlsx, .xls or .csv file.');
            redirect('recipients', ['modal' => 'excel']);
        }

        try {
            $result = excel_parse_uploaded_file($file['tmp_name'], $file['name']);
        } catch (ExcelParseException $e) {
            flash_data('excel_upload_error', $e->getMessage());
            redirect('recipients', ['modal' => 'excel']);
        }

        $result['fileName'] = $file['name'];
        flash_data('excel_preview', $result);
        redirect('recipients', ['modal' => 'excel']);
    },

    // "Change File" in the preview step (was handleReset)
    'resetExcelPreview' => function (array $user): void {
        take_flash_data('excel_preview');
        take_flash_data('excel_upload_error');
        redirect('recipients', ['modal' => 'excel']);
    },

    // Confirm import: append/replace + auto-insert-block choice (was handleConfirmImport)
    'confirmImport' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        $preview = take_flash_data('excel_preview');
        if (!$preview || empty($preview['recipients'])) {
            flash('error', 'Please upload a file first.');
            redirect('recipients', ['modal' => 'excel']);
        }
        $replace = input('importMode') === 'replace';
        $autoInsert = input_bool('autoInsertBlock');
        campaign_import_excel($campaign, $preview['recipients'], $replace, $preview['discoveredVariables'], $autoInsert);
        redirect('recipients'); // closes the modal; the Excel banner is picked up from $_SESSION['excel_toast'] by the layout
    },

    /* ---------------------------------------------------------------- GET downloads (index.php only allows GET for action names starting with "download") */

    // "Download Excel format" / "Download Sample Sheet" / "Download Blank Template" (was downloadSampleExcelTemplate)
    'downloadSample' => function (array $user): void {
        excel_send_sample_template();
    },

    // "Export as CSV" (was handleExportCsv)
    'downloadCsv' => function (array $user): void {
        $campaign = campaign_load((int) $user['id']);
        $rows = [['ID', 'Name', 'Email', 'Status', 'Custom Variables', 'Sent At', 'Message ID', 'Error']];
        foreach ($campaign['recipients'] as $r) {
            $rows[] = [
                $r['id'],
                $r['name'],
                $r['email'],
                $r['status'],
                $r['customData'] ? json_encode($r['customData'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                $r['sentAt'] ?? '',
                $r['messageId'] ?? '',
                $r['errorMessage'] ?? '',
            ];
        }
        send_csv('campaign-recipients-' . (int) (microtime(true) * 1000) . '.csv', $rows);
    },
];
