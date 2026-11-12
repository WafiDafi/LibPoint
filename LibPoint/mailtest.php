<?php
// TEMPORARY mail diagnostic. 1) set $SECRET below  2) upload  3) open:
//   https://YOURSITE/mailtest.php?key=YOURSECRET&to=someone@example.com
// 4) DELETE THIS FILE when finished. It prints your mail settings (never the password).
$SECRET = 'change-this-secret';

require __DIR__ . '/config/config.php';
header('Content-Type: text/plain; charset=utf-8');
if ($SECRET === 'change-this-secret' || !hash_equals($SECRET, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit("Edit \$SECRET inside mailtest.php, then open: mailtest.php?key=YOURSECRET&to=you@example.com\n");
}
$to = (string)($_GET['to'] ?? '');
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) exit("Add &to=your@email.com to the URL.\n");

echo "PHP " . PHP_VERSION . " | openssl: " . (extension_loaded('openssl') ? 'yes' : 'NO') . "\n";
echo "Host " . MAIL_HOST . ":" . MAIL_PORT . " (" . MAIL_ENCRYPTION . ") | user: " . MAIL_USERNAME . " | password length: " . strlen(MAIL_PASSWORD) . "\n";
if (MAIL_USERNAME === '') exit("MAIL_USERNAME is empty -> mail is only written to storage/mail.log.\n");
echo "DNS " . MAIL_HOST . " -> " . gethostbyname(MAIL_HOST) . "\n\n";

echo "--- Can this server reach Gmail's SMTP ports? ---\n";
foreach ([587, 465, 2525] as $p) {
    $t = microtime(true);
    $s = @stream_socket_client(MAIL_HOST . ':' . $p, $en, $es, 8);
    echo "port $p: " . ($s ? 'OPEN' : "BLOCKED ($es)") . sprintf(" [%.1fs]\n", microtime(true) - $t);
    if ($s) fclose($s);
}

echo "\n--- Full SMTP conversation using your config ---\n";
$remote = (MAIL_ENCRYPTION === 'ssl' ? 'ssl://' : '') . MAIL_HOST . ':' . MAIL_PORT;
$fp = @stream_socket_client($remote, $errno, $errstr, 12);
if (!$fp) exit("CONNECT FAILED: $errstr ($errno)\n=> This server blocks the port. Try the other port/encryption, or use a different mail route.\n");
stream_set_timeout($fp, 12);
$read = function () use ($fp) { $all = ''; do { $l = fgets($fp, 515); $all .= $l; } while ($l !== false && isset($l[3]) && $l[3] === '-'); echo "S: " . rtrim($all) . "\n"; return (string)$all; };
$send = function (string $c, bool $mask = false) use ($fp, $read) { echo "C: " . ($mask ? '[hidden]' : $c) . "\n"; fwrite($fp, $c . "\r\n"); return $read(); };
$read();
$send('EHLO localhost');
if (MAIL_ENCRYPTION === 'tls') {
    $send('STARTTLS');
    echo stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) ? "(TLS ok)\n" : "(TLS FAILED)\n";
    $send('EHLO localhost');
}
$send('AUTH LOGIN');
$send(base64_encode(MAIL_USERNAME), true);
$r = $send(base64_encode(MAIL_PASSWORD), true);
if (strpos($r, '235') !== 0) exit("\nLOGIN REJECTED => wrong App Password / username, or 2-Step Verification not on. Create a NEW App Password.\n");
$send('MAIL FROM:<' . MAIL_FROM . '>');
$send("RCPT TO:<$to>");
$send('DATA');
$msg = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\nTo: <$to>\r\nSubject: Library System mail test\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nIf you can read this, SMTP works.\r\n";
fwrite($fp, $msg . "\r\n.\r\n"); echo "C: [message]\n";
$r = $read();
$send('QUIT');
echo strpos($r, '250') === 0
    ? "\nGMAIL ACCEPTED THE MESSAGE. If it is not in the inbox of $to, check Spam; if it still does not arrive, that mailbox/provider is filtering or rejecting it.\n"
    : "\nGMAIL REFUSED THE MESSAGE (see the S: line above).\n";
