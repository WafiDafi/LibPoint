<?php
/** `copies` in every row = number of AVAILABLE copies; `total_copies` = all physical copies. */
final class BookModel
{
    private const SELECT = "SELECT b.*,
        (SELECT COUNT(*) FROM book_copies c WHERE c.book_id=b.id AND c.status='available') AS copies,
        (SELECT COUNT(*) FROM book_copies c WHERE c.book_id=b.id) AS total_copies
        FROM books b";

    public static function find(int $id): ?array
    {
        return Database::one(self::SELECT . " WHERE b.id=?", 'i', [$id]);
    }

    public static function search(string $q = ''): array
    {
        if ($q === '') return Database::all(self::SELECT . " ORDER BY b.title");
        $like = '%' . addcslashes($q, '%_\\') . '%';
        return Database::all(self::SELECT . " WHERE b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? ORDER BY b.title", 'sss', [$like, $like, $like]);
    }

    public static function available(): array
    {
        return Database::all("SELECT * FROM (" . self::SELECT . ") t WHERE t.copies > 0 ORDER BY t.title");
    }

    public static function isbnTaken(string $isbn, int $exceptId = 0): bool
    {
        return Database::value("SELECT COUNT(*) FROM books WHERE isbn=? AND id<>?", 'si', [$isbn, $exceptId]) > 0;
    }

    /** Creates the title and its first $copies physical copies. @return int new book id */
    public static function create(string $title, string $author, string $isbn, float $fee, int $copies): int
    {
        Database::begin();
        try {
            Database::exec("INSERT INTO books (title, author, isbn, fee_per_day) VALUES (?,?,?,?)", 'sssd', [$title, $author, $isbn, $fee]);
            $id = Database::insertId();
            CopyModel::add($id, $copies);
            Database::commit();
            return $id;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public static function update(int $id, string $title, string $author, string $isbn, float $fee): void
    {
        Database::exec("UPDATE books SET title=?, author=?, isbn=?, fee_per_day=? WHERE id=?", 'sssdi', [$title, $author, $isbn, $fee, $id]);
    }

    /** @return bool false if any copy has been borrowed or the book is reserved */
    public static function delete(int $id): bool
    {
        try {
            Database::exec("DELETE FROM books WHERE id=?", 'i', [$id]);
            return true;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1451) return false;
            throw $e;
        }
    }

    public static function totalCopies(): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM book_copies WHERE status='available'");
    }

    public static function countTitles(): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM books");
    }
}
