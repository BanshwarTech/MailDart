<?php
/**
 * Optional: keep campaigns sending even when no browser tab is open.
 * Run every minute from the command line / Task Scheduler / cron:
 *   D:\xampp\php\php.exe D:\xampp\htdocs\core\MailDart\cron\dispatch.php
 *   * * * * * php /path/to/MailDart/cron/dispatch.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require __DIR__ . '/../includes/bootstrap.php';

$stmt = db()->prepare("SELECT user_id FROM campaigns WHERE status = 'running' AND next_send_at <= ?");
$stmt->execute([now_sql()]);
$due = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($due as $userId) {
    $r = dispatch_tick((int) $userId);
    echo date('c'), " user {$userId}: ", $r['sent'] ? 'sent' : 'nothing due', " ({$r['status']})\n";
}
