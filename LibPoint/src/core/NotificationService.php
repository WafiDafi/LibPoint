<?php
/**
 * Emails members about: books due soon, overdue books, and unpaid fees.
 * One email per member per run. `notification_log` stops duplicate reminders.
 *   due soon : once per day while due within DUE_REMINDER_DAYS
 *   overdue  : once per day while the book is still out
 *   fee      : on late return, then every FEE_REMINDER_DAYS until paid
 */
final class NotificationService
{
    private const BASE = "SELECT bb.id, bb.due_date, bb.penalty, u.id AS uid, u.name, u.email, b.title, cp.copy_code
        FROM borrowed_books bb JOIN users u ON u.id=bb.user_id JOIN books b ON b.id=bb.book_id
        LEFT JOIN book_copies cp ON cp.id=bb.copy_id";

    /** @return array{emails:int,items:int,failed:int} */
    public static function runAll(): array
    {
        $byUser = [];
        $add = function (array $rows, string $type) use (&$byUser) {
            foreach ($rows as $r) {
                $byUser[$r['uid']]['user'] = $r;
                $byUser[$r['uid']][$type][] = $r;
            }
        };

        $add(Database::all(self::BASE . " WHERE bb.status='borrowed' AND bb.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND NOT EXISTS (SELECT 1 FROM notification_log n WHERE n.loan_id=bb.id AND n.type='due_soon' AND n.sent_on=CURDATE())", 'i', [DUE_REMINDER_DAYS]), 'due_soon');
        $add(Database::all(self::BASE . " WHERE bb.status='borrowed' AND bb.due_date < CURDATE()
             AND NOT EXISTS (SELECT 1 FROM notification_log n WHERE n.loan_id=bb.id AND n.type='overdue' AND n.sent_on=CURDATE())"), 'overdue');
        $add(Database::all(self::BASE . " WHERE bb.status='returned' AND bb.penalty>0 AND bb.date_paid IS NULL
             AND NOT EXISTS (SELECT 1 FROM notification_log n WHERE n.loan_id=bb.id AND n.type='fee' AND n.sent_on > DATE_SUB(CURDATE(), INTERVAL ? DAY))", 'i', [FEE_REMINDER_DAYS]), 'fee');

        $out = ['emails' => 0, 'items' => 0, 'failed' => 0];
        foreach ($byUser as $g) {
            $u = $g['user'];
            $lines = [];
            foreach ($g['due_soon'] ?? [] as $r) $lines[] = '• DUE SOON: ' . self::label($r) . ' is due on ' . $r['due_date'] . '.';
            foreach ($g['overdue'] ?? [] as $r) {
                $pen = BorrowModel::penalty($r['due_date']);
                $lines[] = '• OVERDUE: ' . self::label($r) . ' was due on ' . $r['due_date'] . '. Penalty so far: ' . peso($pen) . ' (' . peso(PENALTY_PER_DAY) . ' per day).';
            }
            foreach ($g['fee'] ?? [] as $r) $lines[] = '• UNPAID FEE: ' . self::label($r) . ' has an unpaid penalty of ' . peso($r['penalty']) . '.';

            $body = "Hello {$u['name']},\n\nThis is a reminder from " . APP_NAME . ":\n\n" . implode("\n", $lines)
                  . "\n\nPlease return books and settle fees at the library desk. Thank you!\n";
            if (Mailer::send($u['email'], APP_NAME . ' reminder: books due / fees', $body)) {
                $out['emails']++;
                foreach (['due_soon', 'overdue', 'fee'] as $type) foreach ($g[$type] ?? [] as $r) {
                    Database::exec("INSERT IGNORE INTO notification_log (loan_id, user_id, type, sent_on) VALUES (?,?,?,CURDATE())", 'iis', [$r['id'], $u['uid'], $type]);
                    $out['items']++;
                }
            } else {
                $out['failed']++;
            }
        }
        return $out;
    }

    /** Sent right after a late return creates a penalty. $loan comes from BorrowModel::find(). */
    public static function feeNotice(array $loan, float $penalty): void
    {
        $body = "Hello {$loan['user_name']},\n\nYou returned " . self::label($loan) . " after its due date ({$loan['due_date']}).\n"
              . "A penalty of " . peso($penalty) . " was recorded. Please settle it at the library desk.\n";
        if (Mailer::send($loan['user_email'], APP_NAME . ' fee notice', $body)) {
            Database::exec("INSERT IGNORE INTO notification_log (loan_id, user_id, type, sent_on) VALUES (?,?,'fee',CURDATE())", 'ii', [$loan['id'], $loan['user_id']]);
        }
    }

    private static function label(array $r): string
    {
        return '“' . ($r['title'] ?? $r['book_title']) . '”' . (!empty($r['copy_code']) ? ' (Book ID ' . $r['copy_code'] . ')' : '');
    }
}
