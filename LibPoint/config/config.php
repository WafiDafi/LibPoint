<?php
// ============================================================
// APPLICATION CONFIGURATION  (edit these values for your setup)
// ============================================================

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');

// --- App ---
define('APP_NAME', 'LibraryMS');
define('BASE_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Manila');

// --- Library rules ---
define('PENALTY_PER_DAY',   10.00); // pesos per overdue day
define('MAX_ACTIVE_BORROWS', 3);    // max books one member can hold at once
define('MAX_LOAN_DAYS',      30);   // longest allowed loan period

// --- Accounts: only this email domain may register or sign in ---
define('ALLOWED_EMAIL_DOMAIN', 'sanbeda-alabang.edu.ph');

// --- Email notifications (run notify.php daily, or use the dashboard button) ---
define('DUE_REMINDER_DAYS', 1);     // remind when a book is due within this many days
define('FEE_REMINDER_DAYS', 7);     // repeat unpaid-fee reminders every N days

// --- Email one-time codes (sign-up verification AND login 2FA) ---
define('OTP_TTL_MINUTES',   10);    // code validity
define('OTP_MAX_ATTEMPTS',   5);    // wrong guesses before the code is burned
define('OTP_RESEND_SECONDS', 60);   // cooldown between "resend" requests


define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_ENCRYPTION', 'tls');          // 'tls' (STARTTLS, port 587) or 'ssl' (port 465)
define('MAIL_USERNAME',   '');
define('MAIL_PASSWORD',   '');
define('MAIL_FROM',       MAIL_USERNAME !== '' ? MAIL_USERNAME : 'no-reply@library.local');
define('MAIL_FROM_NAME',  APP_NAME);
