<?php
/** Shared HTML shell: <head>, navigation, flash messages, footer. */
final class Layout
{
    private const MEMBER_NAV = [
        'home'         => '🏠 Home',
        'books'        => '📖 Books',
        'borrowed'     => '📋 My Borrowed',
        'reservations' => '⏳ Reservations',
        'profile'      => '👤 Profile',
    ];

    /** slug => [label, permission needed (null = any staff)] */
    private const STAFF_NAV = [
        'admin_dashboard'    => ['🏠 Dashboard', null],
        'admin_books'        => ['📚 Books', 'catalog_view'],
        'admin_borrowed'     => ['📋 Borrowed', 'circulation'],
        'admin_returned'     => ['✅ Returned', 'circulation'],
        'admin_reservations' => ['⏳ Reservations', 'circulation'],
        'admin_users'        => ['👥 Users', 'users_manage'],
        'admin_report'       => ['📊 Reports', 'reports'],
        'profile'            => ['👤 Profile', null],
    ];

    /** @param string $type 'app' (nav + container) or 'auth' (centered card) */
    public static function open(string $title, string $type, string $active): void
    {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>' . e($title) . ' · ' . e(APP_NAME) . '</title>'
           . '<link rel="stylesheet" href="assets/style.css"></head><body>';

        if ($type === 'auth') {
            echo '<div class="auth-page"><div class="auth-card">';
            self::flashes();
            return;
        }

        $staff = Auth::isStaff();
        $items = self::MEMBER_NAV;
        if ($staff) {
            $items = [];
            foreach (self::STAFF_NAV as $slug => [$label, $perm]) {
                if ($perm === null || Auth::can($perm)) $items[$slug] = $label;
            }
        }
        $home = $staff ? 'admin_dashboard' : 'home';

        echo '<nav><a class="nav-brand" href="' . e(url($home)) . '">📚 ' . e(APP_NAME)
           . ($staff ? ' <span class="badge role-admin">' . e(Permissions::label(Auth::role())) . '</span>' : '') . '</a><div class="nav-links">';
        foreach ($items as $slug => $label) {
            echo '<a href="' . e(url($slug)) . '"' . ($slug === $active ? ' class="active"' : '') . '>' . e($label) . '</a>';
        }
        echo '<form method="POST" action="' . e(url('logout')) . '">' . csrf_field()
           . '<button type="submit" class="nav-logout">Logout</button></form></div></nav><div class="container">';
        self::flashes();
    }

    public static function close(string $type): void
    {
        echo $type === 'auth' ? '</div></div>' : '</div>';
        echo '<script src="assets/app.js"></script></body></html>';
    }

    private static function flashes(): void
    {
        foreach (Session::pullFlash() as $f) {
            $ok = $f['type'] === 'success';
            echo '<div class="alert ' . ($ok ? 'alert-success' : 'alert-error') . '">' . ($ok ? '✅ ' : '❌ ') . e($f['message']) . '</div>';
        }
    }
}
