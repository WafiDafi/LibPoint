<?php
/** Admin book inventory: search, edit link, delete. */
final class AdminBooksPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function permission(): ?string { return 'catalog_view'; }
    protected function title(): string  { return 'Manage Books'; }

    protected function onPost(): void
    {
        $this->requireCan('catalog_manage');
        $book = BookModel::find($this->postId());
        if (!$book) Session::flash('error', 'That book does not exist.');
        elseif (BookModel::delete((int)$book['id'])) Session::flash('success', 'Deleted "' . $book['title'] . '".');
        else Session::flash('error', 'Cannot delete a book that has borrow or reservation history. Mark its copies as Lost instead.');
        $this->redirect('admin_books');
    }

    protected function render(): void
    {
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
        $books = BookModel::search($q);
        ?>
        <div class="page-title">📚 Manage Books</div>
        <div class="card">
          <div class="section-header">
            <form method="GET" action="index.php" class="search-bar grow">
              <input type="hidden" name="page" value="admin_books">
              <input type="text" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="🔍 Search by title, author or ISBN...">
              <button class="btn btn-blue" type="submit">Search</button>
              <?php if ($q !== ''): ?><a href="<?= e(url('admin_books')) ?>" class="btn btn-gray">Clear</a><?php endif; ?>
            </form>
            <?php if (Auth::can('catalog_manage')): ?><a class="btn btn-green" href="<?= e(url('admin_book_form')) ?>">➕ Add Book</a><?php endif; ?>
          </div>

          <?php if (!$books): ?>
            <div class="empty"><div class="empty-icon">📚</div>No books found.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Title</th><th>Author</th><th>ISBN</th><th>Fee/Day</th><th>Available / Total</th><th>Actions</th></tr>
            <?php foreach ($books as $i => $b): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($b['title']) ?></strong></td>
              <td><?= e($b['author']) ?></td>
              <td class="muted small"><?= e($b['isbn']) ?></td>
              <td><?= peso($b['fee_per_day']) ?></td>
              <td><span class="badge <?= $b['copies'] > 0 ? 'badge-green' : 'badge-red' ?>"><?= (int)$b['copies'] ?> / <?= (int)$b['total_copies'] ?></span></td>
              <td class="nowrap">
                <a class="btn btn-outline btn-sm" href="<?= e(url('admin_copies', ['id' => $b['id']])) ?>">🏷️ Copies</a>
                <?php if (Auth::can('catalog_manage')): ?>
                <a class="btn btn-gray btn-sm" href="<?= e(url('admin_book_form', ['id' => $b['id']])) ?>">Edit</a>
                <form method="POST" action="<?= e(url('admin_books')) ?>" class="inline" onsubmit="return confirm('Delete this book permanently?')">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button class="btn btn-red btn-sm" type="submit">Delete</button>
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
