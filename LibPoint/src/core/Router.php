<?php
/** Maps ?page=slug to a Page class. Add a new page = add one line here. */
final class Router
{
    private const ROUTES = [
        // public / auth
        'login'              => 'LoginPage',
        'register'           => 'RegisterPage',
        'verify'             => 'VerifyOtpPage',
        'verify_signup'      => 'VerifySignupPage',
        'logout'             => 'LogoutPage',
        // member
        'home'               => 'HomePage',
        'books'              => 'BooksPage',
        'borrowed'           => 'BorrowedPage',
        'reservations'       => 'ReservationsPage',
        'profile'            => 'ProfilePage',
        // admin
        'admin_dashboard'    => 'AdminDashboardPage',
        'admin_books'        => 'AdminBooksPage',
        'admin_book_form'    => 'AdminBookFormPage',
        'admin_copies'       => 'AdminBookCopiesPage',
        'admin_borrow'       => 'AdminBorrowPage',
        'admin_borrowed'     => 'AdminBorrowedPage',
        'admin_returned'     => 'AdminReturnedPage',
        'admin_reservations' => 'AdminReservationsPage',
        'admin_users'        => 'AdminUsersPage',
        'admin_report'       => 'AdminReportPage',
    ];

    public static function dispatch(): void
    {
        $default = Auth::check() ? (Auth::isStaff() ? 'admin_dashboard' : 'home') : 'login';
        $slug = $_GET['page'] ?? $default;
        if (!is_string($slug) || !isset(self::ROUTES[$slug])) {
            http_response_code(404);
            (new NotFoundPage('not_found'))->run();
            return;
        }
        $class = self::ROUTES[$slug];
        (new $class($slug))->run();
    }
}
