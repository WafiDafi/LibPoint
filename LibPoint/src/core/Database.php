<?php
/** Thin wrapper around mysqli. Every query is a prepared statement. */
final class Database
{
    private static ?mysqli $conn = null;

    public static function conn(): mysqli
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                self::$conn->set_charset('utf8mb4');
            } catch (mysqli_sql_exception $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                exit('Database connection failed. Check config/config.php.');
            }
        }
        return self::$conn;
    }

    private static function run(string $sql, string $types, array $params): mysqli_stmt
    {
        $stmt = self::conn()->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(string $sql, string $types = '', array $params = []): array
    {
        return self::run($sql, $types, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function one(string $sql, string $types = '', array $params = []): ?array
    {
        return self::run($sql, $types, $params)->get_result()->fetch_assoc() ?: null;
    }

    public static function value(string $sql, string $types = '', array $params = [])
    {
        $row = self::one($sql, $types, $params);
        return $row ? reset($row) : null;
    }

    /** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
    public static function exec(string $sql, string $types = '', array $params = []): int
    {
        $stmt = self::run($sql, $types, $params);
        return $stmt->affected_rows;
    }

    public static function insertId(): int { return (int)self::conn()->insert_id; }
    public static function begin(): void    { self::conn()->begin_transaction(); }
    public static function commit(): void   { self::conn()->commit(); }
    public static function rollback(): void { self::conn()->rollback(); }
}
