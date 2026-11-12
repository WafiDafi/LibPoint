<?php
final class HomePage extends Page
{
    protected function access(): string { return self::MEMBER; }
    protected function title(): string  { return 'Home'; }

    protected function render(): void
    {
        $user    = Auth::user();
        $mine    = BorrowModel::active(Auth::id());
        $overdue = count(array_filter($mine, fn($b) => new DateTime($b['due_date']) < new DateTime('today')));
        ?>
        <div class="page-title">🏠 Welcome, <?= e($user['name']) ?>! <span class="badge role-user">🎓 Member</span></div>

        <div class="stats-grid">
          <div class="stat-card"><span class="stat-num"><?= BookModel::totalCopies() ?></span><span class="stat-lbl">📚 Books Available</span></div>
          <div class="stat-card"><span class="stat-num c-yellow"><?= count($mine) ?></span><span class="stat-lbl">📋 My Borrowed</span></div>
          <div class="stat-card"><span class="stat-num c-red"><?= $overdue ?></span><span class="stat-lbl">⚠️ Overdue</span></div>
        </div>

        <div class="tile-grid">
          <a href="<?= e(url('books')) ?>" class="card tile"><div class="tile-icon">📖</div><div class="tile-title">Browse Books</div><div class="tile-sub">Search and reserve books</div></a>
          <a href="<?= e(url('borrowed')) ?>" class="card tile"><div class="tile-icon">📋</div><div class="tile-title">My Books</div><div class="tile-sub">View borrowed books</div></a>
        </div>
        <?php
    }
}
