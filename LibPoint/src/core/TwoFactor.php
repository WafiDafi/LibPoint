<?php
/** Email one-time-code second factor. Codes are stored hashed and expire. */
final class TwoFactor
{
    public static function pendingUserId(): int { return (int)Session::get('pending_2fa_user', 0); }

    /** Step 1 of login passed: create a code, email it, remember who is pending. */
    public static function begin(array $user): bool
    {
        Session::set('pending_2fa_user', (int)$user['id']);
        return self::issue($user);
    }

    public static function cancel(): void
    {
        if ($id = self::pendingUserId()) Database::exec("DELETE FROM login_otps WHERE user_id=?", 'i', [$id]);
        Session::forget('pending_2fa_user');
    }

    /** Seconds the user must still wait before requesting another code (0 = allowed). */
    public static function resendWait(): int
    {
        $age = Database::value("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) FROM login_otps WHERE user_id=?", 'i', [self::pendingUserId()]);
        if ($age === null) return 0;
        return max(0, OTP_RESEND_SECONDS - (int)$age);
    }

    public static function resend(): bool
    {
        $user = UserModel::find(self::pendingUserId());
        return $user ? self::issue($user) : false;
    }

    /** @return string|null null on success, otherwise an error message */
    public static function verify(string $code): ?string
    {
        $uid = self::pendingUserId();
        $row = Database::one("SELECT id, code_hash, attempts, expires_at > NOW() AS valid FROM login_otps WHERE user_id=?", 'i', [$uid]);

        if (!$row)            return 'No active code. Please request a new one.';
        if (!$row['valid'])   return 'That code has expired. Please request a new one.';
        if ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) return 'Too many wrong attempts. Please request a new code.';

        if (!password_verify($code, $row['code_hash'])) {
            Database::exec("UPDATE login_otps SET attempts = attempts + 1 WHERE id=?", 'i', [$row['id']]);
            $left = OTP_MAX_ATTEMPTS - (int)$row['attempts'] - 1;
            return $left > 0 ? "Incorrect code. $left attempt(s) left." : 'Incorrect code. Please request a new one.';
        }

        Database::exec("DELETE FROM login_otps WHERE user_id=?", 'i', [$uid]);
        Session::forget('pending_2fa_user');
        Auth::login($uid);
        return null;
    }

    private static function issue(array $user): bool
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($code, PASSWORD_DEFAULT);

        Database::exec("DELETE FROM login_otps WHERE user_id=?", 'i', [$user['id']]);
        Database::exec(
            "INSERT INTO login_otps (user_id, code_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))",
            'isi', [$user['id'], $hash, OTP_TTL_MINUTES]
        );

        $body = "Hello {$user['name']},\n\nYour " . APP_NAME . " verification code is:\n\n    $code\n\n"
              . "It expires in " . OTP_TTL_MINUTES . " minutes. If you did not try to sign in, ignore this email "
              . "and consider changing your password.\n";
        return Mailer::send($user['email'], APP_NAME . ' verification code', $body);
    }
}
