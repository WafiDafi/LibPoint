<?php
/** Admin user management: search, change role, delete. */
final class AdminUsersPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function permission(): ?string { return 'users_manage'; }
    protected function title(): string  { return 'Manage Users'; }

    protected function onPost(): void
    {
        $id   = $this->postId();
        $user = UserModel::find($id);
        $do   = $_POST['do'] ?? '';

        $me = Auth::role();

        if (!$user)                    Session::flash('error', 'That user does not exist.');
        elseif ($id === Auth::id())    Session::flash('error', 'You cannot change or delete your own account here.');
        elseif (!Permissions::canManageAccount($me, $user['role']))
                                       Session::flash('error', 'Only a System Admin can change ' . Permissions::label($user['role']) . ' accounts.');
        elseif ($do === 'role') {
            $v = new Validator($_POST);
            $v->oneOf('role', 'Role', array_keys(Permissions::ROLES));
            if (!$v->passes())
                Session::flash('error', 'Invalid role.');
            elseif (!in_array($v->value('role'), Permissions::assignableRoles($me), true))
                Session::flash('error', 'Only a System Admin can assign the ' . Permissions::label($v->value('role')) . ' role.');
            else { UserModel::setRole($id, $v->value('role')); Session::flash('success', "Role updated for {$user['name']}."); }
        }
        elseif ($do === 'delete') {
            if (!Auth::can('users_delete')) Session::flash('error', 'Only a System Admin can delete users.');
            elseif (UserModel::delete($id)) Session::flash('success', "Deleted {$user['name']}.");
            else Session::flash('error', 'Cannot delete a user who has borrow, reservation or login history.');
        }
        else Session::flash('error', 'Unknown action.');
        $this->redirect('admin_users', ['q' => $_GET['q'] ?? '']);
    }

    protected function render(): void
    {
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
        $users = UserModel::search($q);
        ?>
        <div class="page-title">👥 Manage Users</div>
        <div class="card">
          <form method="GET" action="index.php">
            <input type="hidden" name="page" value="admin_users">
            <div class="search-bar">
              <input type="text" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="🔍 Search by name or email...">
              <button class="btn btn-blue" type="submit">Search</button>
              <?php if ($q !== ''): ?><a href="<?= e(url('admin_users')) ?>" class="btn btn-gray">Clear</a><?php endif; ?>
            </div>
          </form>

          <?php if (!$users): ?>
            <div class="empty"><div class="empty-icon">👥</div>No users found.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>Name</th><th>Email</th><th>Address</th><th>Role</th><th>Actions</th></tr>
            <?php foreach ($users as $u): $self = (int)$u['id'] === Auth::id(); $manage = Permissions::canManageAccount(Auth::role(), $u['role']); ?>
            <tr>
              <td><strong><?= e($u['name']) ?></strong><?= $self ? ' <span class="badge badge-gray">you</span>' : '' ?></td>
              <td><?= e($u['email']) ?></td>
              <td class="muted small"><?= e($u['address'] ?: '—') ?></td>
              <td><span class="badge <?= $u['role'] === 'user' ? 'role-user' : 'role-admin' ?>"><?= e(Permissions::label($u['role'])) ?></span></td>
              <td class="nowrap">
                <?php if ($self): ?>—
                <?php elseif (!$manage): ?><span class="muted small">System Admin only</span>
                <?php else: ?>
                <form method="POST" action="<?= e(url('admin_users', ['q' => $q])) ?>" class="inline">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <select name="role" class="select-sm">
                    <?php foreach (Permissions::ROLES as $key => $label): if (!in_array($key, Permissions::assignableRoles(Auth::role()), true)) continue; ?>
                    <option value="<?= e($key) ?>" <?= $u['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-gray btn-sm" name="do" value="role" type="submit" onclick="return confirm('Change this user\'s role?')">Save role</button>
                  <?php if (Auth::can('users_delete')): ?>
                  <button class="btn btn-red btn-sm" name="do" value="delete" type="submit" onclick="return confirm('Delete this user permanently?')">Delete</button>
                  <?php endif; ?>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
