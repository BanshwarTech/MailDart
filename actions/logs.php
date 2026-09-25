<?php
/** Delivery log actions (was src/components/DeliveryLogs.tsx). */

return [
    // "Clear all logs" (TSX's onClearLogs has no window.confirm, so neither does this)
    'clear' => function (array $user): void {
        $campaign = campaign_load((int) $user['id'], false);
        logs_clear($campaign['id']);
        redirect_back('logs');
    },

    // "Export CSV" — GET download handler (index.php?action=logs.downloadCsv), same columns/filename as
    // DeliveryLogs.tsx's handleExport(): exports every log, ignoring the on-page search/filter.
    'downloadCsv' => function (array $user): void {
        $campaign = campaign_load((int) $user['id']);
        $rows = [['Timestamp', 'Sender (From)', 'Recipient Name', 'Recipient Email', 'Status', 'Mode', 'Message ID', 'Subject', 'Detail']];
        foreach ($campaign['logs'] as $log) {
            $rows[] = [
                $log['timestamp'],
                $log['senderEmail'] ?? '',
                $log['recipientName'],
                $log['recipientEmail'],
                $log['status'],
                $log['mode'],
                $log['messageId'] ?? '',
                $log['subject'],
                $log['detail'] ?? '',
            ];
        }
        send_csv('festival-email-dispatch-logs-' . (int) round(microtime(true) * 1000) . '.csv', $rows);
    },
];
