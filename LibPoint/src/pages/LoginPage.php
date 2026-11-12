<?php
/** Step 1 of sign-in: email + password. On success an email code is sent (step 2 = VerifyOtpPage). */
final class LoginPage extends Page
{
    private string $email = '';

    protected function access(): string { return self::GUEST; }
    protected function title(): string  { return 'Login'; }
    protected function layout(): string { return 'auth'; }

    protected function onPost(): void
    {
        $v = new Validator($_POST);
        $v->email('email')->emailDomain('email')->required('password', 'Password');
        $this->email = $v->value('email');
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        $password = (string)$_POST['password'];
        $user = UserModel::findByEmail($this->email);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->errors['form'] = 'Invalid email or password.';
            return;
        }

        if (!TwoFactor::begin($user)) {
            TwoFactor::cancel();
            $this->errors['form'] = 'We could not send the verification email. Please try again later.';
            return;
        }
        Session::flash('success', 'We sent a 6-digit code to your email.');
        $this->redirect('verify');
    }

    protected function render(): void
    {
        ?>
        <div class="auth-icon">📚</div>
        <div class="auth-title">Library System</div>
        <div class="auth-sub">Sign in to your account</div>
        <?php if (isset($this->errors['form'])): ?><div class="alert alert-error">❌ <?= e($this->errors['form']) ?></div><?php endif; ?>

        <form method="POST" action="<?= e(url('login')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="form-group mb">
            <label>Email</label>
            <input type="email" name="email" value="<?= e($this->email) ?>" required maxlength="150" placeholder="name@<?= e(ALLOWED_EMAIL_DOMAIN) ?>" autofocus>
            <?= $this->error('email') ?>
          </div>
          <div class="form-group mb-lg">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
            <?= $this->error('password') ?>
          </div>
          <button class="btn btn-blue btn-block" type="submit">Login</button>
          <p class="auth-foot">No account yet? <a href="<?= e(url('register')) ?>">Register here</a></p>
        </form>
        <?php
    }
}
