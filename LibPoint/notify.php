<?php
// Daily reminder job. Run from the command line / Task Scheduler / cron, e.g.
//   Windows (XAMPP):  C:\xampp\php\php.exe C:\xampp\htdocs\library\notify.php
//   Linux cron:       0 8 * * *  php /var/www/library/notify.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
require __DIR__ . '/src/bootstrap.php';
$r = NotificationService::runAll();
echo date('Y-m-d H:i:s') . " — emails: {$r['emails']}, items: {$r['items']}, failed: {$r['failed']}\n";
