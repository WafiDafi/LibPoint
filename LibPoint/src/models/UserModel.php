<?php
final class UserModel
{
    public static function find(int $id): ?array
    {
        return Database::one("SELECT id, name, email, password, address, role FROM users WHERE id=?", 'i', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one("SELECT id, name, email, password, address, role FROM users WHERE email=?", 's', [$email]);
    }

    /** @return bool false if the email is already taken */
    public static function create(string $name, string $email, string $passwordHash, string $address): bool
    {
        try {
            Database::exec(
                "INSERT INTO users (name, email, password, address) VALUES (?,?,?,?)",
                'ssss', [$name, strtolower($email), $passwordHash, $address]
            );
            return true;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) return false;
            throw $e;
        }
    }

    public static function updateProfile(int $id, string $name, string $address): void
    {
        Database::exec("UPDATE users SET name=?, address=? WHERE id=?", 'ssi', [$name, $address, $id]);
    }

    public static function search(string $q = ''): array
    {
        if ($q === '') return Database::all("SELECT id, name, email, address, role FROM users ORDER BY name");
        $like = '%' . addcslashes($q, '%_\\') . '%';
        return Database::all(
            "SELECT id, name, email, address, role FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY name",
            'ss', [$like, $like]
        );
    }

    public static function members(): array
    {
        return Database::all("SELECT id, name, email FROM users WHERE role='user' ORDER BY name");
    }

    public static function setRole(int $id, string $role): void
    {
        Database::exec("UPDATE users SET role=? WHERE id=?", 'si', [$role, $id]);
    }

    /** @return bool false if the user still has related records */
    public static function delete(int $id): bool
    {
        try {
            Database::exec("DELETE FROM users WHERE id=?", 'i', [$id]);
            return true;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1451) return false; // foreign key constraint
            throw $e;
        }
    }

    public static function countMembers(): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM users WHERE role='user'");
    }
}
