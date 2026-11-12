<?php
/** Member's own active loans. */
final class BorrowedPage extends Page
{
    protected function access(): string { return self::MEMBER; }
    protected function title(): string  { return 'My Borrowed Books'; }

    protected function render(): void
    {
        $loans = BorrowModel::active(Auth::id());
        ?>
        <div class="page-title">📋 My Borrowed Books</div>
        <div class="card">
          <?php if (!$loans): ?>
            <div class="empty"><div class="empty-icon">📬</div>You have no borrowed books.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Book Title</th><th>Book ID</th><th>Author</th><th>Date Borrowed</th><th>Due Date</th><th>Fee</th><th>Status</th></tr>
            <?php foreach ($loans as $i => $b): $ov = new DateTime($b['due_date']) < new DateTime('today'); ?>
            <tr <?= $ov ? 'class="overdue-row"' : '' ?>>
              <td><?= $i + 1 ?></td>
              <td><?= e($b['book_title']) ?></td>
              <td><code class="code"><?= e($b['copy_code'] ?? '—') ?></code></td>
              <td><?= e($b['author']) ?></td>
              <td><?= e($b['date_borrowed']) ?></td>
              <td><?= e($b['due_date']) ?><?= $ov ? '<br><span class="badge badge-red">Overdue!</span>' : '' ?></td>
              <td><?= peso($b['fee']) ?></td>
              <td><span class="badge badge-yellow">Borrowed</span></td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
