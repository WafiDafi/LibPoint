<?php
/** Member catalog: search + reserve. */
final class BooksPage extends Page
{
    protected function access(): string { return self::MEMBER; }
    protected function title(): string  { return 'Books'; }

    protected function onPost(): void
    {
        $uid  = Auth::id();
        $book = BookModel::find($this->postId('book_id'));

        if (!$book)                                          Session::flash('error', 'That book does not exist.');
        elseif (BorrowModel::userHasActive($uid, (int)$book['id'])) Session::flash('error', 'You are already borrowing this book.');
        elseif (ReservationModel::hasActive($uid, (int)$book['id'])) Session::flash('error', 'You already have an active reservation for this book.');
        else {
            ReservationModel::create($uid, (int)$book['id']);
            Session::flash('success', 'Book reserved successfully! Check your status under Reservations.');
        }
        $this->redirect('reservations');
    }

    protected function render(): void
    {
        $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
        $books = BookModel::search($q);
        ?>
        <div class="page-title">📖 Books Catalog</div>
        <div class="card">
          <form method="GET" action="index.php">
            <input type="hidden" name="page" value="books">
            <div class="search-bar">
              <input type="text" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="🔍 Search by title, author or ISBN...">
              <button class="btn btn-blue" type="submit">Search</button>
              <?php if ($q !== ''): ?><a href="<?= e(url('books')) ?>" class="btn btn-gray">Clear</a><?php endif; ?>
            </div>
          </form>
          <?php if ($q !== ''): ?><p class="muted mb">Found <strong><?= count($books) ?></strong> result(s) for "<strong><?= e($q) ?></strong>"</p><?php endif; ?>

          <?php if (!$books): ?>
            <div class="empty"><div class="empty-icon">🔍</div>No books found.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Title</th><th>Author</th><th>ISBN</th><th>Fee/Day</th><th>Copies</th><th>Action</th></tr>
            <?php foreach ($books as $i => $b): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($b['title']) ?></strong></td>
              <td><?= e($b['author']) ?></td>
              <td class="muted small"><?= e($b['isbn']) ?></td>
              <td><?= peso($b['fee_per_day']) ?></td>
              <td><?= $b['copies'] > 0 ? '<span class="badge badge-green">' . (int)$b['copies'] . ' available</span>' : '<span class="badge badge-red">Out of stock</span>' ?></td>
              <td>
                <form method="POST" action="<?= e(url('books')) ?>" class="inline" onsubmit="return confirm('Reserve this book?')">
                  <?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>">
                  <button class="btn btn-outline btn-sm" type="submit">⏳ Reserve</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
