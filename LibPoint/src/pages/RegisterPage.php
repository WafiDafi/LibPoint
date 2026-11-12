<?php
final class RegisterPage extends Page
{
    private array $old = ['name' => '', 'email' => '', 'address' => ''];

    protected function access(): string { return self::GUEST; }
    protected function title(): string  { return 'Register'; }
    protected function layout(): string { return 'auth'; }

    protected function onPost(): void
    {
        $v = new Validator($_POST);
        $v->text('name', 'Full name', 2, 100, true, "/^[\p{L}][\p{L} .'\-]*$/u", 'Name may only contain letters, spaces, . \' and -.')
          ->email('email')->emailDomain('email')
          ->text('address', 'Address', 0, 255, false)
          ->password('password')
          ->matches('password_confirm', 'password', 'Passwords do not match.');

        $this->old = ['name' => $v->value('name'), 'email' => $v->value('email'), 'address' => $v->value('address')];
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        $email = strtolower($this->old['email']);
        if (UserModel::findByEmail($email)) {
            $this->errors['email'] = 'That email is already registered.';
            return;
        }

        $sent = SignupVerification::begin([
            'name' => $this->old['name'], 'email' => $email, 'address' => $this->old['address'],
            'password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT),
        ]);
        if (!$sent) {
            SignupVerification::cancel();
            $this->errors['email'] = 'We could not send the verification email. Please try again later.';
            return;
        }
        Session::flash('success', 'We sent a 6-digit code to your school email.');
        $this->redirect('verify_signup');
    }

    protected function render(): void
    {
        ?>
        <div class="auth-icon">📚</div>
        <div class="auth-title">Create Account</div>
        <div class="auth-sub">Fill in the form to register</div>

        <form method="POST" action="<?= e(url('register')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="form-group mb">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= e($this->old['name']) ?>" required minlength="2" maxlength="100" placeholder="Enter your full name">
            <?= $this->error('name') ?>
          </div>
          <div class="form-group mb">
            <label>School Email</label>
            <input type="email" name="email" value="<?= e($this->old['email']) ?>" required maxlength="150" placeholder="name@<?= e(ALLOWED_EMAIL_DOMAIN) ?>">
            <?= $this->error('email') ?>
          </div>
          <div class="form-group mb">
            <label>Address <span class="hint">(optional)</span></label>
            <textarea name="address" maxlength="255" placeholder="Your address"><?= e($this->old['address']) ?></textarea>
            <?= $this->error('address') ?>
          </div>
          <div class="form-group mb">
            <label>Password</label>
            <input type="password" name="password" required minlength="8" maxlength="72" placeholder="Create a password">
            <div class="hint">8+ characters with upper-case, lower-case and a number.</div>
            <?= $this->error('password') ?>
          </div>
          <div class="form-group mb-lg">
            <label>Confirm Password</label>
            <input type="password" name="password_confirm" required placeholder="Repeat your password">
            <?= $this->error('password_confirm') ?>
          </div>
          <button class="btn btn-blue btn-block" type="submit">Register</button>
          <p class="auth-foot">Already have an account? <a href="<?= e(url('login')) ?>">Login here</a></p>
        </form>
        <?php
    }
}
