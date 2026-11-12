<?php
final class BorrowModel
{
    private const SELECT = "SELECT bb.*, u.name AS user_name, u.email AS user_email, b.title AS book_title, b.author, cp.copy_code
        FROM borrowed_books bb JOIN users u ON bb.user_id=u.id JOIN books b ON bb.book_id=b.id
        LEFT JOIN book_copies cp ON cp.id=bb.copy_id";

    public static function find(int $id): ?array
    {
        return Database::one(self::SELECT . " WHERE bb.id=?", 'i', [$id]);
    }

    public static function active(?int $userId = null, ?int $limit = null): array
    {
        $sql = self::SELECT . " WHERE bb.status='borrowed'" . ($userId ? " AND bb.user_id=?" : "") . " ORDER BY bb.date_borrowed DESC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        return $userId ? Database::all($sql, 'i', [$userId]) : Database::all($sql);
    }

    public static function returned(): array
    {
        return Database::all(self::SELECT . " WHERE bb.status='returned' ORDER BY bb.date_returned DESC");
    }

    public static function everything(): array
    {
        return Database::all(self::SELECT . " ORDER BY bb.date_borrowed DESC");
    }

    public static function countActiveForUser(int $userId): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM borrowed_books WHERE user_id=? AND status='borrowed'", 'i', [$userId]);
    }

    public static function userHasActive(int $userId, int $bookId): bool
    {
        return Database::value("SELECT COUNT(*) FROM borrowed_books WHERE user_id=? AND book_id=? AND status='borrowed'", 'ii', [$userId, $bookId]) > 0;
    }

    public static function penalty(string $dueDate, ?string $returnedDate = null): float
    {
        $check = $returnedDate ? new DateTime($returnedDate) : new DateTime('today');
        $due   = new DateTime($dueDate);
        return $check > $due ? $due->diff($check)->days * PENALTY_PER_DAY : 0.0;
    }

    /**
     * Lends one specific physical copy. $copyId = 0 picks the first available copy.
     * @return string|null the copy identifier, or null if no copy could be reserved
     */
    public static function issue(int $userId, array $book, string $borrowed, string $due, int $copyId = 0): ?string
    {
        $days = max(1, (new DateTime($due))->diff(new DateTime($borrowed))->days);
        $fee  = (float)$book['fee_per_day'] * $days;

        Database::begin();
        try {
            $copy = $copyId > 0
                ? Database::one("SELECT id, copy_code FROM book_copies WHERE id=? AND book_id=? AND status='available' FOR UPDATE", 'ii', [$copyId, $book['id']])
                : Database::one("SELECT id, copy_code FROM book_copies WHERE book_id=? AND status='available' ORDER BY copy_code LIMIT 1 FOR UPDATE", 'i', [$book['id']]);
            if (!$copy) { Database::rollback(); return null; }

            Database::exec("UPDATE book_copies SET status='borrowed' WHERE id=?", 'i', [$copy['id']]);
            Database::exec(
                "INSERT INTO borrowed_books (user_id, book_id, copy_id, date_borrowed, due_date, fee) VALUES (?,?,?,?,?,?)",
                'iiissd', [$userId, $book['id'], $copy['id'], $borrowed, $due, $fee]
            );
            ReservationModel::claim($userId, (int)$book['id']);
            Database::commit();
            return $copy['copy_code'];
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /** @return float|null the penalty, or null if it was already returned */
    public static function returnBook(array $loan, string $returnedDate): ?float
    {
        $pen = self::penalty($loan['due_date'], $returnedDate);

        Database::begin();
        try {
            $n = Database::exec(
                "UPDATE borrowed_books SET date_returned=?, penalty=?, status='returned' WHERE id=? AND status='borrowed'",
                'sdi', [$returnedDate, $pen, $loan['id']]
            );
            if ($n < 1) { Database::rollback(); return null; }
            if ($loan['copy_id']) Database::exec("UPDATE book_copies SET status='available' WHERE id=? AND status='borrowed'", 'i', [$loan['copy_id']]);
            Database::commit();
            return $pen;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function markPaid(int $id): bool
    {
        return Database::exec("UPDATE borrowed_books SET date_paid=CURDATE() WHERE id=? AND penalty>0 AND date_paid IS NULL", 'i', [$id]) > 0;
    }

    public static function stats(): array
    {
        return [
            'borrowed' => (int)Database::value("SELECT COUNT(*) FROM borrowed_books WHERE status='borrowed'"),
            'returned' => (int)Database::value("SELECT COUNT(*) FROM borrowed_books WHERE status='returned'"),
            'overdue'  => (int)Database::value("SELECT COUNT(*) FROM borrowed_books WHERE status='borrowed' AND due_date < CURDATE()"),
            'unpaid'   => (float)Database::value("SELECT COALESCE(SUM(penalty),0) FROM borrowed_books WHERE date_paid IS NULL AND penalty>0"),
            'collected'=> (float)Database::value("SELECT COALESCE(SUM(penalty),0) FROM borrowed_books WHERE date_paid IS NOT NULL"),
        ];
    }

    public static function topBorrowers(int $limit = 5): array
    {
        return Database::all(
            "SELECT u.name, COUNT(*) AS total FROM borrowed_books bb JOIN users u ON u.id=bb.user_id
             GROUP BY u.id, u.name ORDER BY total DESC, u.name LIMIT " . (int)$limit
        );
    }
}
