<?php
/**
 * Email verification for new sign-ups. Nothing is written to `users` until the
 * emailed code has been entered; until then the data waits in `pending_signups`.
 */
final class SignupVerification
{
    public static function pendingEmail(): string { return (string)Session::get('pending_signup_email', ''); }

    /** @param array{name:string,email:string,address:string,password_hash:string} $d */
    public static function begin(array $d): bool
    {
        Database::exec("DELETE FROM pending_signups WHERE email=? OR expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)", 's', [$d['email']]);
        Database::exec(
            "INSERT INTO pending_signups (email, name, address, password_hash, code_hash, expires_at) VALUES (?,?,?,?, '', NOW())",
            'ssss', [$d['email'], $d['name'], $d['address'], $d['password_hash']]
        );
        Session::set('pending_signup_email', $d['email']);
        return self::issue($d['email'], $d['name']);
    }

    public static function cancel(): void
    {
        if ($e = self::pendingEmail()) Database::exec("DELETE FROM pending_signups WHERE email=?", 's', [$e]);
        Session::forget('pending_signup_email');
    }

    public static function resendWait(): int
    {
        $age = Database::value("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) FROM pending_signups WHERE email=?", 's', [self::pendingEmail()]);
        return $age === null ? 0 : max(0, OTP_RESEND_SECONDS - (int)$age);
    }

    public static function resend(): bool
    {
        $row = Database::one("SELECT email, name FROM pending_signups WHERE email=?", 's', [self::pendingEmail()]);
        return $row ? self::issue($row['email'], $row['name']) : false;
    }

    /** @return string|null null on success (account created), otherwise an error message */
    public static function verify(string $code): ?string
    {
        $row = Database::one("SELECT *, expires_at > NOW() AS valid FROM pending_signups WHERE email=?", 's', [self::pendingEmail()]);
        if (!$row)          return 'No pending sign-up found. Please register again.';
        if (!$row['valid']) return 'That code has expired. Please request a new one.';
        if ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) return 'Too many wrong attempts. Please request a new code.';

        if (!password_verify($code, $row['code_hash'])) {
            Database::exec("UPDATE pending_signups SET attempts = attempts + 1 WHERE id=?", 'i', [$row['id']]);
            $left = OTP_MAX_ATTEMPTS - (int)$row['attempts'] - 1;
            return $left > 0 ? "Incorrect code. $left attempt(s) left." : 'Incorrect code. Please request a new one.';
        }

        $created = UserModel::create($row['name'], $row['email'], $row['password_hash'], (string)$row['address']);
        Database::exec("DELETE FROM pending_signups WHERE id=?", 'i', [$row['id']]);
        Session::forget('pending_signup_email');
        return $created ? null : 'That email is already registered. Please log in.';
    }

    private static function issue(string $email, string $name): bool
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Database::exec(
            "UPDATE pending_signups SET code_hash=?, attempts=0, created_at=NOW(), expires_at=DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE email=?",
            'sis', [password_hash($code, PASSWORD_DEFAULT), OTP_TTL_MINUTES, $email]
        );
        $body = "Hello $name,\n\nUse this code to finish creating your " . APP_NAME . " account:\n\n    $code\n\n"
              . "It expires in " . OTP_TTL_MINUTES . " minutes. If you did not sign up, ignore this email.\n";
        return Mailer::send($email, APP_NAME . ' sign-up verification code', $body);
    }
}
