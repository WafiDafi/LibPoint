<?php
final class ReservationModel
{
    private const SELECT = "SELECT r.*, u.name AS user_name, b.title AS book_title,
        (SELECT COUNT(*) FROM book_copies c WHERE c.book_id=b.id AND c.status='available') AS copies
        FROM reservations r JOIN users u ON r.user_id=u.id JOIN books b ON r.book_id=b.id";

    public static function forUser(int $userId): array
    {
        return Database::all(self::SELECT . " WHERE r.user_id=? ORDER BY r.reserved_at DESC", 'i', [$userId]);
    }

    public static function all(): array
    {
        return Database::all(self::SELECT . " ORDER BY r.reserved_at DESC");
    }

    public static function hasActive(int $userId, int $bookId): bool
    {
        return Database::value("SELECT COUNT(*) FROM reservations WHERE user_id=? AND book_id=? AND status IN ('pending','ready')", 'ii', [$userId, $bookId]) > 0;
    }

    public static function create(int $userId, int $bookId): void
    {
        Database::exec("INSERT INTO reservations (user_id, book_id) VALUES (?,?)", 'ii', [$userId, $bookId]);
    }

    /** Members may only cancel their own; pass null as $userId for admins. */
    public static function cancel(int $id, ?int $userId): bool
    {
        $sql = "UPDATE reservations SET status='cancelled' WHERE id=? AND status IN ('pending','ready')";
        return $userId === null
            ? Database::exec($sql, 'i', [$id]) > 0
            : Database::exec($sql . " AND user_id=?", 'ii', [$id, $userId]) > 0;
    }

    public static function markReady(int $id): bool
    {
        return Database::exec("UPDATE reservations SET status='ready' WHERE id=? AND status='pending'", 'i', [$id]) > 0;
    }

    /** When a member borrows a book, close their open reservation for it. */
    public static function claim(int $userId, int $bookId): void
    {
        Database::exec("UPDATE reservations SET status='claimed' WHERE user_id=? AND book_id=? AND status IN ('pending','ready') LIMIT 1", 'ii', [$userId, $bookId]);
    }

    public static function countPending(): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM reservations WHERE status='pending'");
    }
}
