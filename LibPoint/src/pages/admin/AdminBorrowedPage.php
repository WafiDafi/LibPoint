<?php
/** All active loans, with a Return modal. */
final class AdminBorrowedPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function permission(): ?string { return 'circulation'; }
    protected function title(): string  { return 'Borrowed Books'; }

    protected function onPost(): void
    {
        $v = new Validator($_POST);
        $v->int('borrow_id', 'Loan', 1, PHP_INT_MAX)->date('date_returned', 'Return date');
        $loan = $v->passes() ? BorrowModel::find((int)$v->value('borrow_id')) : null;

        $errs = $v->errors();
        if ($errs)                                 Session::flash('error', reset($errs));
        elseif (!$loan || $loan['status'] !== 'borrowed') Session::flash('error', 'That loan is not active.');
        elseif ($v->value('date_returned') < $loan['date_borrowed']) Session::flash('error', 'Return date cannot be before the borrow date.');
        elseif ($v->value('date_returned') > date('Y-m-d'))          Session::flash('error', 'Return date cannot be in the future.');
        else {
            $pen = BorrowModel::returnBook($loan, $v->value('date_returned'));
            if ($pen === null) Session::flash('error', 'That loan was already returned.');
            else {
                Session::flash('success', 'Book returned! Penalty: ' . peso($pen) . ($pen > 0 ? ' (fee notice emailed to the member)' : ''));
                if ($pen > 0) NotificationService::feeNotice($loan, $pen);
            }
        }
        $this->redirect('admin_borrowed');
    }

    protected function render(): void
    {
        $loans = BorrowModel::active();
        ?>
        <div class="page-title">📋 All Borrowed Books</div>

        <div class="modal-bg" id="returnModal"><div class="modal">
          <div class="modal-header">
            <div class="section-title">📦 Return Book</div>
            <button type="button" class="btn btn-gray btn-sm" onclick="closeModal('returnModal')">✕ Close</button>
          </div>
          <form method="POST" action="<?= e(url('admin_borrowed')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="borrow_id" id="returnId">
            <div class="form-group mb-lg">
              <label>Date Returned</label>
              <input type="date" name="date_returned" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
            <p class="notice">⚠️ Penalty is <strong><?= peso(PENALTY_PER_DAY) ?> per day</strong> past the due date.</p>
            <button class="btn btn-green btn-block" type="submit">Confirm Return</button>
          </form>
        </div></div>

        <div class="card">
          <div class="section-header">
            <div class="section-title">Active loans (<?= count($loans) ?>)</div>
            <a class="btn btn-blue" href="<?= e(url('admin_borrow')) ?>">➕ Borrow a Book</a>
          </div>
          <?php if (!$loans): ?>
            <div class="empty"><div class="empty-icon">📬</div>No borrowed books at the moment.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Member</th><th>Book Title</th><th>Book ID</th><th>Author</th><th>Borrowed</th><th>Due</th><th>Fee</th><th>Status</th><th>Action</th></tr>
            <?php foreach ($loans as $i => $b): $ov = new DateTime($b['due_date']) < new DateTime('today'); ?>
            <tr <?= $ov ? 'class="overdue-row"' : '' ?>>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($b['user_name']) ?></strong></td>
              <td><?= e($b['book_title']) ?></td>
              <td><code class="code"><?= e($b['copy_code'] ?? '—') ?></code></td>
              <td><?= e($b['author']) ?></td>
              <td><?= e($b['date_borrowed']) ?></td>
              <td><?= e($b['due_date']) ?><?= $ov ? '<br><span class="badge badge-red">Overdue!</span>' : '' ?></td>
              <td><?= peso($b['fee']) ?></td>
              <td><span class="badge badge-yellow">Borrowed</span></td>
              <td><button type="button" class="btn btn-green btn-sm" onclick="document.getElementById('returnId').value=<?= (int)$b['id'] ?>;openModal('returnModal')">Return</button></td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
