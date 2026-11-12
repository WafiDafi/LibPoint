# Library Management System (OOP edition)

Requires PHP 8.1+ with mysqli (any current XAMPP).

## Setup
1. Upload so that `index.php`, `src/`, `config/`, `assets/` sit directly in `public_html/` (Hostinger) or `C:\xampp\htdocs\library\`.
2. (XAMPP only) Start Apache + MySQL, and create an empty database such as `library_db` in phpMyAdmin.
3. hPanel → Databases: create a database + user, open phpMyAdmin, **select that database**, then Import
   (the SQL files do not create a database):
   * **New install:** `database/database.sql`
   * **Upgrading the earlier version:** run, in order, `database/migration.sql`,
     `database/migration_copies.sql`, `database/migration_roles.sql` (back up first).
   * **Optional demo data:** then import `database/sample_data.sql` (once, right after `database.sql`).
     It adds 9 users (every role), 12 books with 32 copies, 12 loans (2 overdue, 1 due tomorrow,
     3 penalties) and 6 reservations. Sample accounts use `Password@123`; the admin keeps `Admin@12345`.
     OTP, pending sign-up and notification tables stay empty. 2FA codes go to each account's email,
     so give the accounts you want to log in with a real inbox first.
4. Edit `config/config.php` (database, mail, allowed email domain).
5. Open http://localhost/library/ — default System Admin: `admin@sanbeda-alabang.edu.ph` / `Admin@12345`.
   Change that email to a real school inbox first: login codes are emailed there.

## Accounts and roles
Only `@sanbeda-alabang.edu.ph` addresses can register or log in (`ALLOWED_EMAIL_DOMAIN` in the config).
Sign-up creates **no account** until the 6-digit code emailed to that address is entered.
New accounts are Members. Staff register like everyone else, then an IT Staff / System Admin
promotes them on the **Users** page.

| Capability                                           | Member | Librarian | Chief Librarian | IT Staff | System Admin |
|------------------------------------------------------|:------:|:---------:|:---------------:|:--------:|:------------:|
| Browse, reserve, view own loans                      |   ✓    |           |                 |          |              |
| View books and copies                                |        |     ✓     |        ✓        |    ✓     |      ✓       |
| Borrow, return, reservations, mark fees paid, reminders |     |     ✓     |        ✓        |    ✓     |      ✓       |
| Add / edit / delete books and copies                 |        |           |        ✓        |    ✓     |      ✓       |
| Reports and CSV export                               |        |           |        ✓        |    ✓     |      ✓       |
| Manage users; set roles up to Chief Librarian        |        |           |                 |    ✓     |      ✓       |
| Assign IT Staff / System Admin roles, change those accounts, delete users |  |  |  |  | ✓ |

All of this lives in `src/core/Permissions.php`. IT Staff and System Admin have the same access to every
page and feature, with one split: IT Staff manage Members, Librarians and Chief Librarians, while only a
System Admin can assign the IT Staff / System Admin roles, change those accounts, or delete users.

## Book IDs (no barcodes)
Every physical copy has a Book ID built from the book's id, e.g. `BK0007-02`.
Each loan records which Book ID was lent. Staff type or pick the Book ID; nothing is scanned or printed.

## Email notifications
Members are emailed when a book is due soon (within `DUE_REMINDER_DAYS`), overdue, or has an unpaid
fee (repeated every `FEE_REMINDER_DAYS`). A fee notice is also sent right after a late return.
* Daily job: `C:\xampp\php\php.exe C:\xampp\htdocs\library\notify.php` (Windows Task Scheduler) or
  `0 8 * * * php /path/to/library/notify.php` (cron).
* Or press **Send reminders now** on the staff dashboard.
* `notification_log` prevents repeat emails.

## Email setup
* With `MAIL_USERNAME` set, mail goes out through SMTP (Gmail: use an App Password).
* With it empty, emails are written to `storage/mail.log`, so you can copy codes while testing.

## Layout
```
index.php                  front controller  (?page=slug)
notify.php                 daily reminder job (CLI only)
config/config.php          settings
database/                  database.sql + migration*.sql
docs/swimlanes.html        swimlane diagrams for every module
src/core/                  Page (base class), Router, Layout, Permissions, Database, Session, Auth,
                           TwoFactor, SignupVerification, NotificationService, Mailer, Validator, Csrf
src/models/                UserModel, BookModel, CopyModel, BorrowModel, ReservationModel
src/pages/                 one class per member / auth page
src/pages/admin/           one class per staff page
```
Adding a page = create `src/pages/XxxPage.php` extending `Page`, add one line to `Router::ROUTES`.
