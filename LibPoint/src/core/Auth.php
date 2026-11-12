<?php
/** Who is logged in? Role is always re-read from the database. */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function id(): int { return (int)Session::get('user_id', 0); }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = self::id();
            self::$user = $id ? UserModel::find($id) : null;
            if ($id && !self::$user) Session::forget('user_id'); // account was deleted
        }
        return self::$user;
    }

    public static function check(): bool   { return self::user() !== null; }
    public static function role(): string  { return self::user()['role'] ?? ''; }
    public static function isStaff(): bool { return Permissions::isStaff(self::role()); }
    public static function can(string $perm): bool { return Permissions::can($perm, self::role()); }

    /** Called only after password AND email code have both been verified. */
    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        self::$loaded = false;
        self::$user = null;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$loaded = false;
        self::$user = null;
    }
}
