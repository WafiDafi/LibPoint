<?php
/** Every physical copy of one book: identifier, status, who has it. Add copies, mark lost/damaged, remove. */
final class AdminBookCopiesPage extends Page
{
    private array $book = [];

    protected function access(): string  { return self::STAFF; }
    protected function permission(): ?string { return 'catalog_view'; }
    protected function title(): string   { return 'Book Copies'; }
    protected function navSlug(): string { return 'admin_books'; }

    private function bookId(): int { return max(0, (int)($_GET['id'] ?? 0)); }

    protected function beforeRender(): void
    {
        $this->book = BookModel::find($this->bookId()) ?? [];
        if (!$this->book) { Session::flash('error', 'That book does not exist.'); $this->redirect('admin_books'); }
    }

    protected function onPost(): void
    {
        $this->requireCan('catalog_manage');
        $bookId = $this->bookId();
        $do = $_POST['do'] ?? '';
        $copy = $this->postId('copy_id') ? CopyModel::find($this->postId('copy_id')) : null;
        if ($copy && (int)$copy['book_id'] !== $bookId) $copy = null; // copy must belong to this book

        if ($do === 'add') {
            $v = new Validator($_POST);
            $v->int('qty', 'Number of copies', 1, 100);
            if ($v->passes() && BookModel::find($bookId)) {
                CopyModel::add($bookId, (int)$v->value('qty'));
                Session::flash('success', $v->value('qty') . ' copy/copies added.');
            } else Session::flash('error', $v->errors()['qty'] ?? 'That book does not exist.');
        } elseif (!$copy) {
            Session::flash('error', 'That copy does not exist.');
        } elseif ($do === 'status') {
            CopyModel::setStatus((int)$copy['id'], (string)($_POST['status'] ?? ''))
                ? Session::flash('success', "{$copy['copy_code']} updated.")
                : Session::flash('error', 'A copy that is out on loan cannot be changed.');
        } elseif ($do === 'delete') {
            CopyModel::delete((int)$copy['id'])
                ? Session::flash('success', "{$copy['copy_code']} removed.")
                : Session::flash('error', 'This copy has loan history (or is out on loan), so it cannot be removed. Mark it Lost instead.');
        }
        $this->redirect('admin_copies', ['id' => $bookId]);
    }

    protected function render(): void
    {
        $copies = CopyModel::forBook((int)$this->book['id']);
        $self = url('admin_copies', ['id' => $this->book['id']]);
        $manage = Auth::can('catalog_manage');
        $badge = ['available' => 'badge-green', 'borrowed' => 'badge-yellow', 'lost' => 'badge-red', 'damaged' => 'badge-gray'];
        ?>
        <div class="page-title">🏷️ Copies of “<?= e($this->book['title']) ?>”</div>
        <p class="muted mb"><?= e($this->book['author']) ?> · ISBN <?= e($this->book['isbn']) ?> ·
           <strong><?= (int)$this->book['copies'] ?></strong> available of <strong><?= (int)$this->book['total_copies'] ?></strong></p>

        <div class="card">
          <form method="POST" action="<?= e($self) ?>" class="search-bar">
            <?= csrf_field() ?><input type="hidden" name="do" value="add">
            <?php if ($manage): ?>
            <input type="number" name="qty" value="1" min="1" max="100" required>
            <button class="btn btn-green" type="submit">➕ Add copies</button>
            <?php endif; ?>
            <a class="btn btn-gray" href="<?= e(url('admin_books')) ?>">← Back to books</a>
          </form>
          <?php if (!$copies): ?>
            <div class="empty"><div class="empty-icon">🏷️</div>This book has no copies yet.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>Book ID</th><th>Status</th><th>Borrowed by</th><th>Due</th><th>Actions</th></tr>
            <?php foreach ($copies as $c): $out = $c['status'] === 'borrowed'; ?>
            <tr>
              <td><code class="code"><?= e($c['copy_code']) ?></code></td>
              <td><span class="badge <?= $badge[$c['status']] ?? 'badge-gray' ?>"><?= e(ucfirst($c['status'])) ?></span></td>
              <td><?= e($c['borrower'] ?? '—') ?></td>
              <td><?= e($c['due_date'] ?? '—') ?></td>
              <td class="nowrap">
                <?php if ($out || !$manage): ?>—<?php else: ?>
                <form method="POST" action="<?= e($self) ?>" class="inline">
                  <?= csrf_field() ?><input type="hidden" name="copy_id" value="<?= (int)$c['id'] ?>">
                  <select name="status" class="select-sm">
                    <?php foreach (CopyModel::STATUSES as $st): ?><option value="<?= $st ?>" <?= $c['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?>
                  </select>
                  <button class="btn btn-gray btn-sm" name="do" value="status" type="submit">Set</button>
                  <button class="btn btn-red btn-sm" name="do" value="delete" type="submit" onclick="return confirm('Remove this copy?')">Remove</button>
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
