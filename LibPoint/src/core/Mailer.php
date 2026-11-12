<?php
/**
 * Minimal SMTP mailer (no Composer needed).
 * Falls back to writing storage/mail.log when no SMTP username is configured.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (MAIL_USERNAME === '') return self::log($to, $subject, $body);
        try {
            return self::smtp($to, $subject, $body);
        } catch (Throwable $e) {
            error_log('Mail failed: ' . $e->getMessage());
            @file_put_contents(BASE_PATH . '/storage/mail-error.log', '[' . date('Y-m-d H:i:s') . "] to=$to | " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            return false;
        }
    }

    private static function log(string $to, string $subject, string $body): bool
    {
        $entry = '[' . date('Y-m-d H:i:s') . "] To: $to | Subject: $subject\n$body\n" . str_repeat('-', 60) . "\n";
        return file_put_contents(BASE_PATH . '/storage/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }

    private static function smtp(string $to, string $subject, string $body): bool
    {
        $remote = (MAIL_ENCRYPTION === 'ssl' ? 'ssl://' : '') . MAIL_HOST . ':' . MAIL_PORT;
        $fp = stream_socket_client($remote, $errno, $errstr, 15);
        if (!$fp) throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
        stream_set_timeout($fp, 15);

        $expect = function (string $code) use ($fp): void {
            $line = '';
            do { $line = fgets($fp, 515); } while ($line !== false && isset($line[3]) && $line[3] === '-');
            if ($line === false || strpos($line, $code) !== 0) throw new RuntimeException("SMTP expected $code, got: " . trim((string)$line));
        };
        $cmd = function (string $c, string $code) use ($fp, $expect): void { fwrite($fp, $c . "\r\n"); $expect($code); };

        $expect('220');
        $cmd('EHLO localhost', '250');
        if (MAIL_ENCRYPTION === 'tls') {
            $cmd('STARTTLS', '220');
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('STARTTLS failed');
            $cmd('EHLO localhost', '250');
        }
        $cmd('AUTH LOGIN', '334');
        $cmd(base64_encode(MAIL_USERNAME), '334');
        $cmd(base64_encode(MAIL_PASSWORD), '235');
        $cmd('MAIL FROM:<' . MAIL_FROM . '>', '250');
        $cmd("RCPT TO:<$to>", '250');
        $cmd('DATA', '354');

        $headers = [
            'Date: ' . date('r'),
            'From: ' . '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?= <' . MAIL_FROM . '>',
            "To: <$to>",
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
        fwrite($fp, $message . "\r\n.\r\n");
        $expect('250');
        $cmd('QUIT', '221');
        fclose($fp);
        return true;
    }
}
