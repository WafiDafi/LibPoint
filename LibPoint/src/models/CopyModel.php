<?php
/** One row per physical copy of a book, each with its own identifier (e.g. BK0003-02). */
final class CopyModel
{
    public const STATUSES = ['available', 'lost', 'damaged']; // 'borrowed' is set only by a loan

    public static function find(int $id): ?array
    {
        return Database::one("SELECT * FROM book_copies WHERE id=?", 'i', [$id]);
    }

    /** All copies of a book, with the current borrower (if any). */
    public static function forBook(int $bookId): array
    {
        return Database::all(
            "SELECT c.*, bb.due_date, u.name AS borrower
             FROM book_copies c
             LEFT JOIN borrowed_books bb ON bb.copy_id=c.id AND bb.status='borrowed'
             LEFT JOIN users u ON u.id=bb.user_id
             WHERE c.book_id=? ORDER BY c.copy_code", 'i', [$bookId]
        );
    }

    /** [bookId => [[id, code], ...]] for the Borrow form's copy picker. */
    public static function availableMap(): array
    {
        $map = [];
        foreach (Database::all("SELECT id, book_id, copy_code FROM book_copies WHERE status='available' ORDER BY copy_code") as $r) {
            $map[$r['book_id']][] = ['id' => (int)$r['id'], 'code' => $r['copy_code']];
        }
        return $map;
    }

    /** Create $n new copies with the next free identifiers: BK0003-01, BK0003-02 ... */
    public static function add(int $bookId, int $n): void
    {
        $last = (int)Database::value(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(copy_code,'-',-1) AS UNSIGNED)),0) FROM book_copies WHERE book_id=?", 'i', [$bookId]
        );
        for ($i = 1; $i <= $n; $i++) {
            Database::exec("INSERT INTO book_copies (book_id, copy_code) VALUES (?,?)", 'is', [$bookId, sprintf('BK%04d-%02d', $bookId, $last + $i)]);
        }
    }

    /** A copy that is out on loan cannot be changed. */
    public static function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) return false;
        return Database::exec("UPDATE book_copies SET status=? WHERE id=? AND status<>'borrowed'", 'si', [$status, $id]) > 0;
    }

    /** @return bool false if the copy appears in any loan (history is kept) */
    public static function delete(int $id): bool
    {
        try {
            return Database::exec("DELETE FROM book_copies WHERE id=? AND status<>'borrowed'", 'i', [$id]) > 0;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1451) return false;
            throw $e;
        }
    }
}
