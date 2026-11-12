<?php
/** Add a book (no id) or edit one (?id=). */
final class AdminBookFormPage extends Page
{
    private int $id = 0;
    private array $old = ['title' => '', 'author' => '', 'isbn' => '', 'fee_per_day' => '5.00', 'copies' => '1'];

    protected function access(): string  { return self::STAFF; }
    protected function permission(): ?string { return 'catalog_manage'; }
    protected function title(): string   { return $this->id ? 'Edit Book' : 'Add Book'; }
    protected function navSlug(): string { return 'admin_books'; }

    protected function beforeRender(): void
    {
        $this->id = max(0, (int)($_GET['id'] ?? 0));
        if ($this->id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $book = BookModel::find($this->id);
            if (!$book) { Session::flash('error', 'That book does not exist.'); $this->redirect('admin_books'); }
            $this->old = array_intersect_key($book, $this->old);
        }
    }

    protected function onPost(): void
    {
        $this->id = max(0, (int)($_GET['id'] ?? 0));
        if ($this->id && !BookModel::find($this->id)) { Session::flash('error', 'That book does not exist.'); $this->redirect('admin_books'); }

        $v = new Validator($_POST);
        $v->text('title', 'Title', 1, 255)
          ->text('author', 'Author', 1, 150)
          ->isbn('isbn')
          ->decimal('fee_per_day', 'Fee per day', 0, 10000);
        if (!$this->id) $v->int('copies', 'Number of copies', 0, 100);

        foreach (array_keys($this->old) as $f) $this->old[$f] = $v->value($f);
        $isbn = strtoupper(str_replace(['-', ' '], '', $this->old['isbn']));
        if ($v->passes() && BookModel::isbnTaken($isbn, $this->id)) $v->fail('isbn', 'A book with this ISBN already exists.');
        if (!$v->passes()) { $this->errors = $v->errors(); return; }

        $fee = round((float)$this->old['fee_per_day'], 2);
        $copies = (int)$this->old['copies'];
        if ($this->id) {
            BookModel::update($this->id, $this->old['title'], $this->old['author'], $isbn, $fee);
            Session::flash('success', 'Book updated.');
        } else {
            BookModel::create($this->old['title'], $this->old['author'], $isbn, $fee, $copies);
            Session::flash('success', "Book added with $copies copy/copies, each with its own ID.");
        }
        $this->redirect('admin_books');
    }

    protected function render(): void
    {
        $action = url('admin_book_form', $this->id ? ['id' => $this->id] : []);
        ?>
        <div class="page-title"><?= $this->id ? '✏️ Edit Book' : '➕ Add Book' ?></div>
        <div class="card">
          <form method="POST" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid mb">
              <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= e($this->old['title']) ?>" required maxlength="255">
                <?= $this->error('title') ?>
              </div>
              <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" value="<?= e($this->old['author']) ?>" required maxlength="150">
                <?= $this->error('author') ?>
              </div>
              <div class="form-group">
                <label>ISBN</label>
                <input type="text" name="isbn" value="<?= e($this->old['isbn']) ?>" required maxlength="17" placeholder="10 or 13 digits">
                <?= $this->error('isbn') ?>
              </div>
              <div class="form-group">
                <label>Fee per day (₱)</label>
                <input type="number" name="fee_per_day" value="<?= e($this->old['fee_per_day']) ?>" required min="0" max="10000" step="0.01">
                <?= $this->error('fee_per_day') ?>
              </div>
              <?php if (!$this->id): ?>
              <div class="form-group">
                <label>Number of copies</label>
                <input type="number" name="copies" value="<?= e($this->old['copies']) ?>" required min="0" max="100" step="1">
                <div class="hint">Each copy gets its own ID, e.g. BK0007-01, BK0007-02.</div>
                <?= $this->error('copies') ?>
              </div>
              <?php else: ?>
              <div class="form-group">
                <label>Copies</label>
                <a class="btn btn-outline" href="<?= e(url('admin_copies', ['id' => $this->id])) ?>">🏷️ Manage copies &amp; IDs</a>
              </div>
              <?php endif; ?>
            </div>
            <div class="btn-row">
              <button class="btn btn-blue" type="submit">💾 Save</button>
              <a class="btn btn-gray" href="<?= e(url('admin_books')) ?>">Cancel</a>
            </div>
          </form>
        </div>
        <?php
    }
}
