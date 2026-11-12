<?php
/** Shared by members and admins. Phone number has been removed. */
final class ProfilePage extends Page
{
    private array $old = [];

    protected function access(): string { return self::ANY; }
    protected function title(): string  { return 'My Profile'; }

    protected function onPost(): void
    {
        $v = new Validator($_POST);
        $v->text('name', 'Full name', 2, 100, true, "/^[\p{L}][\p{L} .'\-]*$/u", 'Name may only contain letters, spaces, . \' and -.')
          ->text('address', 'Address', 0, 255, false);
        $this->old = ['name' => $v->value('name'), 'address' => $v->value('address')];
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        UserModel::updateProfile(Auth::id(), $this->old['name'], $this->old['address']);
        Session::flash('success', 'Profile updated!');
        $this->redirect('profile');
    }

    protected function render(): void
    {
        $u    = Auth::user();
        $name = $this->old['name'] ?? $u['name'];
        $addr = $this->old['address'] ?? ($u['address'] ?? '');
        ?>
        <div class="page-title">👤 My Profile</div>
        <div class="card">
          <div class="profile-top">
            <div class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></div>
            <div>
              <div class="profile-name"><?= e($u['name']) ?></div>
              <div class="muted small"><?= e($u['email']) ?> · <?= e(Permissions::label($u['role'])) ?></div>
            </div>
          </div>
          <form method="POST" action="<?= e(url('profile')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid mb">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?= e($name) ?>" required minlength="2" maxlength="100">
                <?= $this->error('name') ?>
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= e($u['email']) ?>" disabled class="disabled">
              </div>
              <div class="form-group">
                <label>Address</label>
                <textarea name="address" maxlength="255"><?= e($addr) ?></textarea>
                <?= $this->error('address') ?>
              </div>
            </div>
            <button class="btn btn-blue" type="submit">💾 Save Changes</button>
          </form>
        </div>
        <?php
    }
}
