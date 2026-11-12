<?php
final class NotFoundPage extends Page
{
    protected function access(): string { return self::PUBLIC; }
    protected function title(): string  { return 'Page not found'; }
    protected function layout(): string { return 'auth'; }

    protected function render(): void
    {
        $home = Auth::check() ? (Auth::isStaff() ? 'admin_dashboard' : 'home') : 'login';
        echo '<div class="auth-icon">🧭</div><div class="auth-title">Page not found</div>'
           . '<div class="auth-sub">That page does not exist.</div>'
           . '<a class="btn btn-blue btn-block" href="' . e(url($home)) . '">Go back</a>';
    }
}
