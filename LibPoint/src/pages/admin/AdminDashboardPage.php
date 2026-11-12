<?php
final class AdminDashboardPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function title(): string  { return 'Admin Dashboard'; }

    protected function onPost(): void
    {
        $this->requireCan('notify');
        $r = NotificationService::runAll();
        Session::flash('success', "Reminders sent: {$r['emails']} email(s) covering {$r['items']} item(s)" . ($r['failed'] ? ", {$r['failed']} failed." : '.'));
        $this->redirect('admin_dashboard');
    }

    protected function render(): void
    {
        $s      = BorrowModel::stats();
        $recent = BorrowModel::active(null, 5);
        ?>
        <div class="page-title">🏠 Welcome, <?= e(Auth::user()['name']) ?>! <span class="badge role-admin">👑 <?= e(Permissions::label(Auth::role())) ?></span></div>

        <div class="stats-grid">
          <div class="stat-card"><span class="stat-num"><?= BookModel::totalCopies() ?></span><span class="stat-lbl">📚 Copies In Stock</span></div>
          <div class="stat-card"><span class="stat-num"><?= BookModel::countTitles() ?></span><span class="stat-lbl">📖 Titles</span></div>
          <div class="stat-card"><span class="stat-num"><?= UserModel::countMembers() ?></span><span class="stat-lbl">👥 Members</span></div>
          <div class="stat-card"><span class="stat-num c-yellow"><?= $s['borrowed'] ?></span><span class="stat-lbl">📋 Borrowed</span></div>
          <div class="stat-card"><span class="stat-num c-red"><?= $s['overdue'] ?></span><span class="stat-lbl">⏰ Overdue</span></div>
          <div class="stat-card"><span class="stat-num c-green"><?= $s['returned'] ?></span><span class="stat-lbl">✅ Returned</span></div>
          <div class="stat-card"><span class="stat-num"><?= ReservationModel::countPending() ?></span><span class="stat-lbl">⏳ Pending Reservations</span></div>
          <div class="stat-card"><span class="stat-num c-red"><?= peso($s['unpaid']) ?></span><span class="stat-lbl">⚠️ Unpaid Penalty</span></div>
        </div>

        <div class="card">
          <div class="section-header">
            <div class="section-title">⚡ Quick Actions</div>
          </div>
          <div class="btn-row">
            <?php if (Auth::can('circulation')): ?><a class="btn btn-blue" href="<?= e(url('admin_borrow')) ?>">➕ Borrow a Book</a><?php endif; ?>
            <?php if (Auth::can('catalog_manage')): ?><a class="btn btn-outline" href="<?= e(url('admin_book_form')) ?>">📚 Add Book</a><?php endif; ?>
            <?php if (Auth::can('users_manage')): ?><a class="btn btn-outline" href="<?= e(url('admin_users')) ?>">👥 Manage Users</a><?php endif; ?>
            <?php if (Auth::can('reports')): ?><a class="btn btn-outline" href="<?= e(url('admin_report')) ?>">📊 View Reports</a><?php endif; ?>
            <?php if (Auth::can('notify')): ?>
            <form method="POST" action="<?= e(url('admin_dashboard')) ?>" class="inline" onsubmit="return confirm('Email all members whose books are due, overdue, or who have unpaid fees?')">
              <?= csrf_field() ?><button class="btn btn-green" type="submit">📧 Send reminders now</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="section-header">
            <div class="section-title">📋 Recently Borrowed</div>
            <a href="<?= e(url('admin_borrowed')) ?>" class="btn btn-outline btn-sm">View All</a>
          </div>
          <?php if (!$recent): ?>
            <div class="empty"><div class="empty-icon">📬</div>No borrowed books yet.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>Member</th><th>Book</th><th>Due Date</th><th>Status</th></tr>
            <?php foreach ($recent as $r): $ov = new DateTime($r['due_date']) < new DateTime('today'); ?>
            <tr <?= $ov ? 'class="overdue-row"' : '' ?>>
              <td><?= e($r['user_name']) ?></td>
              <td><?= e($r['book_title']) ?></td>
              <td><?= e($r['due_date']) ?></td>
              <td><?= $ov ? '<span class="badge badge-red">Overdue</span>' : '<span class="badge badge-yellow">Borrowed</span>' ?></td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
