<?php
/** Step 2 of sign-in: enter the 6-digit code that was emailed. */
final class VerifyOtpPage extends Page
{
    protected function access(): string { return self::GUEST; }
    protected function title(): string  { return 'Verify your email'; }
    protected function layout(): string { return 'auth'; }

    protected function beforeRender(): void
    {
        if (!TwoFactor::pendingUserId()) $this->redirect('login');
    }

    protected function onPost(): void
    {
        if (!TwoFactor::pendingUserId()) $this->redirect('login');
        $do = $_POST['do'] ?? 'verify';

        if ($do === 'cancel') {
            TwoFactor::cancel();
            $this->redirect('login');
        }

        if ($do === 'resend') {
            $wait = TwoFactor::resendWait();
            if ($wait > 0) {
                Session::flash('error', "Please wait $wait second(s) before requesting another code.");
            } elseif (TwoFactor::resend()) {
                Session::flash('success', 'A new code has been sent.');
            } else {
                Session::flash('error', 'We could not send the email. Please try again later.');
            }
            $this->redirect('verify');
        }

        $v = new Validator($_POST);
        $v->text('code', 'Code', 1, 6, true, '/^\d{6}$/', 'The code is 6 digits.');
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        $err = TwoFactor::verify($v->value('code'));
        if ($err !== null) { $this->errors['code'] = $err; return; }

        $this->redirect(Auth::isStaff() ? 'admin_dashboard' : 'home');
    }

    protected function render(): void
    {
        ?>
        <div class="auth-icon">🔐</div>
        <div class="auth-title">Check your email</div>
        <div class="auth-sub">Enter the 6-digit verification code we sent you. It expires in <?= (int)OTP_TTL_MINUTES ?> minutes.</div>

        <form method="POST" action="<?= e(url('verify')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="form-group mb-lg">
            <label>Verification Code</label>
            <input type="text" name="code" class="otp-input" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" required autofocus placeholder="••••••">
            <?= $this->error('code') ?>
          </div>
          <button class="btn btn-blue btn-block" type="submit" name="do" value="verify">Verify &amp; Sign In</button>
        </form>

        <form method="POST" action="<?= e(url('verify')) ?>" class="auth-actions">
          <?= csrf_field() ?>
          <button class="btn btn-gray btn-sm" type="submit" name="do" value="resend">Resend code</button>
          <button class="btn btn-gray btn-sm" type="submit" name="do" value="cancel">Back to login</button>
        </form>
        <?php
    }
}
