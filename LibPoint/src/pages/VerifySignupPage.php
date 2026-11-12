<?php
/** Sign-up step 2: enter the code emailed to the school address to create the account. */
final class VerifySignupPage extends Page
{
    protected function access(): string { return self::GUEST; }
    protected function title(): string  { return 'Verify your email'; }
    protected function layout(): string { return 'auth'; }

    protected function beforeRender(): void
    {
        if (SignupVerification::pendingEmail() === '') $this->redirect('register');
    }

    protected function onPost(): void
    {
        if (SignupVerification::pendingEmail() === '') $this->redirect('register');
        $do = $_POST['do'] ?? 'verify';

        if ($do === 'cancel') { SignupVerification::cancel(); $this->redirect('register'); }

        if ($do === 'resend') {
            $wait = SignupVerification::resendWait();
            if ($wait > 0)                       Session::flash('error', "Please wait $wait second(s) before requesting another code.");
            elseif (SignupVerification::resend()) Session::flash('success', 'A new code has been sent.');
            else                                  Session::flash('error', 'We could not send the email. Please try again later.');
            $this->redirect('verify_signup');
        }

        $v = new Validator($_POST);
        $v->text('code', 'Code', 1, 6, true, '/^\d{6}$/', 'The code is 6 digits.');
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        $err = SignupVerification::verify($v->value('code'));
        if ($err !== null) { $this->errors['code'] = $err; return; }

        Session::flash('success', 'Email verified! Your account is ready. You can log in now.');
        $this->redirect('login');
    }

    protected function render(): void
    {
        ?>
        <div class="auth-icon">✉️</div>
        <div class="auth-title">Verify your email</div>
        <div class="auth-sub">We sent a 6-digit code to <strong><?= e(SignupVerification::pendingEmail()) ?></strong>. Enter it to finish signing up. It expires in <?= (int)OTP_TTL_MINUTES ?> minutes.</div>

        <form method="POST" action="<?= e(url('verify_signup')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="form-group mb-lg">
            <label>Verification Code</label>
            <input type="text" name="code" class="otp-input" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" required autofocus placeholder="••••••">
            <?= $this->error('code') ?>
          </div>
          <button class="btn btn-blue btn-block" type="submit" name="do" value="verify">Verify &amp; Create Account</button>
        </form>
        <form method="POST" action="<?= e(url('verify_signup')) ?>" class="auth-actions">
          <?= csrf_field() ?>
          <button class="btn btn-gray btn-sm" type="submit" name="do" value="resend">Resend code</button>
          <button class="btn btn-gray btn-sm" type="submit" name="do" value="cancel">Use a different email</button>
        </form>
        <?php
    }
}
