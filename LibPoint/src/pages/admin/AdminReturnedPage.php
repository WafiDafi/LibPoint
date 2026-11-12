<?php
final class AdminReturnedPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function permission(): ?string { return 'circulation'; }
    protected function title(): string  { return 'Returned Books'; }

    protected function onPost(): void
    {
        $this->requireCan('fees');
        if (BorrowModel::markPaid($this->postId())) Session::flash('success', 'Penalty marked as paid.');
        else Session::flash('error', 'Nothing to mark as paid for that record.');
        $this->redirect('admin_returned');
    }

    protected function render(): void
    {
        $rows = BorrowModel::returned();
        ?>
        <div class="page-title">✅ Returned Books</div>
        <div class="card">
          <?php if (!$rows): ?>
            <div class="empty"><div class="empty-icon">📬</div>No returned books yet.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Member</th><th>Book</th><th>Book ID</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Fee</th><th>Penalty</th><th>Date Paid</th><th>Action</th></tr>
            <?php foreach ($rows as $i => $b): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($b['user_name']) ?></strong></td>
              <td><?= e($b['book_title']) ?></td>
              <td><code class="code"><?= e($b['copy_code'] ?? '—') ?></code></td>
              <td><?= e($b['date_borrowed']) ?></td>
              <td><?= e($b['due_date']) ?></td>
              <td><?= e($b['date_returned']) ?></td>
              <td><?= peso($b['fee']) ?></td>
              <td><?= $b['penalty'] > 0 ? '<span class="c-red bold">' . peso($b['penalty']) . '</span>' : '<span class="badge badge-green">None</span>' ?></td>
              <td>
                <?php if ($b['date_paid']): ?><span class="badge badge-green"><?= e($b['date_paid']) ?></span>
                <?php elseif ($b['penalty'] > 0): ?><span class="badge badge-red">Unpaid</span>
                <?php else: ?><span class="badge badge-gray">—</span><?php endif; ?>
              </td>
              <td>
                <?php if ($b['penalty'] > 0 && !$b['date_paid']): ?>
                <form method="POST" action="<?= e(url('admin_returned')) ?>" class="inline" onsubmit="return confirm('Mark penalty as paid?')">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button class="btn btn-green btn-sm" type="submit">Mark Paid</button>
                </form>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
