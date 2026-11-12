<?php
/** Admin issues a book to a member. */
final class AdminBorrowPage extends Page
{
    private array $old = [];

    protected function access(): string  { return self::STAFF; }
    protected function permission(): ?string { return 'circulation'; }
    protected function title(): string   { return 'Borrow a Book'; }
    protected function navSlug(): string { return 'admin_borrowed'; }

    protected function onPost(): void
    {
        $v = new Validator($_POST);
        $v->required('user_id', 'Member')->int('user_id', 'Member', 1, PHP_INT_MAX)
          ->required('book_id', 'Book')->int('book_id', 'Book', 1, PHP_INT_MAX)
          ->date('date_borrowed', 'Date borrowed')
          ->date('due_date', 'Due date');
        $this->old = $_POST;

        if ($v->passes()) {
            $borrowed = $v->value('date_borrowed');
            $due      = $v->value('due_date');
            $today    = date('Y-m-d');

            if ($borrowed > $today) $v->fail('date_borrowed', 'Date borrowed cannot be in the future.');
            elseif ($due <= $borrowed) $v->fail('due_date', 'Due date must be after the borrow date.');
            elseif ((new DateTime($due))->diff(new DateTime($borrowed))->days > MAX_LOAN_DAYS)
                $v->fail('due_date', 'Loan period cannot exceed ' . MAX_LOAN_DAYS . ' days.');

            $member = UserModel::find((int)$v->value('user_id'));
            $book   = BookModel::find((int)$v->value('book_id'));
            if (!$member || $member['role'] !== 'user') $v->fail('user_id', 'Select a valid member.');
            if (!$book)                                 $v->fail('book_id', 'Select a valid book.');
            elseif ($book['copies'] < 1)                $v->fail('book_id', 'No copies of this book are available.');

            $copyId = (int)$v->value('copy_id');
            if ($book && $copyId > 0) {
                $copy = CopyModel::find($copyId);
                if (!$copy || (int)$copy['book_id'] !== (int)$book['id'] || $copy['status'] !== 'available')
                    $v->fail('copy_id', 'The selected copy is not available.');
            }

            if ($v->passes()) {
                if (BorrowModel::countActiveForUser((int)$member['id']) >= MAX_ACTIVE_BORROWS)
                    $v->fail('user_id', 'This member already has ' . MAX_ACTIVE_BORROWS . ' borrowed books (the limit).');
                elseif (BorrowModel::userHasActive((int)$member['id'], (int)$book['id']))
                    $v->fail('book_id', 'This member is already borrowing this book.');
            }
        }
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        $code = BorrowModel::issue((int)$member['id'], $book, $borrowed, $due, $copyId);
        if ($code !== null) {
            Session::flash('success', "Book borrowed successfully! Book ID: $code");
            $this->redirect('admin_borrowed');
        }
        $this->errors['book_id'] = 'That copy was just taken. Pick another copy or book.';
    }

    protected function render(): void
    {
        $o = $this->old;
        ?>
        <div class="page-title">➕ Borrow a Book</div>
        <div class="card">
          <form method="POST" action="<?= e(url('admin_borrow')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid mb">
              <div class="form-group">
                <label>Member</label>
                <select name="user_id" required>
                  <option value="">— Select Member —</option>
                  <?php foreach (UserModel::members() as $u): ?>
                  <option value="<?= (int)$u['id'] ?>" <?= (string)($o['user_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                  <?php endforeach; ?>
                </select>
                <?= $this->error('user_id') ?>
              </div>
              <div class="form-group">
                <label>Book</label>
                <select name="book_id" id="bookSel" required>
                  <option value="">— Select Book —</option>
                  <?php foreach (BookModel::available() as $b): ?>
                  <option value="<?= (int)$b['id'] ?>" <?= (string)($o['book_id'] ?? '') === (string)$b['id'] ? 'selected' : '' ?>><?= e($b['title']) ?> — <?= peso($b['fee_per_day']) ?>/day (<?= (int)$b['copies'] ?> available)</option>
                  <?php endforeach; ?>
                </select>
                <?= $this->error('book_id') ?>
              </div>
              <div class="form-group">
                <label>Copy</label>
                <select name="copy_id" id="copySel"><option value="0">Auto-assign first available</option></select>
                <?= $this->error('copy_id') ?>
              </div>
              <div class="form-group">
                <label>Date Borrowed</label>
                <input type="date" name="date_borrowed" value="<?= e($o['date_borrowed'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" required>
                <?= $this->error('date_borrowed') ?>
              </div>
              <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?= e($o['due_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>" required>
                <?= $this->error('due_date') ?>
              </div>
            </div>
            <div class="btn-row">
              <button class="btn btn-blue" type="submit">✅ Confirm Borrow</button>
              <a class="btn btn-gray" href="<?= e(url('admin_borrowed')) ?>">Cancel</a>
            </div>
          </form>
        </div>
        <script>
        const COPIES = <?= json_encode(CopyModel::availableMap(), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const WANT = <?= json_encode((string)($o['copy_id'] ?? '0')) ?>;
        const bookSel = document.getElementById('bookSel'), copySel = document.getElementById('copySel');
        function fillCopies() {
          copySel.innerHTML = '<option value="0">Auto-assign first available</option>';
          (COPIES[bookSel.value] || []).forEach(c => {
            const o = document.createElement('option'); o.value = c.id; o.textContent = c.code; copySel.appendChild(o);
          });
        }
        bookSel.addEventListener('change', fillCopies);
        fillCopies(); copySel.value = WANT; if (!copySel.value) copySel.value = '0';
        </script>
        <?php
    }
}
