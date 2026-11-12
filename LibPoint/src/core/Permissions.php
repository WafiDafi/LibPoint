<?php
/**
 * Single source of truth for "who can do what".
 * System Admin and IT Staff share the same page/feature permissions ($SYSTEM), with ONE exception:
 * only a System Admin may assign the Admin / IT Staff roles, change those accounts, or delete users.
 * IT Staff manage Members, Librarians and Chief Librarians.
 * Librarian and Chief Librarian differ: only the chief manages the catalog and sees reports.
 */
final class Permissions
{
    public const ROLES = [
        'user'            => 'Member',
        'librarian'       => 'Librarian',
        'chief_librarian' => 'Chief Librarian',
        'it_staff'        => 'IT Staff',
        'admin'           => 'System Admin',
    ];

    public const SYSTEM  = ['it_staff', 'admin'];
    private const STAFF  = ['librarian', 'chief_librarian', 'it_staff', 'admin'];

    /** @return string[] roles allowed for a permission */
    private static function roles(string $perm): array
    {
        return match ($perm) {
            'catalog_view', 'circulation', 'fees', 'notify' => self::STAFF,
            'catalog_manage', 'reports'                     => array_merge(['chief_librarian'], self::SYSTEM),
            'users_manage'                                  => self::SYSTEM,
            'users_delete'                                  => ['admin'],
            default                                         => [],
        };
    }

    /** Roles an actor may assign (and the only accounts they may modify). */
    public static function assignableRoles(string $actor): array
    {
        return match ($actor) {
            'admin'    => array_keys(self::ROLES),
            'it_staff' => ['user', 'librarian', 'chief_librarian'],
            default    => [],
        };
    }

    /** May $actor change the account of someone who currently holds $targetRole? */
    public static function canManageAccount(string $actor, string $targetRole): bool
    {
        return in_array($targetRole, self::assignableRoles($actor), true);
    }

    public static function can(string $perm, string $role): bool { return in_array($role, self::roles($perm), true); }
    public static function isStaff(string $role): bool           { return in_array($role, self::STAFF, true); }
    public static function label(string $role): string           { return self::ROLES[$role] ?? ucfirst($role); }
}
