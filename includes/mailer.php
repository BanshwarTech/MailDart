<?php
/**
 * SMTP delivery via bundled PHPMailer (lib/PHPMailer). Mirrors the old Node createSmtpTransport():
 *  - connects to the IPv4 address (many networks have no IPv6 route to smtp.gmail.com)
 *  - keeps the real hostname for TLS (SNI / certificate name)
 *  - picks the TLS mode from the port: 465 = implicit SSL, 587/2525/25 = STARTTLS
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once MD_ROOT . '/lib/PHPMailer/Exception.php';
require_once MD_ROOT . '/lib/PHPMailer/PHPMailer.php';
require_once MD_ROOT . '/lib/PHPMailer/SMTP.php';

class SmtpFailure extends RuntimeException
{
}

/**
 * Build a configured PHPMailer instance.
 * $opts: host, port, secure (bool), user, pass, timeout (seconds)
 * Debug output is captured into $log (used to explain failures).
 */
function smtp_mailer(array $opts, ?array &$log = null): PHPMailer
{
    $log = [];
    $port = (int) ($opts['port'] ?? 0) ?: (!empty($opts['secure']) ? 465 : 587);
    $secure = $port === 465 ? true : (in_array($port, [587, 2525, 25], true) ? false : !empty($opts['secure']));

    // Resolve IPv4 but keep the hostname for TLS
    $host = trim((string) $opts['host']);
    $connectHost = $host;
    $ipv4 = gethostbyname($host);
    if ($ipv4 !== $host && filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $connectHost = $ipv4;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $connectHost;
    $mail->Port = $port;
    $mail->SMTPAuth = true;
    $mail->Username = (string) $opts['user'];
    $mail->Password = (string) $opts['pass'];
    $mail->SMTPSecure = $secure ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAutoTLS = !$secure;
    $mail->Timeout = (int) ($opts['timeout'] ?? 15);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Hostname = parse_url(app_url(), PHP_URL_HOST) ?: 'localhost';
    $mail->SMTPOptions = [
        'ssl' => [
            'peer_name' => $host,
            'SNI_enabled' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function (string $str) use (&$log) {
        $log[] = trim($str);
    };
    return $mail;
}

/** Test login to the SMTP server. Throws SmtpFailure with a friendly message. */
function smtp_verify(array $opts): void
{
    $log = [];
    $mail = smtp_mailer($opts + ['timeout' => 12], $log);
    try {
        if (!$mail->smtpConnect()) {
            throw new SmtpFailure(friendly_smtp_error($mail->ErrorInfo, $log));
        }
        $mail->smtpClose();
    } catch (MailerException $e) {
        throw new SmtpFailure(friendly_smtp_error($e->getMessage(), $log));
    }
}

/**
 * Send one HTML email. Returns ['messageId' => ..., 'response' => ...]. Throws SmtpFailure.
 * $msg: to, subject, html, fromEmail, fromName, replyTo
 */
function smtp_send(array $opts, array $msg): array
{
    $log = [];
    $mail = smtp_mailer($opts + ['timeout' => 20], $log);
    try {
        $mail->setFrom($msg['fromEmail'], $msg['fromName'] ?? '', false);
        $mail->addAddress($msg['to']);
        if (!empty($msg['replyTo'])) {
            $mail->addReplyTo($msg['replyTo']);
        }
        $mail->Subject = $msg['subject'];
        $mail->isHTML(true);
        $mail->Body = $msg['html'];
        $mail->AltBody = trim(html_entity_decode(strip_tags(preg_replace('#<(style|script)[^>]*>.*?</\1>#si', '', $msg['html'])), ENT_QUOTES, 'UTF-8'));
        $mail->send();
        $last = '';
        foreach (array_reverse($log) as $line) {
            if (preg_match('/SERVER -> CLIENT: (2\d\d .*)/', $line, $m)) {
                $last = $m[1];
                break;
            }
        }
        return ['messageId' => $mail->getLastMessageID(), 'response' => $last];
    } catch (MailerException $e) {
        throw new SmtpFailure(friendly_smtp_error($e->getMessage() ?: $mail->ErrorInfo, $log));
    }
}

/** Turn low-level SMTP / network errors into clear guidance for the user (same wording as the Node server). */
function friendly_smtp_error(string $message, array $log = []): string
{
    $all = $message . "\n" . implode("\n", $log);

    if (preg_match('/Could not authenticate|535|Username and Password not accepted|Invalid login|authentication failed/i', $all)) {
        return 'Login failed: username or password was rejected. For Gmail, use a 16-letter App Password (not your normal password) with 2-Step Verification turned on.';
    }
    if (preg_match('/wrong version number|ssl3_get_record|EPROTO|SSL operation failed|unknown protocol/i', $all)) {
        return 'SSL mismatch: use port 465 with SSL on, or port 587 with SSL off.';
    }
    if (preg_match('/Network is unreachable|No route to host|\(101\)|\(113\)|10051|10065/i', $all)) {
        return 'Network unreachable: your internet connection could not reach the SMTP server. Check your connection or firewall and try again.';
    }
    if (preg_match('/Connection refused|\(111\)|10061/i', $all)) {
        return 'Connection refused: the SMTP server rejected the connection on this port. Check the host and port (Gmail: 465 with SSL, or 587 without SSL).';
    }
    if (preg_match('/timed out|timeout|\(110\)|10060/i', $all)) {
        return 'Connection timed out: the SMTP port may be blocked by your network, ISP or antivirus. Try port 587 (or 2525 for Elastic Email).';
    }
    if (preg_match('/getaddrinfo|php_network_getaddresses|No such host|Name or service not known|10109|11001/i', $all)) {
        return 'SMTP host not found: please check the SMTP host name for typos.';
    }
    if (preg_match('/Could not connect to SMTP host/i', $all)) {
        return 'Could not connect to the SMTP server. Check the host and port (Gmail: 465 with SSL, or 587 without SSL) and that your firewall allows outgoing SMTP.';
    }
    return $message !== '' ? $message : 'SMTP error';
}
